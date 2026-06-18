<?php

namespace App\Services;

use App\Enums\EnrollmentStatus;
use App\Models\Lead;
use App\Models\StudentNumber;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class StudentNumberService
{
    public function __construct(private readonly AuditLogger $audit)
    {
    }

    public function issue(Lead $lead, string $nim, User $issuedBy): StudentNumber
    {
        if ($lead->enrollment_status !== EnrollmentStatus::ProsesPemutakhiran) {
            throw new DomainException('NIM can only be issued during proses_pemutakhiran.');
        }

        return DB::transaction(function () use ($lead, $nim, $issuedBy): StudentNumber {
            $studentNumber = StudentNumber::create([
                'lead_id' => $lead->id,
                'nim' => $nim,
                'issued_by_user_id' => $issuedBy->id,
                'issued_at' => now(),
            ]);

            $lead->update([
                'enrollment_status' => EnrollmentStatus::MahasiswaAktif,
                'locked_at' => $lead->locked_at ?? now(),
            ]);

            $this->audit->record('nim_issued', $studentNumber, ['lead_id' => $lead->id], $issuedBy);
            $this->audit->record('enrollment_status_changed', $lead, ['to' => EnrollmentStatus::MahasiswaAktif->value], $issuedBy);

            return $studentNumber;
        });
    }
}
