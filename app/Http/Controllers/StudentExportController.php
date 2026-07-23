<?php

namespace App\Http\Controllers;

use App\Enums\EnrollmentStatus;
use App\Enums\UserRole;
use App\Models\Lead;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentExportController extends Controller
{
    public function activeCsv(): StreamedResponse
    {
        abort_unless(
            auth()->check() && in_array(auth()->user()->role, [UserRole::SuperAdmin, UserRole::Direktur, UserRole::KoordinatorPmb], true),
            403
        );

        $filename = 'mahasiswa-aktif-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function (): void {
            $output = fopen('php://output', 'w');

            fputcsv($output, [
                'nim',
                'nama',
                'kampus',
                'program_studi',
                'jalur_kelas',
                'nik',
                'nisn',
                'tempat_lahir',
                'tanggal_lahir',
                'jenis_kelamin',
                'nama_ibu',
                'telepon',
                'email',
            ]);

            Lead::query()
                ->where('enrollment_status', EnrollmentStatus::MahasiswaAktif)
                ->with(['campus', 'studyProgram', 'classTrack', 'studentProfile', 'studentNumber'])
                ->orderBy('id')
                ->chunk(200, function ($leads) use ($output): void {
                    foreach ($leads as $lead) {
                        fputcsv($output, [
                            $lead->studentNumber?->nim,
                            $lead->full_name,
                            $lead->campus?->name,
                            $lead->studyProgram?->name,
                            $lead->classTrack?->name,
                            $lead->studentProfile?->nik,
                            $lead->studentProfile?->nisn,
                            $lead->studentProfile?->birth_place,
                            $lead->studentProfile?->birth_date?->format('Y-m-d'),
                            $lead->studentProfile?->gender,
                            $lead->studentProfile?->mother_name,
                            $lead->studentProfile?->phone,
                            $lead->studentProfile?->email,
                        ]);
                    }
                });

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
