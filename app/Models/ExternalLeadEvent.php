<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExternalLeadEvent extends Model
{
    protected $fillable = [
        'provider',
        'external_id',
        'event_type',
        'status',
        'campus_id',
        'study_program_id',
        'class_track_id',
        'lead_id',
        'payload_json',
        'normalized_payload_json',
        'error_message',
        'received_at',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload_json' => 'array',
            'normalized_payload_json' => 'array',
            'received_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function studyProgram(): BelongsTo
    {
        return $this->belongsTo(StudyProgram::class);
    }

    public function classTrack(): BelongsTo
    {
        return $this->belongsTo(ClassTrack::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
