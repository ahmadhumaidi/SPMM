<?php

namespace App\Enums;

enum LeadStatus: string
{
    case New = 'new';
    case InPool = 'in_pool';
    case Assigned = 'assigned';
    case Contacted = 'contacted';
    case Interested = 'interested';
    case NotQualified = 'not_qualified';
    case Lost = 'lost';
    case Converted = 'converted';
}
