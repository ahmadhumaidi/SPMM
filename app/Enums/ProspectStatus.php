<?php

namespace App\Enums;

enum ProspectStatus: string
{
    case Cold = 'cold';
    case Warm = 'warm';
    case Hot = 'hot';
    case Closing = 'closing';
}
