<?php

namespace App\Services;

use App\Enums\ActivityType;
use App\Enums\ProspectStatus;
use App\Models\Lead;
use App\Models\LeadProspectEvent;
use App\Models\User;
use InvalidArgumentException;

class LeadProspectStatusService
{
    public function __construct(
        private readonly MetaProspectEventService $metaEvents,
    ) {
    }

    public function update(Lead $lead, string $status, ?User $user = null): LeadProspectEvent
    {
        $prospectStatus = ProspectStatus::tryFrom($status);

        if (! $prospectStatus) {
            throw new InvalidArgumentException('Status prospek tidak valid.');
        }

        $from = $lead->prospect_status?->value ?? $lead->prospect_status;
        $to = $prospectStatus->value;

        $lead->update(['prospect_status' => $to]);

        $lead->activities()->create([
            'user_id' => $user?->id,
            'activity_type' => ActivityType::ProspectStatusChange,
            'note' => 'Status prospek diubah dari '.($from ?: '-').' menjadi '.$to.'.',
        ]);

        $payload = $this->metaEvents->buildEvent($lead->fresh(['campus', 'studyProgram']), $to, $user?->id);

        $event = LeadProspectEvent::query()->create([
            'lead_id' => $lead->id,
            'user_id' => $user?->id,
            'from_status' => $from,
            'to_status' => $to,
            'meta_event_name' => $payload['event_name'],
            'meta_payload_json' => $payload,
            'meta_status' => 'pending',
        ]);

        $this->metaEvents->send($event);

        return $event->fresh();
    }
}
