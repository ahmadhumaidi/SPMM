<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function view(User $user, Invoice $invoice): bool
    {
        if ($user->isSuperAdmin() || $user->role === UserRole::Direktur) {
            return true;
        }

        if ($user->role === UserRole::KoordinatorPmb) {
            return $user->campuses()->whereKey($invoice->lead->campus_id)->exists();
        }

        return $invoice->lead->assigned_to_user_id === $user->id;
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $user->isSuperAdmin();
    }
}
