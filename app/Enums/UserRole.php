<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case KoordinatorPmb = 'koordinator_pmb';
    case StaffPmb = 'staff_pmb';
}
