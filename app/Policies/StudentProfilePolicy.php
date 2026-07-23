<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\StudentProfile;
use App\Models\User;

class StudentProfilePolicy
{
    public function view(User $user, StudentProfile $studentProfile): bool
    {
        if ($user->isSuperAdmin() || $user->role === UserRole::Direktur) {
            return true;
        }

        if ($user->role === UserRole::KoordinatorPmb) {
            return $user->campuses()->whereKey($studentProfile->lead->campus_id)->exists();
        }

        return $studentProfile->lead->assigned_to_user_id === $user->id;
    }

    public function update(User $user, StudentProfile $studentProfile): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->role === UserRole::Direktur) {
            return false;
        }

        return $user->role === UserRole::KoordinatorPmb
            && $user->campuses()->whereKey($studentProfile->lead->campus_id)->exists()
            && ! $studentProfile->lead->isLockedForStaff();
    }
}
