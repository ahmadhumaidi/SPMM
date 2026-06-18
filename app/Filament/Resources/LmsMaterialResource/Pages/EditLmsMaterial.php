<?php

namespace App\Filament\Resources\LmsMaterialResource\Pages;

use App\Filament\Resources\LmsMaterialResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLmsMaterial extends EditRecord
{
    protected static string $resource = LmsMaterialResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
