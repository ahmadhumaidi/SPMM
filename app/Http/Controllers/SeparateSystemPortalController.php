<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SeparateSystemPortalController extends Controller
{
    public function login(string $system): View
    {
        abort_unless(in_array($system, ['siakad', 'lms'], true), 404);

        return view('separate-systems.login', [
            'system' => $system,
            'title' => $system === 'siakad' ? 'SIAKAD Kampus' : 'LMS Kampus',
            'subtitle' => $system === 'siakad'
                ? 'Sistem Informasi Akademik untuk jadwal, KRS, nilai, dan transkrip.'
                : 'Learning Management System untuk materi, tugas, dan aktivitas belajar.',
        ]);
    }

    public function authenticate(string $system, Request $request): RedirectResponse
    {
        abort_unless(in_array($system, ['siakad', 'lms'], true), 404);

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'Email atau password tidak sesuai.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->route("{$system}.dashboard");
    }

    public function dashboard(string $system): View
    {
        abort_unless(in_array($system, ['siakad', 'lms'], true), 404);

        $isSiakad = $system === 'siakad';

        return view('separate-systems.dashboard', [
            'system' => $system,
            'title' => $isSiakad ? 'SIAKAD Kampus' : 'LMS Kampus',
            'subtitle' => $isSiakad
                ? 'Kelola operasional akademik kampus dari pintu khusus SIAKAD.'
                : 'Kelola pembelajaran digital kampus dari pintu khusus LMS.',
            'cards' => $isSiakad ? [
                ['label' => 'Tahun Akademik', 'description' => 'Kelola periode akademik aktif.', 'url' => '/admin/academic-terms'],
                ['label' => 'Mata Kuliah', 'description' => 'Kelola kode, SKS, dan prodi mata kuliah.', 'url' => '/admin/courses'],
                ['label' => 'Kelas & Jadwal', 'description' => 'Kelola kelas, dosen, ruang, dan jadwal.', 'url' => '/admin/course-classes'],
                ['label' => 'KRS & Nilai', 'description' => 'Kelola KRS, KHS, dan nilai mahasiswa.', 'url' => '/admin/study-plans'],
            ] : [
                ['label' => 'Modul Pembelajaran', 'description' => 'Kelola topik/modul per kelas.', 'url' => '/admin/lms-modules'],
                ['label' => 'Materi Kuliah', 'description' => 'Upload file, link, video, dan catatan materi.', 'url' => '/admin/lms-materials'],
                ['label' => 'Tugas Kuliah', 'description' => 'Buat tugas dan deadline pengumpulan.', 'url' => '/admin/lms-assignments'],
                ['label' => 'Pengumpulan Tugas', 'description' => 'Pantau submission, nilai, dan feedback.', 'url' => '/admin/lms-submissions'],
            ],
        ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
