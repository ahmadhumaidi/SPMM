<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum AcademicStatus: string implements HasColor, HasLabel
{
    case Aktif = 'aktif';
    case Cuti = 'cuti';
    case NonAktif = 'non_aktif';
    case Lulus = 'lulus';
    case MengundurkanDiri = 'mengundurkan_diri';
    case PutusStudi = 'putus_studi';
    case MeninggalDunia = 'meninggal_dunia';
    case TransferKeluar = 'transfer_keluar';
    case TransferMasuk = 'transfer_masuk';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Aktif => 'Aktif',
            self::Cuti => 'Cuti',
            self::NonAktif => 'Non Aktif',
            self::Lulus => 'Lulus',
            self::MengundurkanDiri => 'Mengundurkan Diri',
            self::PutusStudi => 'Putus Studi (DO)',
            self::MeninggalDunia => 'Meninggal Dunia',
            self::TransferKeluar => 'Transfer Keluar',
            self::TransferMasuk => 'Transfer Masuk',
        };
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::Aktif => 'success',
            self::Cuti => 'warning',
            self::NonAktif => 'gray',
            self::Lulus => 'info',
            self::MengundurkanDiri => 'danger',
            self::PutusStudi => 'danger',
            self::MeninggalDunia => 'gray',
            self::TransferKeluar => 'warning',
            self::TransferMasuk => 'info',
        };
    }
}
