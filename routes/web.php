<?php

use App\Http\Controllers\PublicRegistrationController;
use App\Http\Controllers\MockPaymentController;
use App\Http\Controllers\SeparateSystemPortalController;
use App\Http\Controllers\StudentPortalController;
use App\Http\Controllers\StudentExportController;
use App\Models\Campus;
use Illuminate\Support\Facades\Route;

Route::bind('campus', fn (string $value): Campus => Campus::query()
    ->where('name', $value)
    ->orWhere('slug', $value)
    ->firstOrFail());

Route::get('/', [PublicRegistrationController::class, 'index'])->name('home');
Route::get('/kampus', [PublicRegistrationController::class, 'index'])->name('campuses.index');
Route::get('/kampus/{campus}/berita', [PublicRegistrationController::class, 'campusNewsIndex'])->name('campuses.news.index');
Route::get('/kampus/{campus}', [PublicRegistrationController::class, 'showCampus'])->name('campuses.show');
Route::get('/berita', [PublicRegistrationController::class, 'newsIndex'])->name('news.index');
Route::get('/berita/{news:slug}', [PublicRegistrationController::class, 'showNews'])->name('news.show');
Route::get('/daftar', [PublicRegistrationController::class, 'create'])->name('registration.create');
Route::post('/daftar', [PublicRegistrationController::class, 'store'])->name('registration.store');
Route::get('/thank-you/{lead}', [PublicRegistrationController::class, 'thankYou'])->name('registration.thank-you');
Route::get('/local-email/{lead}', [PublicRegistrationController::class, 'localVerificationEmail'])->name('registration.local-email');
Route::get('/pemberkasan/{token}', [PublicRegistrationController::class, 'showPemberkasan'])->name('student-profile.show');
Route::post('/pemberkasan/{token}', [PublicRegistrationController::class, 'storePemberkasan'])->name('student-profile.store');
Route::get('/mock-payment/{reference}/pay', [MockPaymentController::class, 'pay'])->name('mock-payment.pay');
Route::get('/exports/mahasiswa-aktif.csv', [StudentExportController::class, 'activeCsv'])->middleware('auth')->name('exports.active-students.csv');

Route::get('/mahasiswa/login', [StudentPortalController::class, 'login'])->name('student-portal.login');
Route::post('/mahasiswa/login', [StudentPortalController::class, 'authenticate'])->name('student-portal.authenticate');
Route::get('/mahasiswa/verifikasi/{token}', [StudentPortalController::class, 'verify'])->name('student-portal.verify');
Route::get('/mahasiswa/dashboard', [StudentPortalController::class, 'dashboard'])->name('student-portal.dashboard');
Route::get('/mahasiswa/profil', [StudentPortalController::class, 'profile'])->name('student-portal.profile');
Route::post('/mahasiswa/profil', [StudentPortalController::class, 'updateProfile'])->name('student-portal.profile.update');
Route::post('/mahasiswa/password', [StudentPortalController::class, 'updatePassword'])->name('student-portal.password.update');
Route::get('/mahasiswa/pemberkasan', [StudentPortalController::class, 'documents'])->name('student-portal.documents');
Route::post('/mahasiswa/pemberkasan', [StudentPortalController::class, 'uploadDocuments'])->name('student-portal.documents.upload');
Route::get('/mahasiswa/pembayaran', [StudentPortalController::class, 'payments'])->name('student-portal.payments');
Route::get('/mahasiswa/affiliate', [StudentPortalController::class, 'affiliateDashboard'])->name('student-portal.affiliate');
Route::get('/mahasiswa/affiliator', [StudentPortalController::class, 'affiliateDashboard']);
Route::get('/mahasiswa/materi-kuliah', [StudentPortalController::class, 'learningMaterials'])->name('student-portal.materials');
Route::get('/mahasiswa/tugas', [StudentPortalController::class, 'assignments'])->name('student-portal.assignments');
Route::post('/mahasiswa/tugas/{assignment}', [StudentPortalController::class, 'submitAssignment'])->name('student-portal.assignments.submit');
Route::get('/mahasiswa/pengaturan-akun', [StudentPortalController::class, 'accountSettings'])->name('student-portal.account-settings');
Route::post('/mahasiswa/logout', [StudentPortalController::class, 'logout'])->name('student-portal.logout');

Route::get('/siakad/login', [SeparateSystemPortalController::class, 'login'])->defaults('system', 'siakad')->name('siakad.login');
Route::post('/siakad/login', [SeparateSystemPortalController::class, 'authenticate'])->defaults('system', 'siakad')->name('siakad.authenticate');
Route::get('/siakad/dashboard', [SeparateSystemPortalController::class, 'dashboard'])->defaults('system', 'siakad')->middleware('auth')->name('siakad.dashboard');

Route::get('/lms/login', [SeparateSystemPortalController::class, 'login'])->defaults('system', 'lms')->name('lms.login');
Route::post('/lms/login', [SeparateSystemPortalController::class, 'authenticate'])->defaults('system', 'lms')->name('lms.authenticate');
Route::get('/lms/dashboard', [SeparateSystemPortalController::class, 'dashboard'])->defaults('system', 'lms')->middleware('auth')->name('lms.dashboard');

Route::post('/system-portal/logout', [SeparateSystemPortalController::class, 'logout'])->middleware('auth')->name('separate-systems.logout');
