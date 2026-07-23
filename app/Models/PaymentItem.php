<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'campus_id',
        'name',
        'description',
        'default_amount',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'default_amount' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function studentPayments(): HasMany
    {
        return $this->hasMany(StudentPayment::class);
    }
}
