<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\StudentAccount;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StudentAccountProvisioner
{
    public function createForLead(Lead $lead): StudentAccount
    {
        $temporaryPassword = Str::password(10, letters: true, numbers: true, symbols: false);
        $verificationToken = Str::random(48);

        $account = StudentAccount::query()->updateOrCreate(
            ['lead_id' => $lead->id],
            [
                'email' => $lead->email,
                'password' => Hash::make($temporaryPassword),
                'verification_token' => $verificationToken,
                'email_verified_at' => null,
            ],
        );

        $this->sendWelcomeEmail($lead, $account, $temporaryPassword);

        return $account;
    }

    private function sendWelcomeEmail(Lead $lead, StudentAccount $account, string $temporaryPassword): void
    {
        if (blank($lead->email)) {
            return;
        }

        $verificationUrl = route('student-portal.verify', $account->verification_token);
        $loginUrl = route('student-portal.login');

        $body = "Halo {$lead->full_name},\n\n".
            "Terima kasih sudah mendaftar di Kampus Nusantara.\n\n".
            "Silakan verifikasi email kamu melalui link berikut:\n{$verificationUrl}\n\n".
            "Akses login mahasiswa:\n".
            "URL: {$loginUrl}\n".
            "Email: {$lead->email}\n".
            "Password sementara: {$temporaryPassword}\n\n".
            "Setelah login, mahasiswa dapat melengkapi biodata, upload berkas, melihat pembayaran, dan mencetak kwitansi pembayaran.\n\n".
            "Salam,\nKampus Nusantara";

        Mail::raw(
            $body,
            function ($message) use ($lead): void {
                $message
                    ->to($lead->email, $lead->full_name)
                    ->subject('Terima kasih sudah mendaftar di Kampus Nusantara');
            },
        );

        if (app()->environment('local')) {
            Storage::disk('local')->put("local-emails/lead-{$lead->id}.txt", $body);
        }
    }
}
