<?php

namespace App\Filament\Resources\LmsModuleResource\Pages;

use App\Filament\Resources\LmsModuleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLmsModule extends EditRecord
{
    protected static string $resource = LmsModuleResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
