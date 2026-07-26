<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Resources\ManualStudentPaymentResource;
use App\Models\StudentAccount;
use App\Models\ClassTrack;
use App\Models\FeeScheme;
use App\Models\Lead;
use App\Models\LmsAssignment;
use App\Models\LmsModule;
use App\Models\LmsSubmission;
use App\Models\ReferralPartner;
use App\Models\StudyPlanItem;
use App\Models\StudyProgram;
use App\Models\StudentDocument;
use App\Models\StudentPayment;
use App\Models\User;
use App\Support\ReceiptPdfRenderer;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Throwable;

class StudentPortalController extends Controller
{
    public function login(Request $request): View
    {
        $redirectTo = $request->query('redirect');

        if (is_string($redirectTo) && Str::startsWith($redirectTo, url('/mahasiswa'))) {
            $request->session()->put('student_portal.intended_url', $redirectTo);
        }

        return view('student-portal.login');
    }

    public function authenticate(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $account = StudentAccount::query()
            ->with(['lead.campus', 'lead.studyProgram', 'lead.latestInvoice'])
            ->where('email', $credentials['email'])
            ->first();

        if (! $account || ! Hash::check($credentials['password'], $account->password)) {
            return back()
                ->withErrors(['email' => 'Email atau password tidak sesuai.'])
                ->onlyInput('email');
        }

        $account->update(['last_login_at' => now()]);
        $request->session()->put('student_account_id', $account->id);

        $intendedUrl = $request->session()->pull('student_portal.intended_url');

        return $intendedUrl
            ? redirect()->to($intendedUrl)
            : redirect()->route('student-portal.dashboard');
    }

    public function verify(string $token, Request $request): RedirectResponse
    {
        $account = StudentAccount::query()
            ->where('verification_token', $token)
            ->firstOrFail();

        $account->update([
            'email_verified_at' => now(),
            'verification_token' => null,
        ]);

        $request->session()->put('student_account_id', $account->id);

        return redirect()->route('student-portal.dashboard')->with('status', 'Email berhasil diverifikasi.');
    }

    public function forgotPassword(): View
    {
        return view('student-portal.forgot-password');
    }

    public function sendPasswordReset(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
        ]);

        $account = StudentAccount::query()
            ->with('lead')
            ->where('email', $data['email'])
            ->first();

        if ($account) {
            $account->update([
                'password_reset_token' => Str::random(64),
                'password_reset_sent_at' => now(),
            ]);

            $this->sendStudentPasswordResetEmail($account);
        }

        return back()->with('status', 'Jika email terdaftar, link reset password sudah dikirim. Silakan cek inbox, spam, atau promosi.');
    }

    public function resetPassword(string $token): View
    {
        $account = $this->resolvePasswordResetAccount($token);

        return view('student-portal.reset-password', [
            'token' => $token,
            'email' => $account->email,
        ]);
    }

    public function updateResetPassword(Request $request, string $token): RedirectResponse
    {
        $account = $this->resolvePasswordResetAccount($token);

        $data = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password.required' => 'Password baru wajib diisi.',
            'password.min' => 'Password baru minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password baru tidak sama.',
        ]);

        $account->update([
            'password' => Hash::make($data['password']),
            'password_reset_token' => null,
            'password_reset_sent_at' => null,
        ]);

        return redirect()->route('student-portal.login')->with('status', 'Password berhasil diganti. Silakan login menggunakan password baru.');
    }

    public function dashboard(Request $request): View|RedirectResponse
    {
        $account = $this->currentAccount($request);

        if (! $account) {
            $request->session()->put('student_portal.intended_url', $request->fullUrl());

            return redirect()->route('student-portal.login');
        }

        $billablePayments = $this->billableStudentPayments($account->lead->studentPayments);

        $currentMonthPayments = $this->activeStudentPayments($billablePayments);

        $activeBillTotal = $currentMonthPayments->sum('amount');
        $billingMonthLabel = Carbon::now()->translatedFormat('F Y');

        return view('student-portal.dashboard', compact('account', 'currentMonthPayments', 'activeBillTotal', 'billingMonthLabel'));
    }

    public function profile(Request $request): View|RedirectResponse
    {
        $account = $this->currentAccount($request);

        if (! $account) {
            return redirect()->route('student-portal.login');
        }

        $biodata = $account->lead->studentBiodata;
        $studyPrograms = StudyProgram::query()
            ->where('campus_id', $account->lead->campus_id)
            ->orderBy('degree_level')
            ->orderBy('name')
            ->get();
        $classTracks = ClassTrack::query()
            ->where('campus_id', $account->lead->campus_id)
            ->whereIn('name', ['Full Online', 'Hybird', 'Hybrid', 'Kelas Professional', 'Kelas RPL'])
            ->orderBy('name')
            ->get();
        $feeScheme = $this->feeSchemeFor($account);

        return view('student-portal.profile', compact('account', 'biodata', 'studyPrograms', 'classTracks', 'feeScheme'));
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $account = $this->currentAccount($request);

        if (! $account) {
            return redirect()->route('student-portal.login');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'selection_number' => ['nullable', 'string', 'max:64'],
            'study_program_id' => ['nullable', 'exists:study_programs,id'],
            'class_track_id' => ['nullable', 'exists:class_tracks,id'],
            'group' => ['nullable', 'string', 'max:64'],
            'information_source' => ['nullable', 'string', 'max:64'],
            'affiliator_code' => ['nullable', 'string', 'max:64'],
            'information_source_other' => ['nullable', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'birth_place' => ['nullable', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date'],
            'religion' => ['nullable', 'string', 'max:64'],
            'gender' => ['nullable', 'string', 'max:32'],
            'address' => ['nullable', 'string'],
            'mailing_address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:255'],
            'village' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:16'],
            'rt_rw' => ['nullable', 'string', 'max:32'],
            'phone' => ['nullable', 'string', 'max:32'],
            'mobile_phone' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:255'],
            'whatsapp_number' => ['nullable', 'string', 'max:32'],
            'identity_number' => ['nullable', 'string', 'max:32'],
            'mother_name' => ['nullable', 'string', 'max:255'],
            'family_number' => ['nullable', 'string', 'max:64'],
            'family_name' => ['nullable', 'string', 'max:255'],
            'family_relationship' => ['nullable', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'company_address' => ['nullable', 'string'],
            'company_city' => ['nullable', 'string', 'max:255'],
            'company_postal_code' => ['nullable', 'string', 'max:16'],
            'company_phone' => ['nullable', 'string', 'max:32'],
            'development_contribution' => ['nullable', 'integer', 'min:0'],
            'semester_education_contribution' => ['nullable', 'integer', 'min:0'],
        ]);

        $biodata = $account->lead->studentBiodata;

        if ($request->hasFile('photo')) {
            if ($biodata?->photo_path) {
                Storage::disk('public')->delete($biodata->photo_path);
            }

            $data['photo_path'] = $request->file('photo')->store('student-photos', 'public');
        }

        unset($data['photo']);

        $feeScheme = $this->feeSchemeFor($account, $data['study_program_id'] ?? null, $data['class_track_id'] ?? null);
        $financialStatus = str($account->lead->payment_status->value)->replace('_', ' ')->title()->toString();

        $account->lead->studentBiodata()->updateOrCreate(
            ['lead_id' => $account->lead_id],
            array_merge($data, [
                'student_type' => 'new',
                'campus_id' => $account->lead->campus_id,
                'study_program_id' => $data['study_program_id'] ?? $account->lead->study_program_id,
                'class_track_id' => $data['class_track_id'] ?? $account->lead->class_track_id,
                'selection_number' => $account->lead->studentBiodata?->selection_number ?? $this->selectionNumberFor($account),
                'student_number' => $account->lead->studentNumber?->nim,
                'financial_status' => $financialStatus,
                'email' => $data['email'] ?? $account->email,
                'whatsapp_number' => $data['whatsapp_number'] ?? $account->lead->whatsapp_number,
                'development_contribution' => $feeScheme?->building_fee ?? 0,
                'semester_education_contribution' => $feeScheme?->monthly_tuition_fee ?: ($feeScheme?->ukt_total ?? 0),
                'almamater_jacket_fee' => 0,
                'form_fee' => $feeScheme?->registration_fee ?? 0,
            ]),
        );

        $account->lead->update([
            'full_name' => $data['name'],
            'email' => $data['email'] ?? $account->email,
            'whatsapp_number' => $data['whatsapp_number'] ?? $account->lead->whatsapp_number,
            'study_program_id' => $data['study_program_id'] ?? $account->lead->study_program_id,
            'class_track_id' => $data['class_track_id'] ?? $account->lead->class_track_id,
        ]);

        return redirect()->route('student-portal.profile')->with('status', 'Biodata berhasil disimpan ke sistem pusat.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $account = $this->currentAccount($request);

        if (! $account) {
            return redirect()->route('student-portal.login');
        }

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'current_password.required' => 'Password lama wajib diisi.',
            'password.required' => 'Password baru wajib diisi.',
            'password.min' => 'Password baru minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password baru tidak sesuai.',
        ]);

        if (! Hash::check($data['current_password'], $account->password)) {
            return back()
                ->withErrors(['current_password' => 'Password lama tidak sesuai.'])
                ->withInput();
        }

        $account->update([
            'password' => Hash::make($data['password']),
        ]);

        return redirect()
            ->route('student-portal.account-settings')
            ->with('status', 'Password akun mahasiswa berhasil diperbarui.');
    }

    public function accountSettings(Request $request): View|RedirectResponse
    {
        $account = $this->currentAccount($request);

        if (! $account) {
            return redirect()->route('student-portal.login');
        }

        return view('student-portal.account-settings', ['account' => $account]);
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget('student_account_id');

        return redirect()->route('student-portal.login');
    }

    public function payments(Request $request): View|RedirectResponse
    {
        $account = $this->currentAccount($request);

        if (! $account) {
            return redirect()->route('student-portal.login');
        }

        $payments = $account->lead->studentPayments()
            ->orderBy('month')
            ->orderBy('due_date')
            ->get();
        $billablePayments = $this->billableStudentPayments($payments);
        $activePayments = $this->activeStudentPayments($billablePayments);

        return view('student-portal.payments', [
            'account' => $account,
            'payments' => $payments,
            'billablePayments' => $billablePayments,
            'activePayments' => $activePayments,
            'activePaymentTotal' => $activePayments->sum('amount'),
            'billingMonthLabel' => Carbon::now()->translatedFormat('F Y'),
        ]);
    }

    public function paymentReceipt(Request $request): Response|RedirectResponse
    {
        $account = $this->currentAccount($request);

        if (! $account) {
            return redirect()->route('student-portal.login');
        }

        $lead = $account->lead->fresh(['campus', 'studyProgram', 'classTrack', 'studentBiodata', 'studentNumber', 'latestInvoice', 'studentPayments' => fn ($query) => $query->orderBy('paid_at')->orderBy('month')]);
        $paidPayments = $this->billableStudentPayments($lead->studentPayments)
            ->filter(fn ($payment): bool => in_array($payment->status, ['paid', 'waived'], true))
            ->values();

        abort_if($paidPayments->isEmpty(), 404);

        return ReceiptPdfRenderer::render('admin.student-payment-receipt', [
            'lead' => $lead,
            'paidPayments' => $paidPayments,
            'receiptNumber' => 'KWT-'.now()->format('Ymd').'-'.str_pad((string) $lead->id, 5, '0', STR_PAD_LEFT),
            'totalPaid' => (int) $paidPayments->sum('amount'),
            'receiptUrl' => route('admin.student-payments.receipt', $lead),
        ], 'kwitansi-'.$lead->id.'.pdf');
    }
    public function uploadPaymentProof(Request $request): RedirectResponse
    {
        $account = $this->currentAccount($request);

        if (! $account) {
            return redirect()->route('student-portal.login');
        }

        $data = $request->validate([
            'student_payment_id' => ['required', 'integer'],
            'proof_path' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ], [
            'student_payment_id.required' => 'Pilih tagihan yang akan dibayar.',
            'proof_path.required' => 'Bukti pembayaran wajib diupload.',
            'proof_path.mimes' => 'Bukti pembayaran harus berupa JPG, PNG, atau PDF.',
            'proof_path.max' => 'Ukuran bukti pembayaran maksimal 5 MB.',
        ]);

        $payment = $account->lead->studentPayments()
            ->whereKey($data['student_payment_id'])
            ->firstOrFail();

        $billableIds = $this->billableStudentPayments(
            $account->lead->studentPayments()->orderBy('month')->get()
        )->pluck('id');

        abort_unless($billableIds->contains($payment->id), 403);

        if (in_array($payment->status, ['paid', 'waived'], true)) {
            return back()->with('status', 'Tagihan ini sudah lunas.');
        }

        $path = $request->file('proof_path')->store('student-payment-proofs/'.$account->lead_id, 'public');
        $manualPayment = $this->pendingManualPaymentFor($account->lead_id, $payment);

        if ($manualPayment?->proof_path) {
            Storage::disk('public')->delete($manualPayment->proof_path);
        }

        $manualPayload = [
            'lead_id' => $account->lead_id,
            'fee_scheme_id' => $payment->fee_scheme_id,
            'invoice_id' => $payment->invoice_id,
            'month' => $manualPayment?->month ?? ManualStudentPaymentResource::nextManualMonthFor($account->lead_id),
            'payment_label' => $payment->payment_label ?: ((int) $payment->month === 0 ? 'Formulir Pendaftaran' : 'Bulan '.$payment->month),
            'registration_fee' => (int) $payment->registration_fee,
            'development_fee' => (int) $payment->development_fee,
            'tuition_fee' => (int) $payment->tuition_fee,
            'ukt' => (int) $payment->ukt,
            'amount' => (int) $payment->amount,
            'due_date' => $payment->due_date,
            'status' => 'pending',
            'payment_method' => 'transfer_rekening_cv',
            'proof_path' => $path,
            'submitted_at' => now(),
            'payment_type' => 'manual',
            'verification_status' => 'pending',
            'verification_notes' => null,
            'source_row_json' => array_merge($payment->source_row_json ?: [], [
                'type' => 'student_payment_proof_upload',
                'rr_student_payment_id' => $payment->id,
                'rr_month' => $payment->month,
                'rr_payment_label' => $payment->payment_label ?: ((int) $payment->month === 0 ? 'Formulir Pendaftaran' : 'Bulan '.$payment->month),
            ]),
        ];

        if ($manualPayment) {
            $manualPayment->update($manualPayload);
        } else {
            $manualPayment = StudentPayment::query()->create($manualPayload);
        }

        $payment->update([
            'status' => 'pending',
            'payment_method' => 'transfer_rekening_cv',
            'proof_path' => $path,
            'submitted_at' => now(),
            'verification_status' => 'pending',
            'verification_notes' => null,
        ]);

        $this->notifyAdminsPaymentProofSubmitted($manualPayment->refresh(), $payment->refresh());

        return back()->with('status', 'Bukti pembayaran berhasil dikirim. Tim keuangan akan memvalidasi pembayaran kamu.');
    }

    /**
     * Same flow as uploadPaymentProof(), but for the public thank-you page: no student
     * portal login required, scoped directly to the lead's registration-fee payment
     * (month 0) via the route-bound Lead instead of the session-based student account.
     */
    public function uploadPublicPaymentProof(Request $request, Lead $lead): RedirectResponse
    {
        $data = $request->validate([
            'proof_path' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ], [
            'proof_path.required' => 'Bukti pembayaran wajib diupload.',
            'proof_path.mimes' => 'Bukti pembayaran harus berupa JPG, PNG, atau PDF.',
            'proof_path.max' => 'Ukuran bukti pembayaran maksimal 5 MB.',
        ]);

        $payment = $lead->studentPayments()
            ->where('month', 0)
            ->where('payment_type', '!=', 'manual')
            ->first();

        if (! $payment) {
            return back()->withErrors(['proof_path' => 'Tagihan pendaftaran tidak ditemukan untuk pendaftaran ini.']);
        }

        if (in_array($payment->status, ['paid', 'waived'], true)) {
            return back()->with('status', 'Tagihan pendaftaran ini sudah lunas.');
        }

        $path = $data['proof_path']->store('student-payment-proofs/'.$lead->id, 'public');
        $manualPayment = $this->pendingManualPaymentFor($lead->id, $payment);

        if ($manualPayment?->proof_path) {
            Storage::disk('public')->delete($manualPayment->proof_path);
        }

        $manualPayload = [
            'lead_id' => $lead->id,
            'fee_scheme_id' => $payment->fee_scheme_id,
            'invoice_id' => $payment->invoice_id,
            'month' => $manualPayment?->month ?? ManualStudentPaymentResource::nextManualMonthFor($lead->id),
            'payment_label' => $payment->payment_label ?: 'Formulir Pendaftaran',
            'registration_fee' => (int) $payment->registration_fee,
            'development_fee' => (int) $payment->development_fee,
            'tuition_fee' => (int) $payment->tuition_fee,
            'ukt' => (int) $payment->ukt,
            'amount' => (int) $payment->amount,
            'due_date' => $payment->due_date,
            'status' => 'pending',
            'payment_method' => 'transfer_rekening_cv',
            'proof_path' => $path,
            'submitted_at' => now(),
            'payment_type' => 'manual',
            'verification_status' => 'pending',
            'verification_notes' => null,
            'source_row_json' => array_merge($payment->source_row_json ?: [], [
                'type' => 'student_payment_proof_upload',
                'rr_student_payment_id' => $payment->id,
                'rr_month' => $payment->month,
                'rr_payment_label' => $payment->payment_label ?: 'Formulir Pendaftaran',
            ]),
        ];

        if ($manualPayment) {
            $manualPayment->update($manualPayload);
        } else {
            $manualPayment = StudentPayment::query()->create($manualPayload);
        }

        $payment->update([
            'status' => 'pending',
            'payment_method' => 'transfer_rekening_cv',
            'proof_path' => $path,
            'submitted_at' => now(),
            'verification_status' => 'pending',
            'verification_notes' => null,
        ]);

        $this->notifyAdminsPaymentProofSubmitted($manualPayment->refresh(), $payment->refresh());

        return redirect()->away($this->adminWhatsappConfirmationUrl($lead));
    }

    /**
     * wa.me link to the PMB admin number (same number used across the public site's
     * consultation CTAs) with a default message telling the admin the student has paid.
     */
    private function adminWhatsappConfirmationUrl(Lead $lead): string
    {
        $adminNumber = '6282199976600';
        $message = "Halo Admin, saya {$lead->full_name} sudah melakukan pembayaran pendaftaran dan upload bukti transfer di sistem. Mohon dicek dan diproses ya. Terima kasih.";

        return 'https://wa.me/'.$adminNumber.'?text='.rawurlencode($message);
    }

    public function affiliateDashboard(Request $request): View|RedirectResponse
    {
        $account = $this->currentAccount($request);

        if (! $account) {
            $request->session()->put('student_portal.intended_url', $request->fullUrl());

            return redirect()->route('student-portal.login');
        }

        $partner = ReferralPartner::query()
            ->with(['conversions.lead.campus', 'conversions.lead.studyProgram'])
            ->firstOrCreate(
                ['student_account_id' => $account->id, 'type' => 'mahasiswa'],
                [
                    'name' => $account->lead->full_name,
                    'referral_code' => $this->uniqueStudentReferralCode($account),
                    'commission_amount' => 0,
                    'status' => 'active',
                    'notes' => 'Dibuat otomatis dari dashboard akun mahasiswa.',
                ],
            );

        $partner->load(['conversions.lead.campus', 'conversions.lead.studyProgram']);
        $conversions = $partner->conversions->sortByDesc('created_at')->values();

        return view('student-portal.affiliate', [
            'account' => $account,
            'partner' => $partner,
            'conversions' => $conversions,
            'referralLink' => rtrim(config('spmm.public_url', 'https://kampus.media'), '/').'/daftar?ref='.$partner->referral_code,
            'registeredCount' => $conversions->count(),
            'paidCount' => $conversions->whereNotNull('herregistration_paid_at')->count(),
            'approvedCommission' => $conversions->sum(fn ($conversion): int =>
                (in_array($conversion->herregistration_commission_status, ['approved'], true) ? (int) $conversion->herregistration_commission_amount : 0)
                + (in_array($conversion->semester1_commission_status, ['approved'], true) ? (int) $conversion->semester1_commission_amount : 0)
            ),
            'paidCommission' => $conversions->sum(fn ($conversion): int =>
                ($conversion->herregistration_commission_status === 'paid' ? (int) $conversion->herregistration_commission_amount : 0)
                + ($conversion->semester1_commission_status === 'paid' ? (int) $conversion->semester1_commission_amount : 0)
            ),
            'pendingCommission' => $conversions->sum(fn ($conversion): int =>
                (in_array($conversion->herregistration_commission_status, ['pending', 'approved'], true) ? (int) $conversion->herregistration_commission_amount : 0)
                + (in_array($conversion->semester1_commission_status, ['pending', 'approved'], true) ? (int) $conversion->semester1_commission_amount : 0)
            ),
        ]);
    }

    public function documents(Request $request): View|RedirectResponse
    {
        $account = $this->currentAccount($request);

        if (! $account) {
            return redirect()->route('student-portal.login');
        }

        $documents = $account->lead->studentDocuments()
            ->get()
            ->keyBy('document_type');

        return view('student-portal.documents', [
            'account' => $account,
            'documentTypes' => $this->documentTypes(),
            'documents' => $documents,
        ]);
    }

    public function uploadDocuments(Request $request): RedirectResponse
    {
        $account = $this->currentAccount($request);

        if (! $account) {
            return redirect()->route('student-portal.login');
        }

        $rules = [];
        $existingDocuments = $account->lead->studentDocuments()
            ->pluck('id', 'document_type');

        foreach ($this->documentTypes() as $key => $document) {
            $rules[$key] = [
                $document['required'] && ! $existingDocuments->has($key) ? 'required' : 'nullable',
                'file',
                'mimes:jpg,jpeg,png,pdf',
                'max:4096',
            ];
        }

        $request->validate($rules);

        foreach ($this->documentTypes() as $key => $document) {
            if (! $request->hasFile($key)) {
                continue;
            }

            $existing = $account->lead->studentDocuments()
                ->where('document_type', $key)
                ->first();

            if ($existing) {
                Storage::disk('public')->delete($existing->file_path);
            }

            $file = $request->file($key);
            $path = $file->store('student-documents/'.$account->lead_id, 'public');

            StudentDocument::query()->updateOrCreate(
                [
                    'lead_id' => $account->lead_id,
                    'document_type' => $key,
                ],
                [
                    'file_path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getClientMimeType(),
                    'file_size' => $file->getSize(),
                    'status' => 'uploaded',
                ],
            );
        }

        return redirect()->route('student-portal.documents')->with('status', 'Dokumen pemberkasan berhasil disimpan.');
    }

    public function learningMaterials(Request $request): View|RedirectResponse
    {
        $account = $this->currentAccount($request);

        if (! $account) {
            return redirect()->route('student-portal.login');
        }

        $courseClassIds = $this->enrolledCourseClassIds($account);
        $modules = $courseClassIds->isEmpty()
            ? collect()
            : LmsModule::query()
                ->with(['courseClass.course', 'courseClass.academicTerm', 'materials' => fn ($query) => $query->where('status', 'published')->orderBy('sort_order')])
                ->whereIn('course_class_id', $courseClassIds)
                ->where('status', 'published')
                ->orderBy('sort_order')
                ->get();

        return view('student-portal.learning-materials', compact('account', 'modules'));
    }

    public function assignments(Request $request): View|RedirectResponse
    {
        $account = $this->currentAccount($request);

        if (! $account) {
            return redirect()->route('student-portal.login');
        }

        $courseClassIds = $this->enrolledCourseClassIds($account);
        $assignments = $courseClassIds->isEmpty()
            ? collect()
            : LmsAssignment::query()
                ->with([
                    'module.courseClass.course',
                    'module.courseClass.academicTerm',
                    'submissions' => fn ($query) => $query->where('lead_id', $account->lead_id),
                ])
                ->whereHas('module', fn ($query) => $query->whereIn('course_class_id', $courseClassIds))
                ->where('status', 'published')
                ->orderBy('due_at')
                ->get();

        return view('student-portal.assignments', compact('account', 'assignments'));
    }

    public function submitAssignment(Request $request, LmsAssignment $assignment): RedirectResponse
    {
        $account = $this->currentAccount($request);

        if (! $account) {
            return redirect()->route('student-portal.login');
        }

        $courseClassIds = $this->enrolledCourseClassIds($account);
        $assignment->load('module');

        abort_if(! $courseClassIds->contains($assignment->module?->course_class_id), 403);

        $data = $request->validate([
            'answer_text' => ['nullable', 'string'],
            'file_path' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png,zip', 'max:8192'],
        ]);

        $submission = LmsSubmission::query()->firstOrNew([
            'lms_assignment_id' => $assignment->id,
            'lead_id' => $account->lead_id,
        ]);

        if ($request->hasFile('file_path')) {
            if ($submission->file_path) {
                Storage::disk('public')->delete($submission->file_path);
            }

            $data['file_path'] = $request->file('file_path')->store('lms/submissions/'.$account->lead_id, 'public');
        }

        $submission->fill([
            'answer_text' => $data['answer_text'] ?? $submission->answer_text,
            'file_path' => $data['file_path'] ?? $submission->file_path,
            'submitted_at' => now(),
            'status' => $assignment->due_at && now()->greaterThan($assignment->due_at) ? 'late' : 'submitted',
        ])->save();

        return redirect()->route('student-portal.assignments')->with('status', 'Tugas berhasil dikumpulkan.');
    }

    private function currentAccount(Request $request): ?StudentAccount
    {
        $accountId = $request->session()->get('student_account_id');

        if (! $accountId) {
            return null;
        }

        return StudentAccount::query()
            ->with(['lead.campus', 'lead.studyProgram', 'lead.latestInvoice'])
            ->with('lead.studentBiodata')
            ->with(['lead.classTrack', 'lead.studentNumber', 'lead.studentPayments' => fn ($query) => $query->orderBy('due_date')->orderBy('month')])
            ->find($accountId);
    }

    private function enrolledCourseClassIds(StudentAccount $account)
    {
        return StudyPlanItem::query()
            ->whereHas('studyPlan', fn ($query) => $query
                ->where('lead_id', $account->lead_id)
                ->whereIn('status', ['draft', 'submitted', 'approved']))
            ->where('status', 'taken')
            ->pluck('course_class_id')
            ->unique()
            ->values();
    }

    private function documentTypes(): array
    {
        return [
            'ktp' => ['label' => 'KTP', 'required' => true, 'icon' => 'id-card'],
            'kk' => ['label' => 'KK', 'required' => true, 'icon' => 'users'],
            'ijazah' => ['label' => 'Ijazah', 'required' => true, 'icon' => 'graduation-cap'],
            'transkrip_skhu' => ['label' => 'Transkrip/SKHU', 'required' => true, 'icon' => 'file-text'],
            'pass_foto_formal' => ['label' => 'Pass Foto Formal', 'required' => true, 'icon' => 'image'],
            'dokumen_pendukung' => ['label' => 'Dokumen Pendukung Lainnya', 'required' => false, 'icon' => 'paperclip'],
        ];
    }

    private function selectionNumberFor(StudentAccount $account): string
    {
        return 'SEL-'.now()->format('Ymd').'-'.str_pad((string) $account->lead_id, 5, '0', STR_PAD_LEFT);
    }

    private function uniqueStudentReferralCode(StudentAccount $account): string
    {
        $base = 'MHS-'.str_pad((string) $account->id, 5, '0', STR_PAD_LEFT);

        if (! ReferralPartner::query()->where('referral_code', $base)->exists()) {
            return $base;
        }

        do {
            $code = $base.'-'.Str::upper(Str::random(4));
        } while (ReferralPartner::query()->where('referral_code', $code)->exists());

        return $code;
    }

    private function pendingManualPaymentFor(int $leadId, StudentPayment $rrPayment): ?StudentPayment
    {
        return StudentPayment::query()
            ->where('lead_id', $leadId)
            ->where('payment_type', 'manual')
            ->whereIn('verification_status', ['pending', 'unverified'])
            ->latest('id')
            ->get()
            ->first(fn (StudentPayment $payment): bool => (int) data_get($payment->source_row_json, 'rr_student_payment_id') === (int) $rrPayment->id);
    }

    private function notifyAdminsPaymentProofSubmitted(StudentPayment $manualPayment, StudentPayment $rrPayment): void
    {
        $manualPayment->loadMissing(['lead.campus', 'lead.studyProgram']);
        $lead = $manualPayment->lead;

        if (! $lead) {
            return;
        }

        $recipients = User::query()
            ->where('status', UserStatus::Active)
            ->where(function ($query) use ($lead): void {
                $query
                    ->whereIn('role', [UserRole::SuperAdmin, UserRole::Direktur])
                    ->orWhere(function ($roleQuery) use ($lead): void {
                        $roleQuery
                            ->whereIn('role', [UserRole::KoordinatorPmb, UserRole::StaffPmb])
                            ->whereHas('campuses', fn ($campusQuery) => $campusQuery->whereKey($lead->campus_id));
                    });
            })
            ->get();

        if ($recipients->isEmpty()) {
            return;
        }

        $verificationUrl = $this->adminUrl(
            ManualStudentPaymentResource::getUrl('edit', ['record' => $manualPayment])
        );

        Notification::make()
            ->title('Bukti pembayaran mahasiswa masuk')
            ->body($lead->full_name.' mengirim bukti pembayaran '.($rrPayment->payment_label ?: 'tagihan').' sebesar Rp '.number_format((int) $rrPayment->amount, 0, ',', '.').'. Mohon verifikasi di Pembayaran Manual.')
            ->icon('heroicon-o-banknotes')
            ->warning()
            ->actions([
                NotificationAction::make('open')
                    ->label('Buka Verifikasi')
                    ->button()
                    ->url($verificationUrl),
            ])
            ->sendToDatabase($recipients);
    }

    private function adminUrl(string $path): string
    {
        $adminUrl = rtrim(config('spmm.admin_url', 'https://spmm.maheramedia.com/admin'), '/');
        $path = parse_url($path, PHP_URL_PATH) ?: $path;

        if (str_starts_with($path, '/admin/')) {
            $path = substr($path, 6);
        } elseif ($path === '/admin') {
            $path = '';
        }

        return $adminUrl.'/'.ltrim($path, '/');
    }

    private function resolvePasswordResetAccount(string $token): StudentAccount
    {
        $account = StudentAccount::query()
            ->where('password_reset_token', $token)
            ->whereNotNull('password_reset_sent_at')
            ->firstOrFail();

        if ($account->password_reset_sent_at->lt(now()->subMinutes(60))) {
            abort(403, 'Link reset password sudah kedaluwarsa. Silakan minta link baru.');
        }

        return $account;
    }

    private function sendStudentPasswordResetEmail(StudentAccount $account): void
    {
        $resetUrl = route('student-portal.password.reset', $account->password_reset_token);
        $studentName = $account->lead?->full_name ?: 'Mahasiswa';

        $body = "Halo {$studentName},\n\n".
            "Kami menerima permintaan reset password akun mahasiswa Kampus Media.\n\n".
            "Klik link berikut untuk membuat password baru:\n{$resetUrl}\n\n".
            "Link ini berlaku selama 60 menit. Abaikan email ini jika kamu tidak meminta reset password.\n\n".
            "Salam,\nKampus Media";

        try {
            Mail::raw($body, function ($message) use ($account, $studentName): void {
                $message
                    ->to($account->email, $studentName)
                    ->subject('Reset Password Akun Mahasiswa Kampus Media');
            });
        } catch (Throwable $exception) {
            Log::warning('Student password reset email failed.', [
                'student_account_id' => $account->id,
                'email' => $account->email,
                'message' => $exception->getMessage(),
            ]);
        }

        if (app()->environment('local')) {
            Storage::disk('local')->put("local-emails/student-password-reset-{$account->id}.txt", $body);
        }
    }

    private function feeSchemeFor(StudentAccount $account, ?int $studyProgramId = null, ?int $classTrackId = null): ?FeeScheme
    {
        $lead = $account->lead;

        return FeeScheme::query()
            ->where('campus_id', $lead->campus_id)
            ->where(function ($query) use ($lead, $studyProgramId): void {
                $query->whereNull('study_program_id')
                    ->orWhere('study_program_id', $studyProgramId ?? $lead->study_program_id);
            })
            ->where(function ($query) use ($lead, $classTrackId): void {
                $query->whereNull('class_track_id')
                    ->orWhere('class_track_id', $classTrackId ?? $lead->class_track_id);
            })
            ->where('is_active', true)
            ->orderByRaw('study_program_id is null')
            ->orderByRaw('class_track_id is null')
            ->first();
    }

    private function billableStudentPayments($payments)
    {
        $payments = $payments
            ->filter(fn ($payment): bool => $payment->payment_type !== 'manual')
            ->values();

        $registrationPayment = $payments->firstWhere('month', 0);
        $registrationPaid = $registrationPayment && in_array($registrationPayment->status, ['paid', 'waived'], true);

        if ($registrationPaid) {
            return $payments;
        }

        return $payments->filter(fn ($payment): bool => (int) $payment->month === 0)->values();
    }

    private function activeStudentPayments($payments)
    {
        $today = now()->endOfDay();

        return $payments
            ->filter(fn ($payment): bool => $payment->due_date && $payment->due_date->lte($today))
            ->filter(fn ($payment): bool => ! in_array($payment->status, ['paid', 'waived'], true))
            ->sortBy('due_date')
            ->values();
    }
}


