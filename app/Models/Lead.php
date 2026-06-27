<?php

namespace App\Models;

use App\Enums\EnrollmentStatus;
use App\Enums\LeadStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProspectStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Lead extends Model
{
    use HasFactory;

    protected $fillable = [
        'campus_id',
        'study_program_id',
        'class_track_id',
        'assigned_to_user_id',
        'referral_partner_id',
        'referral_code',
        'source_channel',
        'source_detail',
        'full_name',
        'whatsapp_number',
        'email',
        'origin_school',
        'graduation_year',
        'lead_status',
        'prospect_status',
        'payment_status',
        'enrollment_status',
        'pemberkasan_token',
        'locked_at',
    ];

    protected function casts(): array
    {
        return [
            'graduation_year' => 'integer',
            'lead_status' => LeadStatus::class,
            'prospect_status' => ProspectStatus::class,
            'payment_status' => PaymentStatus::class,
            'enrollment_status' => EnrollmentStatus::class,
            'locked_at' => 'datetime',
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

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function referralPartner(): BelongsTo
    {
        return $this->belongsTo(ReferralPartner::class);
    }

    public function referralConversion(): HasOne
    {
        return $this->hasOne(ReferralConversion::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function latestInvoice(): HasOne
    {
        return $this->hasOne(Invoice::class)->latestOfMany();
    }

    public function studentProfile(): HasOne
    {
        return $this->hasOne(StudentProfile::class);
    }

    public function studentBiodata(): HasOne
    {
        return $this->hasOne(StudentBiodata::class);
    }

    public function studentNumber(): HasOne
    {
        return $this->hasOne(StudentNumber::class);
    }

    public function studentAccount(): HasOne
    {
        return $this->hasOne(StudentAccount::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(LeadActivity::class);
    }

    public function prospectEvents(): HasMany
    {
        return $this->hasMany(LeadProspectEvent::class);
    }

    public function studentDocuments(): HasMany
    {
        return $this->hasMany(StudentDocument::class);
    }

    public function studentPayments(): HasMany
    {
        return $this->hasMany(StudentPayment::class);
    }

    public function lmsSubmissions(): HasMany
    {
        return $this->hasMany(LmsSubmission::class);
    }

    public function isLockedForStaff(): bool
    {
        return $this->locked_at !== null
            || $this->payment_status === PaymentStatus::Paid
            || in_array($this->enrollment_status, [
                EnrollmentStatus::MenungguPemutakhiran,
                EnrollmentStatus::ProsesPemutakhiran,
                EnrollmentStatus::MahasiswaAktif,
            ], true);
    }
}
