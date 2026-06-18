<?php

namespace App\Filament\Pages;

use App\Support\FilamentResourceScope;
use Filament\Pages\Page;

class NewStudentBiodata extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationIcon = 'heroicon-o-user-plus';

    protected static ?string $navigationGroup = 'Mahasiswa Baru';

    protected static ?string $navigationLabel = 'Biodata';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.student-menu-placeholder';

    public string $moduleTitle = 'Biodata Mahasiswa Baru';

    public string $moduleDescription = 'Halaman untuk melihat dan mengelola biodata calon mahasiswa atau mahasiswa baru.';

    public static function canAccess(): bool
    {
        return FilamentResourceScope::canAccessStudentRecords();
    }
}
