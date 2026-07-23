<?php

namespace App\Models;

use App\Enums\CampusStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campus extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'subdomain',
        'custom_domain',
        'status',
        'address',
        'city',
        'province',
        'logo_path',
        'website_settings',
    ];

    protected function casts(): array
    {
        return [
            'status' => CampusStatus::class,
            'website_settings' => 'array',
        ];
    }

    public function studyPrograms(): HasMany
    {
        return $this->hasMany(StudyProgram::class);
    }

    public function classTracks(): HasMany
    {
        return $this->hasMany(ClassTrack::class);
    }

    public function feeSchemes(): HasMany
    {
        return $this->hasMany(FeeScheme::class);
    }

    public function educationNews(): HasMany
    {
        return $this->hasMany(EducationNews::class);
    }

    public function publishedNews(): BelongsToMany
    {
        return $this->belongsToMany(EducationNews::class, 'campus_education_news')->withTimestamps();
    }

    public function publicUrl(): string
    {
        if (filled($this->custom_domain)) {
            return str_starts_with($this->custom_domain, 'http')
                ? $this->custom_domain
                : 'https://'.$this->custom_domain;
        }

        if (filled($this->subdomain) && ! app()->environment('local')) {
            return 'https://'.$this->subdomain.'.kampus.media';
        }

        return route('campuses.show', ['campus' => $this->name]);
    }
}
