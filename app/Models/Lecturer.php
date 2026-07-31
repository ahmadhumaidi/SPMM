<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lecturer extends Model
{
    use HasFactory;

    protected $fillable = [
        'campus_id',
        'name',
        'nidn',
        'email',
        'phone',
        'status',
    ];

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function courseClasses(): HasMany
    {
        return $this->hasMany(CourseClass::class);
    }
}
