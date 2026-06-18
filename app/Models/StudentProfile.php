<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'nik',
        'nisn',
        'birth_place',
        'birth_date',
        'gender',
        'religion',
        'mother_name',
        'father_name',
        'address',
        'province',
        'city',
        'district',
        'village',
        'postal_code',
        'school_npsn',
        'citizenship',
        'phone',
        'email',
        'pddikti_payload_json',
        'completed_at',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'pddikti_payload_json' => 'array',
            'completed_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
