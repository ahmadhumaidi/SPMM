<?php

namespace App\Policies;

use App\Models\StudentNumber;
use App\Models\User;

class StudentNumberPolicy
{
    public function view(User $user, StudentNumber $studentNumber): bool
    {
        return $user->isSuperAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function update(User $user, StudentNumber $studentNumber): bool
    {
        return $user->isSuperAdmin();
    }
}
