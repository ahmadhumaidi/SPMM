<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentBiodata extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_type',
        'lead_id',
        'campus_id',
        'study_program_id',
        'class_track_id',
        'information_source',
        'affiliator_code',
        'information_source_other',
        'name',
        'selection_number',
        'student_number',
        'group',
        'cohort_year',
        'financial_status',
        'entry_type',
        'class_time',
        'photo_path',
        'birth_place',
        'birth_date',
        'religion',
        'gender',
        'address',
        'mailing_address',
        'city',
        'district',
        'village',
        'postal_code',
        'rt_rw',
        'phone',
        'mobile_phone',
        'email',
        'whatsapp_number',
        'identity_number',
        'mother_name',
        'mother_identity_number',
        'father_name',
        'father_identity_number',
        'family_number',
        'family_name',
        'family_relationship',
        'company_name',
        'job_title',
        'company_address',
        'company_city',
        'company_postal_code',
        'company_phone',
        'company_fax',
        'development_contribution',
        'semester_education_contribution',
        'almamater_jacket_fee',
        'form_fee',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'development_contribution' => 'integer',
            'semester_education_contribution' => 'integer',
            'almamater_jacket_fee' => 'integer',
            'form_fee' => 'integer',
        ];
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
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
