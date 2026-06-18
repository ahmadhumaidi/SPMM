<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LmsModule extends Model
{
    use HasFactory;

    protected $fillable = ['course_class_id', 'title', 'description', 'sort_order', 'status'];

    public function courseClass(): BelongsTo
    {
        return $this->belongsTo(CourseClass::class);
    }

    public function materials(): HasMany
    {
        return $this->hasMany(LmsMaterial::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(LmsAssignment::class);
    }
}
