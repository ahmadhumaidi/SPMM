<?php

namespace App\Imports;

use App\Models\Campus;
use App\Models\ClassTrack;
use App\Models\Lead;
use App\Models\StudyProgram;
use Closure;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class LeadsImport implements SkipsOnFailure, ToModel, WithHeadingRow, WithValidation
{
    use SkipsFailures;

    /** @var array<int, Lead> */
    public array $createdLeads = [];

    private ?Validator $currentValidator = null;

    /**
     * @param  array<int, int>|null  $campusIds  Null means unrestricted (super admin / direktur).
     */
    public function __construct(private ?array $campusIds = null) {}

    public function withValidator(Validator $validator): void
    {
        $this->currentValidator = $validator;
    }

    public function model(array $row): ?Lead
    {
        $campus = $this->resolveCampus($row['kampus'] ?? null);
        $studyProgram = $campus ? $this->resolveStudyProgram($campus->id, $row['program_studi'] ?? null) : null;
        $classTrack = $campus ? $this->resolveClassTrack($campus->id, $row['program_perkuliahan'] ?? null) : null;

        if (! $campus || ! $studyProgram || ! $classTrack) {
            return null;
        }

        $lead = new Lead([
            'campus_id' => $campus->id,
            'study_program_id' => $studyProgram->id,
            'class_track_id' => $classTrack->id,
            'full_name' => trim((string) $row['nama']),
            'whatsapp_number' => trim((string) $row['no_whatsapp']),
            'email' => filled($row['email'] ?? null) ? trim((string) $row['email']) : null,
            'origin_school' => filled($row['asal_sekolah'] ?? null) ? trim((string) $row['asal_sekolah']) : null,
            'graduation_year' => filled($row['tahun_lulus'] ?? null) ? (int) $row['tahun_lulus'] : null,
            'lead_status' => 'in_pool',
            'payment_status' => 'pending',
            'enrollment_status' => 'calon_mahasiswa',
            'source_channel' => 'manual',
            'source_detail' => 'Import manual (upload Excel)',
        ]);

        $this->createdLeads[] = $lead;

        return $lead;
    }

    public function rules(): array
    {
        return [
            'kampus' => ['required', function (string $attribute, mixed $value, Closure $fail): void {
                if (! $this->resolveCampus($value)) {
                    $fail('Kampus tidak ditemukan atau tidak diizinkan untuk akun ini.');
                }
            }],
            'nama' => ['required', 'max:255'],
            'no_whatsapp' => ['required', 'max:32'],
            'email' => ['nullable', 'email', 'max:255'],
            'program_studi' => ['required', function (string $attribute, mixed $value, Closure $fail): void {
                $campus = $this->resolveCampus($this->siblingValue($attribute, 'kampus'));

                if ($campus && ! $this->resolveStudyProgram($campus->id, $value)) {
                    $fail('Program studi tidak ditemukan di kampus tersebut.');
                }
            }],
            'program_perkuliahan' => ['required', function (string $attribute, mixed $value, Closure $fail): void {
                $campus = $this->resolveCampus($this->siblingValue($attribute, 'kampus'));

                if ($campus && ! $this->resolveClassTrack($campus->id, $value)) {
                    $fail('Program perkuliahan tidak ditemukan di kampus tersebut.');
                }
            }],
            'tahun_lulus' => ['nullable', 'integer', 'digits:4'],
        ];
    }

    private function siblingValue(string $attribute, string $field): ?string
    {
        $rowIndex = strtok($attribute, '.');

        return $this->currentValidator?->getData()[$rowIndex][$field] ?? null;
    }

    private function resolveCampus(mixed $name): ?Campus
    {
        if (blank($name)) {
            return null;
        }

        return Campus::query()
            ->when($this->campusIds !== null, fn ($query) => $query->whereIn('id', $this->campusIds))
            ->whereRaw('LOWER(name) = ?', [Str::lower(trim((string) $name))])
            ->first();
    }

    private function resolveStudyProgram(int $campusId, mixed $name): ?StudyProgram
    {
        if (blank($name)) {
            return null;
        }

        return StudyProgram::query()
            ->where('campus_id', $campusId)
            ->whereRaw('LOWER(name) = ?', [Str::lower(trim((string) $name))])
            ->first();
    }

    private function resolveClassTrack(int $campusId, mixed $name): ?ClassTrack
    {
        if (blank($name)) {
            return null;
        }

        return ClassTrack::query()
            ->where('campus_id', $campusId)
            ->whereRaw('LOWER(name) = ?', [Str::lower(trim((string) $name))])
            ->first();
    }
}
