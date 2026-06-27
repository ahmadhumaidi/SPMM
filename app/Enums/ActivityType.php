<?php

namespace App\Enums;

enum ActivityType: string
{
    case Call = 'call';
    case Whatsapp = 'whatsapp';
    case Note = 'note';
    case StatusChange = 'status_change';
    case ProspectStatusChange = 'prospect_status_change';
    case InvoiceRegenerated = 'invoice_regenerated';
    case Assignment = 'assignment';
}
