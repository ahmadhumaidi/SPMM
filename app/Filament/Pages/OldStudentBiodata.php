<?php

namespace App\Filament\Pages;

use App\Support\FilamentResourceScope;
use Filament\Pages\Page;

class OldStudentBiodata extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationGroup = 'Mahasiswa Lama';

    protected static ?string $navigationLabel = 'Biodata';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.student-menu-placeholder';

    public string $moduleTitle = 'Biodata Mahasiswa Lama';

    public string $moduleDescription = 'Halaman untuk melihat dan mengelola data biodata mahasiswa lama.';

    public static function canAccess(): bool
    {
        return FilamentResourceScope::canAccessStudentRecords();
    }
}
