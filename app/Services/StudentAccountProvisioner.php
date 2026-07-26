<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\StudentAccount;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

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

        $html = view('admin.student-welcome-email', [
            'lead' => $lead,
            'verificationUrl' => $verificationUrl,
            'loginUrl' => $loginUrl,
            'temporaryPassword' => $temporaryPassword,
        ])->render();

        try {
            Mail::html(
                $html,
                function ($message) use ($lead): void {
                    $message
                        ->to($lead->email, $lead->full_name)
                        ->subject('Terima kasih sudah mendaftar di Kampus Media');
                },
            );
        } catch (Throwable $exception) {
            Log::warning('Student welcome email failed.', [
                'lead_id' => $lead->id,
                'email' => $lead->email,
                'message' => $exception->getMessage(),
            ]);
        }

        if (app()->environment('local')) {
            Storage::disk('local')->put("local-emails/lead-{$lead->id}.html", $html);
        }
    }
}
