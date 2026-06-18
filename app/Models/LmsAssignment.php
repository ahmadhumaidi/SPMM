<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LmsAssignment extends Model
{
    use HasFactory;

    protected $fillable = ['lms_module_id', 'title', 'instructions', 'opens_at', 'due_at', 'max_score', 'status'];

    protected function casts(): array
    {
        return ['opens_at' => 'datetime', 'due_at' => 'datetime'];
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(LmsModule::class, 'lms_module_id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(LmsSubmission::class);
    }
}
