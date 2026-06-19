<?php

namespace App\Http\Controllers;

use App\Models\StudentAccount;
use App\Models\ClassTrack;
use App\Models\FeeScheme;
use App\Models\LmsAssignment;
use App\Models\LmsModule;
use App\Models\LmsSubmission;
use App\Models\ReferralPartner;
use App\Models\StudyPlanItem;
use App\Models\StudyProgram;
use App\Models\StudentDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

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

    public function dashboard(Request $request): View|RedirectResponse
    {
        $account = $this->currentAccount($request);

        if (! $account) {
            $request->session()->put('student_portal.intended_url', $request->fullUrl());

            return redirect()->route('student-portal.login');
        }

        $currentMonthPayments = $account->lead->studentPayments
            ->filter(fn ($payment): bool => $payment->due_date?->isSameMonth(now()) ?? false)
            ->filter(fn ($payment): bool => ! in_array($payment->status, ['paid', 'waived'], true))
            ->sortBy('due_date')
            ->values();

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
        $activePayments = $payments
            ->filter(fn ($payment): bool => $payment->due_date?->isSameMonth(now()) ?? false)
            ->filter(fn ($payment): bool => ! in_array($payment->status, ['paid', 'waived'], true))
            ->sortBy('due_date')
            ->values();

        return view('student-portal.payments', [
            'account' => $account,
            'payments' => $payments,
            'activePayments' => $activePayments,
            'activePaymentTotal' => $activePayments->sum('amount'),
            'billingMonthLabel' => Carbon::now()->translatedFormat('F Y'),
        ]);
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
            'referralLink' => rtrim(config('spmm.public_url', 'https://kampusmedia.cloud'), '/').'/daftar?ref='.$partner->referral_code,
            'registeredCount' => $conversions->count(),
            'paidCount' => $conversions->whereNotNull('paid_at')->count(),
            'approvedCommission' => $conversions->where('commission_status', 'approved')->sum('commission_amount'),
            'paidCommission' => $conversions->where('commission_status', 'paid')->sum('commission_amount'),
            'pendingCommission' => $conversions->whereIn('commission_status', ['pending', 'approved'])->sum('commission_amount'),
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
}
