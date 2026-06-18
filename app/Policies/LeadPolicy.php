<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Lead;
use App\Models\User;

class LeadPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [UserRole::SuperAdmin, UserRole::KoordinatorPmb, UserRole::StaffPmb], true);
    }

    public function view(User $user, Lead $lead): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->role === UserRole::KoordinatorPmb) {
            return $user->campuses()->whereKey($lead->campus_id)->exists();
        }

        return $lead->assigned_to_user_id === $user->id;
    }

    public function update(User $user, Lead $lead): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->role === UserRole::KoordinatorPmb) {
            return $user->campuses()->whereKey($lead->campus_id)->exists();
        }

        return $lead->assigned_to_user_id === $user->id && ! $lead->isLockedForStaff();
    }

    public function delete(User $user, Lead $lead): bool
    {
        return $user->isSuperAdmin();
    }
}
