<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiakadStudentSyncEvent extends Model
{
    protected $fillable = [
        'lead_id',
        'student_number_id',
        'payload_json',
        'status',
        'error_message',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'payload_json' => 'array',
            'sent_at' => 'datetime',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function studentNumber(): BelongsTo
    {
        return $this->belongsTo(StudentNumber::class);
    }
}
