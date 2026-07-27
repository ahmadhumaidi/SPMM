<?php

namespace App\Providers\Filament;

use App\Filament\Resources\AcademicTermResource;
use App\Filament\Resources\CourseClassResource;
use App\Filament\Resources\CourseResource;
use App\Filament\Resources\LecturerResource;
use App\Filament\Resources\SiakadStudentResource;
use App\Filament\Resources\StudyPlanResource;
use App\Filament\Widgets\SiakadOverviewWidget;
use App\Filament\Widgets\SiakadPendingKrsWidget;
use App\Filament\Widgets\SiakadScheduleWidget;
use App\Support\FilamentBackButton;
use App\Support\SiakadCampusResolver;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class SiakadPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('siakad')
            ->path('siakad')
            ->login()
            ->brandName(fn (): string => 'SIAKAD '.(SiakadCampusResolver::current()?->name ?? 'Kampus'))
            ->brandLogo(function (): ?HtmlString {
                $campus = SiakadCampusResolver::current();

                if (! $campus?->logo_path) {
                    return null;
                }

                return new HtmlString('<img src="'.Storage::url($campus->logo_path).'" class="h-8" alt="Logo '.e($campus->name).'">');
            })
            ->colors([
                'primary' => Color::Red,
                'gray' => Color::Slate,
            ])
            ->font('Inter')
            ->renderHook(
                'panels::head.end',
                fn (): HtmlString => new HtmlString('<link rel="stylesheet" href="/css/filament-siakad.css?v=1">'),
            )
            ->renderHook(
                PanelsRenderHook::PAGE_HEADER_ACTIONS_BEFORE,
                FilamentBackButton::renderHook(),
            )
            ->resources([
                SiakadStudentResource::class,
                AcademicTermResource::class,
                CourseResource::class,
                CourseClassResource::class,
                LecturerResource::class,
                StudyPlanResource::class,
            ])
            ->widgets([
                SiakadOverviewWidget::class,
                SiakadScheduleWidget::class,
                SiakadPendingKrsWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
