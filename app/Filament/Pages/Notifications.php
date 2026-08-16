<?php

namespace App\Filament\Pages;

use Filament\Notifications\Notification as FilamentNotification;
use Filament\Pages\Page;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Notifications\DatabaseNotification;

class Notifications extends Page
{
    protected static ?string $navigationIcon = "heroicon-o-bell";
    protected static ?string $navigationLabel = "Semua Notifikasi";
    protected static ?string $title = "Semua Notifikasi";
    protected static string $view = "filament.pages.notifications";
    protected static bool $shouldRegisterNavigation = false;

    public function getNotifications(): Paginator
    {
        return auth()->user()->notifications()->where("data->format", "filament")->simplePaginate(50);
    }

    public function renderNotification(DatabaseNotification $notification): FilamentNotification
    {
        return FilamentNotification::fromDatabase($notification)
            ->date($notification->created_at->diffForHumans());
    }
}