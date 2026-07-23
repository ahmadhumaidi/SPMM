<?php

namespace App\Http\Controllers;

use App\Enums\EnrollmentStatus;
use App\Enums\PaymentStatus;
use App\Http\Requests\CompleteStudentProfileRequest;
use App\Http\Requests\RegisterLeadRequest;
use App\Models\Campus;
use App\Models\EducationNews;
use App\Models\Lead;
use App\Services\LeadRegistrationService;
use App\Services\TenantResolver;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PublicRegistrationController extends Controller
{
    public function index(Request $request, TenantResolver $tenants): JsonResponse|View
    {
        $tenant = $tenants->resolve($request);

        if ($tenant !== null) {
            $tenant->load([
                'studyPrograms' => fn ($query) => $query->where('status', 'active'),
                'classTracks' => fn ($query) => $query->where('status', 'active'),
                'feeSchemes' => fn ($query) => $query->where('is_active', true),
            ]);

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
        $referralCode = $request->query('ref') ?: $request->session()->get('referral_code');

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
            ->get();

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

    public function campusNewsIndex(Campus|string $campus): View
    {
        $campus = $this->resolveCampusRouteValue($campus);

        abort_unless($campus->status->value === 'active', 404);

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
        $result = $registration->register($request->validated());

        if (! $request->wantsJson()) {
            return redirect()->route('registration.thank-you', $result['lead']);
        }

        return response()->json([
            'lead_id' => $result['lead']->id,
            'invoice_number' => $result['invoice']->invoice_number,
            'amount' => $result['invoice']->amount,
            'payment_url' => $result['invoice']->payment_url,
            'expires_at' => $result['invoice']->expires_at,
        ], 201);
    }

    public function thankYou(Request $request, Lead $lead): JsonResponse|View
    {
        $lead->load('latestInvoice');

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

        $path = "local-emails/lead-{$lead->id}.txt";

        abort_unless(Storage::disk('local')->exists($path), 404);

        return response(Storage::disk('local')->get($path), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
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
            ->where('pemberkasan_token', $token)
            ->where('payment_status', PaymentStatus::Paid)
            ->firstOrFail();
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
