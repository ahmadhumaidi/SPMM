<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditLogger
{
    public function record(string $event, ?Model $auditable = null, array $metadata = [], mixed $user = null): AuditLog
    {
        return AuditLog::create([
            'event' => $event,
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'user_id' => $user?->id ?? auth()->id(),
            'metadata' => $metadata,
        ]);
    }
}
