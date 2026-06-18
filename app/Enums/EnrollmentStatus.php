<?php

namespace App\Enums;

enum EnrollmentStatus: string
{
    case CalonMahasiswa = 'calon_mahasiswa';
    case MenungguPemberkasan = 'menunggu_pemberkasan';
    case MenungguPemutakhiran = 'menunggu_pemutakhiran';
    case ProsesPemutakhiran = 'proses_pemutakhiran';
    case MahasiswaAktif = 'mahasiswa_aktif';
}
