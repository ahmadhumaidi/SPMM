<?php

use App\Http\Controllers\PublicRegistrationController;
use App\Http\Controllers\MockPaymentController;
use App\Http\Controllers\SeparateSystemPortalController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\StudentPortalController;
use App\Http\Controllers\StudentExportController;
use App\Models\Campus;
use App\Support\ReceiptPdfRenderer;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;

Route::bind('campus', fn (string $value): Campus => Campus::query()
    ->where('name', $value)
    ->orWhere('slug', $value)
    ->firstOrFail());

Route::domain('affiliate.kampus.media')->group(function (): void {
    Route::get('/', [PublicRegistrationController::class, 'affiliate'])->name('affiliate.home.short');
    Route::get('/affiliate', fn () => redirect('/'));
    Route::get('/login', [PublicRegistrationController::class, 'affiliateLogin'])->name('affiliate.login.short');
    Route::post('/login', [PublicRegistrationController::class, 'authenticateAffiliate'])->name('affiliate.authenticate.short');
    Route::get('/lupa-password', [PublicRegistrationController::class, 'forgotAffiliatePassword'])->name('affiliate.password.request.short');
    Route::post('/lupa-password', [PublicRegistrationController::class, 'sendAffiliatePasswordReset'])->name('affiliate.password.email.short');
    Route::get('/reset-password/{token}', [PublicRegistrationController::class, 'resetAffiliatePassword'])->name('affiliate.password.reset.short');
    Route::post('/reset-password/{token}', [PublicRegistrationController::class, 'updateAffiliatePassword'])->name('affiliate.password.update.short');
    Route::get('/dashboard', [PublicRegistrationController::class, 'affiliateDashboardHome'])->name('affiliate.dashboard.home.short');
    Route::get('/dashboard/{token}', [PublicRegistrationController::class, 'affiliateDashboard'])->name('affiliate.dashboard.short');
    Route::post('/logout', [PublicRegistrationController::class, 'logoutAffiliate'])->name('affiliate.logout.short');
    Route::get('/daftar', [PublicRegistrationController::class, 'createAffiliate'])->name('affiliate.register.short');
    Route::post('/daftar', [PublicRegistrationController::class, 'storeAffiliate'])->name('affiliate.store.short');
});

Route::redirect('/login', '/admin/login')->name('login');

if ($adminHost = parse_url(config('spmm.admin_url', ''), PHP_URL_HOST)) {
    // Kampus Media's public portal has migrated to its own domain; this app's
    // own admin domain should no longer serve it and must go straight to login.
    Route::domain($adminHost)->get('/', fn () => redirect('/admin/login'))->name('home.admin-domain');
}

Route::get('/', [PublicRegistrationController::class, 'index'])->name('home');
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');
Route::get('/kampus', [PublicRegistrationController::class, 'index'])->name('campuses.index');
Route::get('/kampus/{campus}/berita', [PublicRegistrationController::class, 'campusNewsIndex'])->name('campuses.news.index');
Route::get('/kampus/{campus}', [PublicRegistrationController::class, 'showCampus'])->name('campuses.show');
Route::get('/berita', [PublicRegistrationController::class, 'newsIndex'])->name('news.index');
Route::get('/berita/{news:slug}', [PublicRegistrationController::class, 'showNews'])->name('news.show');
Route::get('/affiliate', [PublicRegistrationController::class, 'affiliate'])->name('affiliate.public');
Route::get('/affiliate/daftar', [PublicRegistrationController::class, 'createAffiliate'])->name('affiliate.register');
Route::post('/affiliate/daftar', [PublicRegistrationController::class, 'storeAffiliate'])->name('affiliate.store');
Route::get('/affiliate/dashboard/{token}', [PublicRegistrationController::class, 'affiliateDashboard'])->name('affiliate.dashboard');
Route::get('/affiliate/verifikasi/{token}', [PublicRegistrationController::class, 'verifyAffiliate'])->name('affiliate.verify');
Route::get('/daftar', [PublicRegistrationController::class, 'create'])->name('registration.create');
Route::post('/daftar', [PublicRegistrationController::class, 'store'])->name('registration.store');
Route::get('/thank-you/{lead}', [PublicRegistrationController::class, 'thankYou'])->name('registration.thank-you');
Route::post('/thank-you/{lead}/bukti-pembayaran', [StudentPortalController::class, 'uploadPublicPaymentProof'])->name('registration.payment-proof.upload');
Route::get('/local-email/{lead}', [PublicRegistrationController::class, 'localVerificationEmail'])->name('registration.local-email');
Route::get('/pemberkasan/{token}', [PublicRegistrationController::class, 'showPemberkasan'])->name('student-profile.show');
Route::post('/pemberkasan/{token}', [PublicRegistrationController::class, 'storePemberkasan'])->name('student-profile.store');
Route::get('/mock-payment/{reference}/pay', [MockPaymentController::class, 'pay'])->name('mock-payment.pay');
Route::get('/exports/mahasiswa-aktif.csv', [StudentExportController::class, 'activeCsv'])->middleware('auth')->name('exports.active-students.csv');
Route::get('/admin/student-payments/{lead}/receipt', function (\App\Models\Lead $lead) {
    $lead->loadMissing(['campus', 'studyProgram', 'classTrack', 'studentBiodata', 'studentNumber', 'latestInvoice', 'studentPayments' => fn ($query) => $query->orderBy('paid_at')->orderBy('month')]);

    abort_unless(auth()->check() && \App\Support\FilamentResourceScope::canAccessCampus($lead->campus_id), 403);

    $paidPayments = $lead->studentPayments
        ->filter(fn ($payment) => $payment->payment_type !== 'manual')
        ->filter(fn ($payment) => in_array($payment->status, ['paid', 'waived'], true))
        ->values();

    abort_if($paidPayments->isEmpty(), 404);

    return ReceiptPdfRenderer::render('admin.student-payment-receipt', [
        'lead' => $lead,
        'paidPayments' => $paidPayments,
        'receiptNumber' => 'KWT-'.now()->format('Ymd').'-'.str_pad((string) $lead->id, 5, '0', STR_PAD_LEFT),
        'totalPaid' => (int) $paidPayments->sum('amount'),
    ], 'kwitansi-'.$lead->id.'.pdf');
})->middleware('auth')->name('admin.student-payments.receipt');

Route::get('/admin/student-payments/transaction/{payment}/receipt', function (\App\Models\StudentPayment $payment, \Illuminate\Http\Request $request, \App\Services\StudentPaymentReceiptArchiver $archiver) {
    abort_unless(auth()->check() && \App\Support\FilamentResourceScope::canAccessCampus($payment->lead?->campus_id), 403);
    abort_unless(in_array($payment->status, ['paid', 'waived'], true), 404);

    $filename = 'kwitansi-'.$payment->lead_id.'-'.$payment->id.'.pdf';
    $disposition = $request->boolean('download') ? 'attachment' : 'inline';

    return response($archiver->contentsFor($payment), 200, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => $disposition.'; filename="'.$filename.'"',
        'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
    ]);
})->middleware('auth')->name('admin.student-payments.transaction-receipt');

Route::get('/admin/whatsapp-broadcasts/{broadcast}/manual-runner', function (\App\Models\WhatsappBroadcast $broadcast) {
    abort_unless(auth()->check(), 403);
    abort_unless($broadcast->created_by_user_id === auth()->id() || \App\Support\FilamentResourceScope::canAccessCampus($broadcast->campus_id), 403);

    $recipients = $broadcast->recipients()
        ->with(['lead.latestInvoice', 'lead.studyProgram'])
        ->where('status', 'queued')
        ->orderBy('id')
        ->get()
        ->map(fn (\App\Models\WhatsappBroadcastRecipient $recipient): array => [
            'id' => $recipient->id,
            'name' => $recipient->lead?->full_name ?? $recipient->recipient_name ?? 'Tanpa nama',
            'phone' => $recipient->recipient_number,
            'url' => $broadcast->whatsappWebUrlForRecipient($recipient),
        ])
        ->values();

    return view('admin.whatsapp-broadcast-runner', [
        'broadcast' => $broadcast,
        'recipients' => $recipients,
    ]);
})->middleware('auth')->name('admin.whatsapp-broadcasts.manual-runner');

Route::get('/admin/whatsapp-broadcasts/python-helper/download', function () {
    return response()->download(base_path('tools/whatsapp_web_auto_sender.py'), 'whatsapp_web_auto_sender.py', [
        'Content-Type' => 'text/x-python; charset=UTF-8',
    ]);
})->name('admin.whatsapp-broadcasts.python-helper');
Route::get('/admin/whatsapp-broadcasts/python-runner/install', function () {
    abort_unless(auth()->check(), 403);

    return response()->download(base_path('tools/install_spmm_whatsapp_runner.bat'), 'install_spmm_whatsapp_runner.bat', [
        'Content-Type' => 'application/bat',
    ]);
})->middleware('auth')->name('admin.whatsapp-broadcasts.python-runner.install');
Route::get('/admin/whatsapp-broadcasts/chrome-extension/download', function () {
    abort_unless(auth()->check(), 403);

    return response()->download(base_path('tools/spmm-wa-sender-extension.zip'), 'spmm-wa-sender-extension.zip', [
        'Content-Type' => 'application/zip',
    ]);
})->middleware('auth')->name('admin.whatsapp-broadcasts.extension.download');

Route::get('/admin/whatsapp-broadcasts/{broadcast}/python-queue', function (\App\Models\WhatsappBroadcast $broadcast) {
    abort_unless(auth()->check(), 403);
    abort_unless($broadcast->created_by_user_id === auth()->id() || \App\Support\FilamentResourceScope::canAccessCampus($broadcast->campus_id), 403);

    $expiresAt = now()->addHours(12);

    $recipients = $broadcast->recipients()
        ->with(['lead.latestInvoice', 'lead.studyProgram'])
        ->where('status', 'queued')
        ->orderBy('id')
        ->get()
        ->map(function (\App\Models\WhatsappBroadcastRecipient $recipient) use ($broadcast, $expiresAt): array {
            $phone = \App\Support\PhoneNumber::normalizeWhatsapp((string) $recipient->recipient_number, config('spmm.whatsapp.default_country_code'));
            $message = $broadcast->renderMessageForRecipient($recipient);

            return [
                'id' => $recipient->id,
                'name' => $recipient->lead?->full_name ?? $recipient->recipient_name ?? 'Tanpa nama',
                'phone' => $phone,
                'message' => $message,
                'web_url' => 'https://web.whatsapp.com/send?phone='.$phone.'&text='.rawurlencode($message),
                'mark_sent_url' => URL::temporarySignedRoute('admin.whatsapp-broadcasts.recipients.python-sent', $expiresAt, [$broadcast, $recipient]),
                'mark_invalid_url' => URL::temporarySignedRoute('admin.whatsapp-broadcasts.recipients.python-invalid', $expiresAt, [$broadcast, $recipient]),
            ];
        })
        ->values();

    $filename = 'whatsapp-broadcast-'.$broadcast->id.'-queue.json';

    return response()->json([
        'broadcast_id' => $broadcast->id,
        'broadcast_name' => $broadcast->name,
        'interval_seconds' => max(30, (int) ($broadcast->interval_seconds ?: 45)),
        'load_seconds' => 14,
        'expires_at' => $expiresAt->toIso8601String(),
        'recipients' => $recipients,
    ], 200, [
        'Content-Disposition' => 'attachment; filename="'.$filename.'"',
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
})->middleware('auth')->name('admin.whatsapp-broadcasts.python-queue');
Route::get('/admin/whatsapp-broadcasts/{broadcast}/python-queue-signed', function (\App\Models\WhatsappBroadcast $broadcast) {
    $expiresAt = now()->addHours(12);

    $recipients = $broadcast->recipients()
        ->with(['lead.latestInvoice', 'lead.studyProgram'])
        ->where('status', 'queued')
        ->orderBy('id')
        ->get()
        ->map(function (\App\Models\WhatsappBroadcastRecipient $recipient) use ($broadcast, $expiresAt): array {
            $phone = \App\Support\PhoneNumber::normalizeWhatsapp((string) $recipient->recipient_number, config('spmm.whatsapp.default_country_code'));
            $message = $broadcast->renderMessageForRecipient($recipient);

            return [
                'id' => $recipient->id,
                'name' => $recipient->lead?->full_name ?? $recipient->recipient_name ?? 'Tanpa nama',
                'phone' => $phone,
                'message' => $message,
                'web_url' => 'https://web.whatsapp.com/send?phone='.$phone.'&text='.rawurlencode($message),
                'mark_sent_url' => URL::temporarySignedRoute('admin.whatsapp-broadcasts.recipients.python-sent', $expiresAt, [$broadcast, $recipient]),
                'mark_invalid_url' => URL::temporarySignedRoute('admin.whatsapp-broadcasts.recipients.python-invalid', $expiresAt, [$broadcast, $recipient]),
            ];
        })
        ->values();

    return response()->json([
        'broadcast_id' => $broadcast->id,
        'broadcast_name' => $broadcast->name,
        'interval_seconds' => max(30, (int) ($broadcast->interval_seconds ?: 45)),
        'load_seconds' => 16,
        'expires_at' => $expiresAt->toIso8601String(),
        'recipients' => $recipients,
    ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
})->middleware('signed')->name('admin.whatsapp-broadcasts.python-queue.signed');

Route::post('/admin/whatsapp-broadcasts/{broadcast}/recipients/{recipient}/python-sent', function (\App\Models\WhatsappBroadcast $broadcast, \App\Models\WhatsappBroadcastRecipient $recipient) {
    abort_unless($recipient->whatsapp_broadcast_id === $broadcast->id, 404);

    if ($recipient->status !== 'sent') {
        $recipient->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $broadcast->increment('sent_count');
    }

    if ($broadcast->recipients()->where('status', 'queued')->doesntExist()) {
        $broadcast->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    return response()->json(['ok' => true]);
})->middleware('signed')->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class)->name('admin.whatsapp-broadcasts.recipients.python-sent');

Route::post('/admin/whatsapp-broadcasts/{broadcast}/recipients/{recipient}/python-invalid', function (\App\Models\WhatsappBroadcast $broadcast, \App\Models\WhatsappBroadcastRecipient $recipient) {
    abort_unless($recipient->whatsapp_broadcast_id === $broadcast->id, 404);

    if ($recipient->status !== 'invalid') {
        $recipient->update([
            'status' => 'invalid',
            'failed_reason' => 'Nomor invalid dari Python runner',
        ]);

        $broadcast->increment('failed_count');
    }

    if ($broadcast->recipients()->where('status', 'queued')->doesntExist()) {
        $broadcast->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    return response()->json(['ok' => true]);
})->middleware('signed')->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class)->name('admin.whatsapp-broadcasts.recipients.python-invalid');

Route::post('/admin/whatsapp-broadcasts/{broadcast}/recipients/{recipient}/sent', function (\App\Models\WhatsappBroadcast $broadcast, \App\Models\WhatsappBroadcastRecipient $recipient) {
    abort_unless(auth()->check(), 403);
    abort_unless($recipient->whatsapp_broadcast_id === $broadcast->id, 404);
    abort_unless($broadcast->created_by_user_id === auth()->id() || \App\Support\FilamentResourceScope::canAccessCampus($broadcast->campus_id), 403);

    if ($recipient->status !== 'sent') {
        $recipient->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $broadcast->increment('sent_count');
    }

    if ($broadcast->recipients()->where('status', 'queued')->doesntExist()) {
        $broadcast->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    return response()->json(['ok' => true]);
})->middleware('auth')->name('admin.whatsapp-broadcasts.recipients.sent');

Route::post('/admin/whatsapp-broadcasts/{broadcast}/recipients/{recipient}/invalid', function (\App\Models\WhatsappBroadcast $broadcast, \App\Models\WhatsappBroadcastRecipient $recipient) {
    abort_unless(auth()->check(), 403);
    abort_unless($recipient->whatsapp_broadcast_id === $broadcast->id, 404);
    abort_unless($broadcast->created_by_user_id === auth()->id() || \App\Support\FilamentResourceScope::canAccessCampus($broadcast->campus_id), 403);

    if ($recipient->status !== 'invalid') {
        $recipient->update([
            'status' => 'invalid',
            'failed_reason' => 'Nomor invalid',
        ]);

        $broadcast->increment('failed_count');
    }

    if ($broadcast->recipients()->where('status', 'queued')->doesntExist()) {
        $broadcast->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    return response()->json(['ok' => true]);
})->middleware('auth')->name('admin.whatsapp-broadcasts.recipients.invalid');

Route::get('/admin/whatsapp-broadcasts/{broadcast}/report', function (\App\Models\WhatsappBroadcast $broadcast) {
    abort_unless(auth()->check(), 403);
    abort_unless($broadcast->created_by_user_id === auth()->id() || \App\Support\FilamentResourceScope::canAccessCampus($broadcast->campus_id), 403);

    $recipients = $broadcast->recipients()
        ->with(['lead.studyProgram'])
        ->orderBy('id')
        ->get();

    return view('admin.whatsapp-broadcast-report', [
        'broadcast' => $broadcast,
        'recipients' => $recipients,
        'queuedCount' => $recipients->where('status', 'queued')->count(),
        'sentCount' => $recipients->where('status', 'sent')->count(),
        'invalidCount' => $recipients->where('status', 'invalid')->count(),
        'failedCount' => $recipients->where('status', 'failed')->count(),
    ]);
})->middleware('auth')->name('admin.whatsapp-broadcasts.report');

Route::get('/mahasiswa/login', [StudentPortalController::class, 'login'])->name('student-portal.login');
Route::post('/mahasiswa/login', [StudentPortalController::class, 'authenticate'])->name('student-portal.authenticate');
Route::get('/mahasiswa/lupa-password', [StudentPortalController::class, 'forgotPassword'])->name('student-portal.password.request');
Route::post('/mahasiswa/lupa-password', [StudentPortalController::class, 'sendPasswordReset'])->name('student-portal.password.email');
Route::get('/mahasiswa/reset-password/{token}', [StudentPortalController::class, 'resetPassword'])->name('student-portal.password.reset');
Route::post('/mahasiswa/reset-password/{token}', [StudentPortalController::class, 'updateResetPassword'])->name('student-portal.password.reset.update');
Route::get('/mahasiswa/verifikasi/{token}', [StudentPortalController::class, 'verify'])->name('student-portal.verify');
Route::get('/mahasiswa/dashboard', [StudentPortalController::class, 'dashboard'])->name('student-portal.dashboard');
Route::get('/mahasiswa/profil', [StudentPortalController::class, 'profile'])->name('student-portal.profile');
Route::post('/mahasiswa/profil', [StudentPortalController::class, 'updateProfile'])->name('student-portal.profile.update');
Route::post('/mahasiswa/password', [StudentPortalController::class, 'updatePassword'])->name('student-portal.password.update');
Route::get('/mahasiswa/pemberkasan', [StudentPortalController::class, 'documents'])->name('student-portal.documents');
Route::post('/mahasiswa/pemberkasan', [StudentPortalController::class, 'uploadDocuments'])->name('student-portal.documents.upload');
Route::get('/mahasiswa/pembayaran', [StudentPortalController::class, 'payments'])->name('student-portal.payments');
Route::post('/mahasiswa/pembayaran/bukti', [StudentPortalController::class, 'uploadPaymentProof'])->name('student-portal.payments.proof');
Route::get('/mahasiswa/pembayaran/kwitansi', [StudentPortalController::class, 'paymentReceipt'])->name('student-portal.payments.receipt');
Route::get('/mahasiswa/affiliate', [StudentPortalController::class, 'affiliateDashboard'])->name('student-portal.affiliate');
Route::get('/mahasiswa/affiliator', [StudentPortalController::class, 'affiliateDashboard']);
Route::get('/mahasiswa/materi-kuliah', [StudentPortalController::class, 'learningMaterials'])->name('student-portal.materials');
Route::get('/mahasiswa/tugas', [StudentPortalController::class, 'assignments'])->name('student-portal.assignments');
Route::post('/mahasiswa/tugas/{assignment}', [StudentPortalController::class, 'submitAssignment'])->name('student-portal.assignments.submit');
Route::get('/mahasiswa/pengaturan-akun', [StudentPortalController::class, 'accountSettings'])->name('student-portal.account-settings');
Route::post('/mahasiswa/logout', [StudentPortalController::class, 'logout'])->name('student-portal.logout');

Route::get('/lms/login', [SeparateSystemPortalController::class, 'login'])->defaults('system', 'lms')->name('lms.login');
Route::post('/lms/login', [SeparateSystemPortalController::class, 'authenticate'])->defaults('system', 'lms')->name('lms.authenticate');
Route::get('/lms/dashboard', [SeparateSystemPortalController::class, 'dashboard'])->defaults('system', 'lms')->middleware('auth')->name('lms.dashboard');

Route::post('/system-portal/logout', [SeparateSystemPortalController::class, 'logout'])->middleware('auth')->name('separate-systems.logout');



