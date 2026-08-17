<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class LeadsTemplateExport implements WithMultipleSheets
{
    /**
     * @param  array<int, int>|null  $campusIds  Null means unrestricted (super admin / direktur).
     */
    public function __construct(private ?array $campusIds = null) {}

    public function sheets(): array
    {
        return [
            'Template' => new LeadsTemplateSheet,
            'Daftar Kampus & Program' => new LeadsTemplateReferenceSheet($this->campusIds),
        ];
    }
}
