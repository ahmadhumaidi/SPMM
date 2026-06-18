<?php

namespace App\Filament\Resources\EducationNewsResource\Pages;

use App\Filament\Resources\EducationNewsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEducationNews extends EditRecord
{
    protected static string $resource = EducationNewsResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
