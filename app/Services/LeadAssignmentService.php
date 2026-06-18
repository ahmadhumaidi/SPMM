<?php

namespace App\Services;

use App\Enums\ActivityType;
use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LeadAssignmentService
{
    public function __construct(private readonly AuditLogger $audit)
    {
    }

    /**
     * @param  array<int>  $leadIds
     * @return Collection<int, Lead>
     */
    public function assign(array $leadIds, User $staff, User $assignedBy): Collection
    {
        return DB::transaction(function () use ($leadIds, $staff, $assignedBy): Collection {
            $leads = Lead::query()
                ->whereIn('id', $leadIds)
                ->lockForUpdate()
                ->get();

            foreach ($leads as $lead) {
                $lead->update([
                    'assigned_to_user_id' => $staff->id,
                    'lead_status' => LeadStatus::Assigned,
                ]);

                $lead->activities()->create([
                    'user_id' => $assignedBy->id,
                    'activity_type' => ActivityType::Assignment,
                    'note' => "Assigned to {$staff->name}.",
                ]);

                $this->audit->record('lead_assigned', $lead, [
                    'assigned_to_user_id' => $staff->id,
                    'assigned_by_user_id' => $assignedBy->id,
                ], $assignedBy);
            }

            return $leads->fresh();
        });
    }
}
