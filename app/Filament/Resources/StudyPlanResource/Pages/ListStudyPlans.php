<?php

namespace App\Filament\Resources\StudyPlanResource\Pages;

use App\Filament\Resources\StudyPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStudyPlans extends ListRecords
{
    protected static string $resource = StudyPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
