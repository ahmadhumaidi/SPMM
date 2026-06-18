<?php

namespace App\Filament\Pages;

use App\Support\FilamentResourceScope;
use Filament\Pages\Page;

class OldStudentSklRecap extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-check-badge';

    protected static ?string $navigationGroup = 'Mahasiswa Lama';

    protected static ?string $navigationLabel = 'Rekap SKL (Lunas)';

    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.student-menu-placeholder';

    public string $moduleTitle = 'Rekap SKL (Lunas) Mahasiswa Lama';

    public string $moduleDescription = 'Halaman untuk rekap mahasiswa lama yang sudah lunas dan siap masuk daftar SKL.';

    public static function canAccess(): bool
    {
        return FilamentResourceScope::canAccessStudentRecords();
    }
}
