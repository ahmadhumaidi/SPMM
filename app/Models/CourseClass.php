<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseClass extends Model
{
    use HasFactory;

    protected $fillable = [
        'academic_term_id',
        'course_id',
        'class_track_id',
        'lecturer_id',
        'class_name',
        'day_of_week',
        'starts_at',
        'ends_at',
        'room',
        'capacity',
        'status',
    ];

    public function academicTerm(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function classTrack(): BelongsTo
    {
        return $this->belongsTo(ClassTrack::class);
    }

    public function lecturer(): BelongsTo
    {
        return $this->belongsTo(Lecturer::class);
    }

    public function studyPlanItems(): HasMany
    {
        return $this->hasMany(StudyPlanItem::class);
    }

    public function lmsModules(): HasMany
    {
        return $this->hasMany(LmsModule::class);
    }
}
