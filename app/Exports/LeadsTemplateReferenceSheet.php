<?php

namespace App\Exports;

use App\Models\Campus;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;

class LeadsTemplateReferenceSheet implements FromArray, WithTitle
{
    /**
     * @param  array<int, int>|null  $campusIds  Null means unrestricted (super admin / direktur).
     */
    public function __construct(private ?array $campusIds = null) {}

    public function title(): string
    {
        return 'Daftar Kampus & Program';
    }

    public function array(): array
    {
        $campuses = Campus::query()
            ->when($this->campusIds !== null, fn ($query) => $query->whereIn('id', $this->campusIds))
            ->with(['studyPrograms' => fn ($query) => $query->orderBy('name'), 'classTracks' => fn ($query) => $query->orderBy('name')])
            ->orderBy('name')
            ->get();

        $rows = [['Nama Kampus (valid)', 'Program Studi Tersedia', 'Program Perkuliahan Tersedia']];

        foreach ($campuses as $campus) {
            $rows[] = [
                $campus->name,
                $campus->studyPrograms->pluck('name')->implode('; '),
                $campus->classTracks->pluck('name')->implode('; '),
            ];
        }

        return $rows;
    }
}
