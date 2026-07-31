<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeScheme extends Model
{
    use HasFactory;

    protected $fillable = [
        'campus_id',
        'study_program_id',
        'class_track_id',
        'financing_model',
        'registration_fee',
        'building_fee',
        'monthly_tuition_fee',
        'tuition_semesters',
        'ukt_total',
        'ukt_first_installment_amount',
        'total_initial_payment',
        'development_installments_json',
        'tuition_installments_json',
        'ukt_installments_json',
        'custom_payment_items_json',
        'installment_schedule_json',
        'uses_custom_installments',
        'custom_installment_schedule_json',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'registration_fee' => 'integer',
            'building_fee' => 'integer',
            'monthly_tuition_fee' => 'integer',
            'tuition_semesters' => 'integer',
            'ukt_total' => 'integer',
            'ukt_first_installment_amount' => 'integer',
            'total_initial_payment' => 'integer',
            'development_installments_json' => 'array',
            'tuition_installments_json' => 'array',
            'ukt_installments_json' => 'array',
            'custom_payment_items_json' => 'array',
            'installment_schedule_json' => 'array',
            'uses_custom_installments' => 'boolean',
            'custom_installment_schedule_json' => 'array',
            'is_active' => 'boolean',
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
}
