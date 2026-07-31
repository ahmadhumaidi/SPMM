<?php

namespace App\Http\Controllers;

use App\Enums\EnrollmentStatus;
use App\Enums\PaymentStatus;
use App\Http\Requests\CompleteStudentProfileRequest;
use App\Http\Requests\RegisterLeadRequest;
use App\Models\Campus;
use App\Models\EducationNews;
use App\Models\FeeScheme;
use App\Models\Lead;
use App\Models\ReferralPartner;
use App\Services\LeadRegistrationService;
use App\Services\TenantResolver;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;
use Illuminate\View\View;
use Throwable;

class PublicRegistrationController extends Controller
{
    private const REFERRAL_COOKIE = 'spmm_referral_code';

    private const AFFILIATE_TERMS_DOCUMENT = 'documents/sk-ketentuan-fee-dan-insentif-affiliator-mahera-media-2026.docx';

    public function index(Request $request, TenantResolver $tenants): JsonResponse|View
    {
        $tenant = $tenants->resolve($request);

        if ($tenant !== null) {
            $tenant->load([
                'studyPrograms' => fn ($query) => $query->where('status', 'active'),
                'classTracks' => fn ($query) => $query->where('status', 'active'),
                'feeSchemes' => fn ($query) => $query->where('is_active', true),
            ]);

            $this->hideOptionsWithoutFeeScheme($tenant);

            if ($request->wantsJson()) {
                return response()->json(['data' => [$tenant]]);
            }

            $tenant->loadMissing([
                'studyPrograms' => fn ($query) => $query->where('status', 'active'),
                'classTracks' => fn ($query) => $query->where('status', 'active'),
                'feeSchemes' => fn ($query) => $query->where('is_active', true),
            ]);

            return view('public.partner-campus-site', [
                'campus' => $tenant,
                'educationNews' => $this->campusNews($tenant),
            ]);
        }

        $campuses = Campus::query()
            ->where('status', 'active')
            ->with(['studyPrograms' => fn ($query) => $query->where('status', 'active'), 'classTracks' => fn ($query) => $query->where('status', 'active'), 'feeSchemes' => fn ($query) => $query->where('is_active', true)])
            ->get();

        $educationNews = EducationNews::query()
            ->with(['campus', 'campuses'])
            ->where('status', 'published')
            ->where(fn ($query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->latest('published_at')
            ->latest()
            ->limit(3)
            ->get();

        if (! $request->wantsJson()) {
            return view('public.campuses', ['campuses' => $campuses, 'tenant' => null, 'educationNews' => $educationNews]);
        }

        return response()->json(['data' => $campuses]);
    }

    public function create(Request $request, TenantResolver $tenants): View
    {
        $tenant = $tenants->resolve($request);
        $referralCode = $request->query('ref')
            ?: $request->session()->get('referral_code');

        if ($request->query('ref')) {
            $request->session()->put('referral_code', $request->query('ref'));
        }

        $campuses = Campus::query()
            ->where('status', 'active')
            ->with([
                'studyPrograms' => fn ($query) => $query->where('status', 'active'),
                'classTracks' => fn ($query) => $query->where('status', 'active'),
                'feeSchemes' => fn ($query) => $query->where('is_active', true),
            ])
            ->when($tenant, fn ($query) => $query->whereKey($tenant->id))
            ->get()
            ->each(fn (Campus $campus) => $this->hideOptionsWithoutFeeScheme($campus));

        return view('public.register', [
            'campuses' => $campuses,
            'selectedCampus' => $tenant,
            'referralCode' => $referralCode,
        ]);
    }

    public function showCampus(Request $request, Campus|string $campus): RedirectResponse|View
    {
        $campus = $this->resolveCampusRouteValue($campus);

        abort_unless($campus->status->value === 'active', 404);

        $publicUrl = $campus->publicUrl();
        $publicHost = parse_url($publicUrl, PHP_URL_HOST);

        if (! app()->environment('local') && filled($publicHost) && $request->getHost() !== $publicHost) {
            return redirect()->away($publicUrl);
        }

        $campus->load([
            'studyPrograms' => fn ($query) => $query->where('status', 'active'),
            'classTracks' => fn ($query) => $query->where('status', 'active'),
            'feeSchemes' => fn ($query) => $query->where('is_active', true),
        ]);

        $this->hideOptionsWithoutFeeScheme($campus);

        return view('public.partner-campus-site', [
            'campus' => $campus,
            'educationNews' => $this->campusNews($campus),
        ]);
    }

    public function showNews(EducationNews $news): View
    {
        abort_unless($news->status === 'published', 404);
        abort_if($news->published_at && $news->published_at->isFuture(), 404);

        $news->load(['campus', 'campuses']);

        return view('public.news-detail', compact('news'));
    }

    public function newsIndex(): View
    {
        $educationNews = EducationNews::query()
            ->with(['campus', 'campuses'])
            ->where('status', 'published')
            ->where(fn ($query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->latest('published_at')
            ->latest()
            ->paginate(12);

        return view('public.news-index', [
            'educationNews' => $educationNews,
            'campus' => null,
            'title' => 'Berita Pendidikan',
            'subtitle' => 'Kumpulan informasi pendidikan, kampus, karier, kelas fleksibel, dan PMB.',
        ]);
    }

    public function affiliate(Request $request): View
    {
        if ($request->query('ref')) {
            $request->session()->put('affiliate_referral_code', $request->query('ref'));
        }

        return view('public.affiliate', [
            'publicUrl' => rtrim(config('spmm.public_url', 'https://kampus.media'), '/'),
            'affiliateReferralCode' => $request->session()->get('affiliate_referral_code'),
        ]);
    }

    public function createAffiliate(Request $request): View|RedirectResponse
    {
        if ($request->getHost() === 'affiliate.kampus.media' && $request->path() === 'affiliate/daftar') {
            return redirect('/daftar');
        }

        return view('public.affiliate-register');
    }

    public function affiliateLogin(): View
    {
        return view('public.affiliate-login', [
            'studentLoginUrl' => url('/mahasiswa/login'),
        ]);
    }

    public function authenticateAffiliate(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $partner = ReferralPartner::query()
            ->where('type', 'umum')
            ->where('email', $data['email'])
            ->whereNotNull('email_verified_at')
            ->where('status', 'active')
            ->first();

        if (! $partner || blank($partner->password) || ! Hash::check($data['password'], $partner->password)) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Email atau password tidak sesuai, atau akun belum aktif. Pastikan email sudah diverifikasi.']);
        }

        $request->session()->put('affiliate_partner_id', $partner->id);
        $request->session()->put('affiliate_referral_code', $partner->referral_code);
        $request->session()->regenerate();

        return redirect()->to(url('/dashboard'));
    }

    public function forgotAffiliatePassword(): View
    {
        return view('public.affiliate-forgot-password');
    }

    public function sendAffiliatePasswordReset(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
        ]);

        $partner = ReferralPartner::query()
            ->where('type', 'umum')
            ->where('email', $data['email'])
            ->whereNotNull('email_verified_at')
            ->where('status', 'active')
            ->first();

        if ($partner) {
            $partner->update([
                'password_reset_token' => Str::random(64),
                'password_reset_sent_at' => now(),
            ]);

            $this->sendAffiliatePasswordResetEmail($partner);
        }

        return back()->with('status', 'Jika email terdaftar dan aktif, link reset password sudah dikirim. Silakan cek inbox atau folder spam.');
    }

    public function resetAffiliatePassword(string $token): View
    {
        $partner = $this->resolveAffiliatePasswordResetPartner($token);

        return view('public.affiliate-reset-password', [
            'token' => $token,
            'email' => $partner->email,
        ]);
    }

    public function updateAffiliatePassword(Request $request, string $token): RedirectResponse
    {
        $partner = $this->resolveAffiliatePasswordResetPartner($token);

        $data = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password.required' => 'Password baru wajib diisi.',
            'password.min' => 'Password baru minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password baru tidak sama.',
        ]);

        $partner->update([
            'password' => Hash::make($data['password']),
            'password_reset_token' => null,
            'password_reset_sent_at' => null,
        ]);

        return redirect()->to(url('/login'))->with('status', 'Password berhasil diganti. Silakan login menggunakan password baru.');
    }

    public function affiliateDashboardHome(Request $request): RedirectResponse|View
    {
        $partnerId = $request->session()->get('affiliate_partner_id');

        if (! $partnerId) {
            return redirect()->to(url('/login'))->with('status', 'Silakan login sebagai Affiliator Umum terlebih dahulu.');
        }

        $partner = ReferralPartner::query()
            ->whereKey($partnerId)
            ->where('type', 'umum')
            ->where('status', 'active')
            ->first();

        if (! $partner) {
            $request->session()->forget(['affiliate_partner_id', 'affiliate_referral_code']);

            return redirect()->to(url('/login'))->withErrors(['email' => 'Sesi dashboard tidak valid. Silakan login ulang.']);
        }

        return $this->affiliateDashboardView($partner);
    }

    public function logoutAffiliate(Request $request): RedirectResponse
    {
        $request->session()->forget(['affiliate_partner_id', 'affiliate_referral_code']);

        return redirect()->to(url('/login'))->with('status', 'Kamu sudah keluar dari dashboard affiliator.');
    }

    public function storeAffiliate(Request $request): RedirectResponse
    {
        $validator = validator($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:referral_partners,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => ['required', 'string', 'max:32'],
            'address' => ['required', 'string', 'max:2000'],
            'source_information' => ['required', 'string', 'max:255'],
            'npwp' => ['required', 'string', 'max:32'],
            'bank_name' => ['required', 'string', 'max:100'],
            'bank_account_number' => ['required', 'string', 'max:64'],
            'bank_account_name' => ['required', 'string', 'max:255'],
            'identity_card' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
            'terms_accepted' => ['accepted'],
            'tax_notice_accepted' => ['accepted'],
            'digital_signature' => ['required', 'string'],
        ], [
            'email.unique' => 'Email ini sudah terdaftar sebagai affiliate. Jika belum menerima email verifikasi, hubungi admin Kampus Media.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak sama.',
            'npwp.required' => 'NPWP wajib diisi.',
            'identity_card.required' => 'Upload KTP wajib diisi.',
            'identity_card.mimes' => 'KTP harus berupa JPG, PNG, atau PDF.',
            'identity_card.max' => 'Ukuran file KTP maksimal 4 MB.',
            'terms_accepted.accepted' => 'Syarat dan ketentuan wajib disetujui.',
            'tax_notice_accepted.accepted' => 'Persetujuan pajak wajib dicentang.',
            'digital_signature.required' => 'Tanda tangan digital wajib diisi.',
        ]);

        $validator->after(function (Validator $validator) use ($request): void {
            if ($this->comparableName($request->input('name')) !== $this->comparableName($request->input('bank_account_name'))) {
                $validator->errors()->add('bank_account_name', 'Atas nama rekening wajib sama dengan nama sesuai KTP.');
            }

            if (! $this->isValidAffiliateSignature($request->input('digital_signature'))) {
                $validator->errors()->add('digital_signature', 'Tanda tangan digital wajib diisi dengan benar.');
            }
        });

        $data = $validator->validate();
        $signaturePath = $this->storeAffiliateSignature($data['digital_signature']);

        $partner = ReferralPartner::query()->create([
            'name' => $data['name'],
            'type' => 'umum',
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'phone' => $data['phone'],
            'address' => $data['address'],
            'source_information' => $data['source_information'],
            'npwp' => $data['npwp'],
            'bank_name' => $data['bank_name'],
            'bank_account_number' => $data['bank_account_number'],
            'bank_account_name' => $data['bank_account_name'],
            'identity_card_path' => $request->file('identity_card')->store('affiliate-identity-cards', 'public'),
            'terms_document_path' => self::AFFILIATE_TERMS_DOCUMENT,
            'terms_accepted_at' => now(),
            'tax_notice_accepted_at' => now(),
            'digital_signature_path' => $signaturePath,
            'verification_token' => Str::random(64),
            'dashboard_token' => Str::random(64),
            'referral_code' => 'pending-'.Str::lower(Str::random(24)),
            'commission_amount' => 0,
            'status' => 'inactive',
            'notes' => 'Pendaftaran affiliate umum dari halaman publik. Menunggu verifikasi email.',
        ]);

        $partner->update([
            'referral_code' => $this->affiliateReferralCodeForId($partner->id),
        ]);

        $this->sendAffiliateVerificationEmail($partner);

        return redirect()
            ->to($this->affiliateRegisterUrl())
            ->with('status', 'Pendaftaran affiliate berhasil dikirim. Silakan cek email untuk mengaktifkan akun affiliate kamu.');
    }

    public function verifyAffiliate(string $token): RedirectResponse
    {
        $partner = ReferralPartner::query()
            ->where('verification_token', $token)
            ->where('type', 'umum')
            ->firstOrFail();

        $partner->update([
            'verification_token' => null,
            'dashboard_token' => $partner->dashboard_token ?: Str::random(64),
            'email_verified_at' => now(),
            'status' => 'active',
            'notes' => trim(($partner->notes ? $partner->notes."\n" : '').'Email affiliate umum sudah diverifikasi pada '.now()->format('Y-m-d H:i:s').'.'),
        ]);

        request()->session()->put('affiliate_referral_code', $partner->referral_code);

        return redirect()
            ->to($this->affiliateRegisterUrl())
            ->with('status', 'Email berhasil diverifikasi. Akun affiliate kamu sudah aktif.')
            ->with('affiliate_activated', true)
            ->with('referral_code', $partner->referral_code)
            ->with('referral_link', rtrim(config('spmm.public_url', 'https://kampus.media'), '/').'/daftar?ref='.$partner->referral_code)
            ->with('dashboard_url', $this->affiliateDashboardUrl($partner));
    }

    public function affiliateDashboard(string $token): View
    {
        $partner = ReferralPartner::query()
            ->where('dashboard_token', $token)
            ->where('type', 'umum')
            ->firstOrFail();

        request()->session()->put('affiliate_partner_id', $partner->id);
        request()->session()->put('affiliate_referral_code', $partner->referral_code);

        return $this->affiliateDashboardView($partner);
    }

    private function affiliateDashboardView(ReferralPartner $partner): View
    {
        $partner->load([
            'conversions' => fn ($query) => $query
                ->with([
                    'lead.campus',
                    'lead.studyProgram',
                    'lead.latestInvoice',
                    'lead.studentPayments' => fn ($payments) => $payments->orderBy('month')->orderBy('due_date'),
                ])
                ->latest('registered_at')
                ->latest('id'),
        ]);

        $conversions = $partner->conversions;

        return view('public.affiliate-dashboard', [
            'partner' => $partner,
            'conversions' => $conversions,
            'registeredCount' => $conversions->count(),
            'paidCount' => $conversions->whereNotNull('herregistration_paid_at')->count(),
            'eligibleCommission' => $conversions->sum(fn ($conversion): int =>
                (in_array($conversion->herregistration_commission_status, ['approved', 'paid'], true) ? (int) $conversion->herregistration_commission_amount : 0)
                + (in_array($conversion->semester1_commission_status, ['approved', 'paid'], true) ? (int) $conversion->semester1_commission_amount : 0)
            ),
            'paidCommission' => $conversions->sum(fn ($conversion): int =>
                ($conversion->herregistration_commission_status === 'paid' ? (int) $conversion->herregistration_commission_amount : 0)
                + ($conversion->semester1_commission_status === 'paid' ? (int) $conversion->semester1_commission_amount : 0)
            ),
            'referralLink' => rtrim(config('spmm.public_url', 'https://kampus.media'), '/').'/daftar?ref='.$partner->referral_code,
        ]);
    }

    public function campusNewsIndex(Request $request, Campus|string $campus): RedirectResponse|View
    {
        $campus = $this->resolveCampusRouteValue($campus);

        abort_unless($campus->status->value === 'active', 404);

        $publicUrl = $campus->publicUrl();
        $publicHost = parse_url($publicUrl, PHP_URL_HOST);

        if (! app()->environment('local') && filled($publicHost) && $request->getHost() !== $publicHost) {
            return redirect()->away(rtrim($publicUrl, '/').'/kampus/'.$campus->slug.'/berita');
        }

        $educationNews = $this->campusNewsQuery($campus)->paginate(12);

        return view('public.news-index', [
            'educationNews' => $educationNews,
            'campus' => $campus,
            'title' => 'Berita '.$campus->name,
            'subtitle' => 'Informasi pendidikan dan update PMB yang relevan untuk '.$campus->name.'.',
        ]);
    }

    public function store(RegisterLeadRequest $request, LeadRegistrationService $registration): JsonResponse|RedirectResponse
    {
        $data = $request->validated();
        $data['referral_code'] = ($data['referral_code'] ?? null)
            ?: $request->session()->get('referral_code');

        $result = $registration->register($data);
        $request->session()->forget('referral_code');

        if (! $request->wantsJson()) {
            return redirect()
                ->route('registration.thank-you', $result['lead'])
                ->withCookie(cookie()->forget(self::REFERRAL_COOKIE));
        }

        return response()->json([
            'lead_id' => $result['lead']->id,
            'invoice_number' => $result['invoice']->invoice_number,
            'amount' => $result['invoice']->amount,
            'payment_url' => $result['invoice']->payment_url,
            'expires_at' => $result['invoice']->expires_at,
        ], 201)->withCookie(cookie()->forget(self::REFERRAL_COOKIE));
    }

    public function thankYou(Request $request, Lead $lead): JsonResponse|View
    {
        $lead->load(['latestInvoice', 'campus']);

        if (! $request->wantsJson()) {
            return view('public.thank-you', [
                'lead' => $lead,
                'invoice' => $lead->latestInvoice,
            ]);
        }

        return response()->json([
            'lead' => $lead,
            'invoice' => $lead->latestInvoice,
        ]);
    }

    public function localVerificationEmail(Lead $lead)
    {
        abort_unless(app()->environment('local'), 404);

        $path = "local-emails/lead-{$lead->id}.html";

        abort_unless(Storage::disk('local')->exists($path), 404);

        return response(Storage::disk('local')->get($path), 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }

    public function showPemberkasan(Request $request, string $token): JsonResponse|View
    {
        $lead = $this->resolvePaidLeadByToken($token);

        if (! $request->wantsJson()) {
            return view('public.pemberkasan', ['lead' => $lead]);
        }

        return response()->json([
            'lead_id' => $lead->id,
            'full_name' => $lead->full_name,
            'status' => $lead->enrollment_status,
        ]);
    }

    public function storePemberkasan(CompleteStudentProfileRequest $request, string $token, AuditLogger $audit): JsonResponse|RedirectResponse
    {
        $lead = $this->resolvePaidLeadByToken($token);

        $profile = $lead->studentProfile()->updateOrCreate(
            ['lead_id' => $lead->id],
            array_merge($request->validated(), [
                'completed_at' => now(),
                'pddikti_payload_json' => $request->validated(),
            ])
        );

        $lead->update([
            'enrollment_status' => EnrollmentStatus::MenungguPemutakhiran,
            'locked_at' => $lead->locked_at ?? now(),
        ]);

        $audit->record('student_profile_completed', $profile, ['lead_id' => $lead->id]);
        $audit->record('enrollment_status_changed', $lead, ['to' => EnrollmentStatus::MenungguPemutakhiran->value]);

        if (! $request->wantsJson()) {
            return redirect()->route('student-profile.show', $token)->with('status', 'Pemberkasan berhasil disimpan. Tim PMB akan memproses pemutakhiran data.');
        }

        return response()->json([
            'message' => 'Pemberkasan berhasil disimpan.',
            'student_profile_id' => $profile->id,
            'enrollment_status' => $lead->fresh()->enrollment_status,
        ]);
    }

    private function resolvePaidLeadByToken(string $token): Lead
    {
        return Lead::query()
            ->with('campus')
            ->where('pemberkasan_token', $token)
            ->where('payment_status', PaymentStatus::Paid)
            ->firstOrFail();
    }

    private function affiliateReferralCodeForId(int $id): string
    {
        $base = 'aff'.str_pad((string) $id, 3, '0', STR_PAD_LEFT);

        if (! ReferralPartner::query()
            ->where('referral_code', $base)
            ->whereKeyNot($id)
            ->exists()) {
            return $base;
        }

        $suffix = 2;
        do {
            $code = $base.'-'.$suffix++;
        } while (ReferralPartner::query()
            ->where('referral_code', $code)
            ->whereKeyNot($id)
            ->exists());

        return $code;
    }

    private function comparableName(?string $value): string
    {
        return Str::of((string) $value)
            ->lower()
            ->replaceMatches('/[^a-z0-9\s]/', ' ')
            ->squish()
            ->toString();
    }

    private function isValidAffiliateSignature(?string $value): bool
    {
        if (! is_string($value) || ! str_starts_with($value, 'data:image/png;base64,')) {
            return false;
        }

        $decoded = base64_decode(substr($value, strlen('data:image/png;base64,')), true);

        return is_string($decoded) && strlen($decoded) > 1000;
    }

    private function storeAffiliateSignature(string $value): string
    {
        $binary = base64_decode(substr($value, strlen('data:image/png;base64,')), true);
        $path = 'affiliate-signatures/'.Str::uuid().'.png';

        Storage::disk('public')->put($path, $binary ?: '');

        return $path;
    }

    private function affiliateRegisterUrl(): string
    {
        if (request()->getHost() === 'affiliate.kampus.media') {
            return url('/daftar');
        }

        return route('affiliate.register');
    }

    private function sendAffiliateVerificationEmail(ReferralPartner $partner): void
    {
        $verificationUrl = route('affiliate.verify', $partner->verification_token);

        $body = "Halo {$partner->name},\n\n".
            "Terima kasih sudah mendaftar sebagai affiliate Kampus Media.\n\n".
            "Silakan verifikasi email kamu untuk mengaktifkan akun affiliate melalui link berikut:\n{$verificationUrl}\n\n".
            "Setelah aktif, link referral kamu adalah:\n".
            rtrim(config('spmm.public_url', 'https://kampus.media'), '/').'/daftar?ref='.$partner->referral_code."\n\n".
            "Dashboard affiliator untuk memantau referral dan status komisi:\nhttps://affiliate.kampus.media/login\n\n".
            "Catatan: rekening komisi wajib atas nama pendaftar sendiri.\n\n".
            "Salam,\nKampus Media";

        try {
            Mail::raw($body, function ($message) use ($partner): void {
                $message
                    ->to($partner->email, $partner->name)
                    ->subject('Verifikasi Affiliate Kampus Media');
            });
        } catch (Throwable $exception) {
            Log::warning('Affiliate verification email failed.', [
                'referral_partner_id' => $partner->id,
                'email' => $partner->email,
                'message' => $exception->getMessage(),
            ]);
        }

        if (app()->environment('local')) {
            Storage::disk('local')->put("local-emails/affiliate-{$partner->id}.txt", $body);
        }
    }

    private function sendAffiliatePasswordResetEmail(ReferralPartner $partner): void
    {
        $resetUrl = 'https://affiliate.kampus.media/reset-password/'.$partner->password_reset_token;

        $body = "Halo {$partner->name},\n\n".
            "Kami menerima permintaan reset password dashboard affiliate Kampus Media.\n\n".
            "Klik link berikut untuk membuat password baru:\n{$resetUrl}\n\n".
            "Link ini berlaku selama 60 menit. Abaikan email ini jika kamu tidak meminta reset password.\n\n".
            "Salam,\nKampus Media";

        try {
            Mail::raw($body, function ($message) use ($partner): void {
                $message
                    ->to($partner->email, $partner->name)
                    ->subject('Reset Password Affiliate Kampus Media');
            });
        } catch (Throwable $exception) {
            Log::warning('Affiliate password reset email failed.', [
                'referral_partner_id' => $partner->id,
                'email' => $partner->email,
                'message' => $exception->getMessage(),
            ]);
        }

        if (app()->environment('local')) {
            Storage::disk('local')->put("local-emails/affiliate-password-reset-{$partner->id}.txt", $body);
        }
    }

    private function resolveAffiliatePasswordResetPartner(string $token): ReferralPartner
    {
        $partner = ReferralPartner::query()
            ->where('type', 'umum')
            ->where('password_reset_token', $token)
            ->whereNotNull('password_reset_sent_at')
            ->firstOrFail();

        if ($partner->password_reset_sent_at->lt(now()->subMinutes(60))) {
            abort(403, 'Link reset password sudah kedaluwarsa. Silakan minta link baru.');
        }

        return $partner;
    }

    private function affiliateDashboardUrl(ReferralPartner $partner): string
    {
        $token = $partner->dashboard_token ?: '';

        if (request()->getHost() === 'affiliate.kampus.media') {
            return url('/dashboard/'.$token);
        }

        return route('affiliate.dashboard', $token);
    }

    private function resolveCampusRouteValue(Campus|string $campus): Campus
    {
        if ($campus instanceof Campus) {
            return $campus;
        }

        return Campus::query()
            ->where('name', $campus)
            ->orWhere('slug', $campus)
            ->firstOrFail();
    }

    /**
     * Hide study programs and class tracks that have no active fee scheme
     * configured, so prospective students can't pick a combination that
     * would fail registration with "biaya pendaftaran belum diatur".
     */
    private function hideOptionsWithoutFeeScheme(Campus $campus): void
    {
        $feeSchemes = $campus->feeSchemes;

        $studyPrograms = $campus->studyPrograms->filter(
            fn ($program): bool => $feeSchemes->contains(
                fn (FeeScheme $fee): bool => $fee->study_program_id === null || $fee->study_program_id === $program->id
            )
        )->values();

        $classTracks = $campus->classTracks->filter(
            fn ($track): bool => $feeSchemes->contains(
                fn (FeeScheme $fee): bool => $fee->class_track_id === null || $fee->class_track_id === $track->id
            )
        )->values();

        $campus->setRelation('studyPrograms', $studyPrograms);
        $campus->setRelation('classTracks', $classTracks);
    }

    private function campusNews(Campus $campus)
    {
        return $this->campusNewsQuery($campus)
            ->limit(9)
            ->get();
    }

    private function campusNewsQuery(Campus $campus)
    {
        return EducationNews::query()
            ->with(['campus', 'campuses'])
            ->where('status', 'published')
            ->where(fn ($query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->where(function ($query) use ($campus): void {
                $query
                    ->whereDoesntHave('campuses')
                    ->orWhereHas('campuses', fn ($campusQuery) => $campusQuery->whereKey($campus->id))
                    ->orWhere('campus_id', $campus->id);
            })
            ->latest('published_at')
            ->latest();
    }
}
