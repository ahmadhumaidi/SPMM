<?php

namespace App\Filament\Resources\LmsSubmissionResource\Pages;

use App\Filament\Resources\LmsSubmissionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLmsSubmissions extends ListRecords
{
    protected static string $resource = LmsSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
