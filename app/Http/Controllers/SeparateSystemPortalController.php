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
        abort_unless($system === 'lms', 404);

        return view('separate-systems.login', [
            'system' => 'lms',
            'title' => 'LMS Kampus',
            'subtitle' => 'Learning Management System untuk materi, tugas, dan aktivitas belajar.',
        ]);
    }

    public function authenticate(string $system, Request $request): RedirectResponse
    {
        abort_unless($system === 'lms', 404);

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
        abort_unless($system === 'lms', 404);

        return view('separate-systems.dashboard', [
            'system' => 'lms',
            'title' => 'LMS Kampus',
            'subtitle' => 'Kelola pembelajaran digital kampus dari pintu khusus LMS.',
            'cards' => [
                ['label' => 'Modul Pembelajaran', 'description' => 'Kelola topik/modul per kelas.', 'url' => $this->adminUrl('lms-modules')],
                ['label' => 'Materi Kuliah', 'description' => 'Upload file, link, video, dan catatan materi.', 'url' => $this->adminUrl('lms-materials')],
                ['label' => 'Tugas Kuliah', 'description' => 'Buat tugas dan deadline pengumpulan.', 'url' => $this->adminUrl('lms-assignments')],
                ['label' => 'Pengumpulan Tugas', 'description' => 'Pantau submission, nilai, dan feedback.', 'url' => $this->adminUrl('lms-submissions')],
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

    /**
     * The public kampus.media domain blocks /admin at the nginx level (only the
     * spmm.maheramedia.com vhost serves the Filament panel), so links from the LMS
     * portal into resource pages must use the absolute admin URL, not a relative /admin/... path.
     */
    private function adminUrl(string $path): string
    {
        return rtrim(config('spmm.admin_url'), '/').'/'.ltrim($path, '/');
    }
}
