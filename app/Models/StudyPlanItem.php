<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudyPlanItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'study_plan_id',
        'course_class_id',
        'status',
        'assignment_score',
        'midterm_score',
        'final_score',
        'final_numeric_score',
        'final_grade',
        'grade_point',
    ];

    public function studyPlan(): BelongsTo
    {
        return $this->belongsTo(StudyPlan::class);
    }

    public function courseClass(): BelongsTo
    {
        return $this->belongsTo(CourseClass::class);
    }
}
