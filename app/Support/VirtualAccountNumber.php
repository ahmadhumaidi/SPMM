<?php

namespace App\Support;

use App\Models\Invoice;
use App\Models\Lead;

class VirtualAccountNumber
{
    public static function forLead(?Lead $lead): string
    {
        if (! $lead) {
            return '-';
        }

        return static::format((int) $lead->id);
    }

    public static function forInvoice(?Invoice $invoice): string
    {
        if (! $invoice) {
            return '-';
        }

        if (filled($invoice->va_number) && preg_match('/^\d+$/', (string) $invoice->va_number)) {
            return (string) $invoice->va_number;
        }

        return static::forLead($invoice->lead);
    }

    public static function format(int $leadId): string
    {
        return '000'.str_pad((string) $leadId, 7, '0', STR_PAD_LEFT);
    }
}
