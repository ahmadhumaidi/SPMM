<?php

namespace App\Filament\Resources\EducationNewsResource\Pages;

use App\Filament\Resources\EducationNewsResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEducationNews extends CreateRecord
{
    protected static string $resource = EducationNewsResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
