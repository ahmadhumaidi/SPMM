<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;

class LeadsTemplateSheet implements FromArray, WithTitle
{
    public function title(): string
    {
        return 'Template';
    }

    public function array(): array
    {
        return [
            ['Kampus', 'Nama', 'No WhatsApp', 'Email', 'Program Studi', 'Program Perkuliahan', 'Asal Sekolah', 'Tahun Lulus'],
            ['Contoh: lihat sheet "Daftar Kampus & Program"', 'Contoh: Budi Santoso', '6281234567890', 'budi@email.com', 'Contoh: Manajemen', 'Contoh: Karyawan', 'SMA Negeri 1', 2024],
        ];
    }
}
