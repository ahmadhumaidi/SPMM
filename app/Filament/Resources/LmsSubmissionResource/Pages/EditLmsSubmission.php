<?php

namespace App\Filament\Resources\LmsSubmissionResource\Pages;

use App\Filament\Resources\LmsSubmissionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLmsSubmission extends EditRecord
{
    protected static string $resource = LmsSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
