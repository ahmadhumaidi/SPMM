<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class StudentNumber extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'uuid',
        'nim',
        'issued_by_user_id',
        'issued_at',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (StudentNumber $studentNumber): void {
            $studentNumber->uuid ??= (string) Str::uuid();
        });
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by_user_id');
    }

    public function siakadSyncEvents(): HasMany
    {
        return $this->hasMany(SiakadStudentSyncEvent::class);
    }
}
