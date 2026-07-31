<?php

namespace App\Support;

use App\Enums\UserRole;
use App\Models\Campus;

class SiakadCampusResolver
{
    /**
     * Prototype: SIAKAD is scoped to a single campus at a time, mirroring the target
     * architecture where each campus eventually runs its own SIAKAD instance
     * (docs/separated-systems-architecture.md). Koordinator/staff see their own assigned
     * campus; super_admin/direktur default to the demo campus (STIE Pemuda).
     */
    public static function current(): ?Campus
    {
        $user = auth()->user();

        if (! $user) {
            return static::prototypeCampus();
        }

        $isUnrestricted = $user->isSuperAdmin() || $user->role === UserRole::Direktur;

        return $isUnrestricted
            ? static::prototypeCampus()
            : $user->campuses()->orderBy('name')->first();
    }

    private static function prototypeCampus(): ?Campus
    {
        return Campus::query()->where('slug', 'stie-pemuda')->first() ?? Campus::query()->orderBy('name')->first();
    }
}
