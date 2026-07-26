<?php

namespace App\Support;

use App\Enums\UserRole;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;

class FilamentResourceScope
{
    public static function applyCampusScope(Builder $query, string $campusColumn = 'campus_id'): Builder
    {
        $user = auth()->user();

        if ($user === null || $user->isSuperAdmin() || static::isDirector()) {
            return $query;
        }

        if (in_array($user->role, [UserRole::KoordinatorPmb, UserRole::StaffPmb], true)) {
            return $query->whereIn($campusColumn, $user->campuses()->select('campuses.id'));
        }

        return $query->whereRaw('1 = 0');
    }

    public static function applyManagedLeadCampusScope(Builder $query): Builder
    {
        $user = auth()->user();

        if ($user === null || $user->isSuperAdmin() || static::isDirector()) {
            return $query;
        }

        if (in_array($user->role, [UserRole::KoordinatorPmb, UserRole::StaffPmb], true)) {
            return $query->whereIn('leads.campus_id', $user->campuses()->select('campuses.id'));
        }

        return $query->whereRaw('1 = 0');
    }

    public static function applyRelatedCampusScope(Builder $query, string $relation, string $campusColumn = 'campus_id'): Builder
    {
        $user = auth()->user();

        if ($user === null || $user->isSuperAdmin() || static::isDirector()) {
            return $query;
        }

        if (in_array($user->role, [UserRole::KoordinatorPmb, UserRole::StaffPmb], true)) {
            $campusIds = $user->campuses()->select('campuses.id');

            return $query->whereHas($relation, fn (Builder $related): Builder => $related->whereIn($campusColumn, $campusIds));
        }

        return $query->whereRaw('1 = 0');
    }

    public static function applyLeadScope(Builder $query): Builder
    {
        $user = auth()->user();

        if ($user === null || $user->isSuperAdmin() || static::isDirector()) {
            return $query;
        }

        if ($user->role === UserRole::KoordinatorPmb) {
            return $query->whereIn('leads.campus_id', $user->campuses()->select('campuses.id'));
        }

        if ($user->role === UserRole::StaffPmb) {
            return $query->where('leads.assigned_to_user_id', $user->id);
        }

        return $query;
    }

    public static function isSuperAdmin(): bool
    {
        return auth()->user()?->role === UserRole::SuperAdmin;
    }

    public static function isCoordinator(): bool
    {
        return auth()->user()?->role === UserRole::KoordinatorPmb;
    }

    public static function isDirector(): bool
    {
        return auth()->user()?->role === UserRole::Direktur;
    }

    public static function isStaff(): bool
    {
        return auth()->user()?->role === UserRole::StaffPmb;
    }

    public static function canAccessMasterData(): bool
    {
        return static::isSuperAdmin() || static::isCoordinator();
    }

    public static function canAccessAcademic(): bool
    {
        if (Filament::getCurrentPanel()?->getId() !== 'siakad') {
            return false;
        }

        return static::isSuperAdmin() || static::isDirector() || static::isCoordinator();
    }

    public static function canAccessPortalPublic(): bool
    {
        return static::isSuperAdmin() || static::isDirector() || static::isCoordinator() || static::isStaff();
    }

    public static function canAccessReports(): bool
    {
        return static::isSuperAdmin() || static::isDirector() || static::isCoordinator() || static::isStaff();
    }

    public static function canAccessPddikti(): bool
    {
        return static::isSuperAdmin();
    }

    public static function canAccessUsers(): bool
    {
        return static::isSuperAdmin() || static::isCoordinator();
    }

    public static function canAccessAffiliates(): bool
    {
        return static::isSuperAdmin() || static::isDirector() || static::isCoordinator() || static::isStaff();
    }

    public static function canAccessPayments(): bool
    {
        return static::isSuperAdmin() || static::isDirector() || static::isCoordinator() || static::isStaff();
    }

    public static function canVerifyPayments(): bool
    {
        return static::canAccessPayments();
    }

    public static function canAccessStudentRecords(): bool
    {
        return static::isSuperAdmin() || static::isDirector() || static::isCoordinator() || static::isStaff();
    }
}
