<?php

namespace App\Support;

use Closure;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\View\View;

class FilamentBackButton
{
    /**
     * Renders a "Kembali" button back to the resource's list page, on every
     * Create/Edit/View page across all resources in a panel. Registered as an
     * unscoped panel render hook, so it must inspect the current page's own
     * scopes (passed in by Filament) rather than being registered per-resource.
     */
    public static function renderHook(): Closure
    {
        return function (array $scopes): ?View {
            $pageClass = $scopes[0] ?? null;

            if (! is_string($pageClass) || ! class_exists($pageClass)) {
                return null;
            }

            $isRecordFormPage = is_subclass_of($pageClass, CreateRecord::class)
                || is_subclass_of($pageClass, EditRecord::class)
                || is_subclass_of($pageClass, ViewRecord::class);

            if (! $isRecordFormPage) {
                return null;
            }

            $resourceClass = $scopes[1] ?? null;

            if (! is_string($resourceClass) || ! method_exists($resourceClass, 'getUrl')) {
                return null;
            }

            return view('filament.back-button', ['url' => $resourceClass::getUrl('index')]);
        };
    }
}
