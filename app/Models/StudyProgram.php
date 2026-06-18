<?php

namespace App\Models;

use App\Enums\CampusStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudyProgram extends Model
{
    use HasFactory;

    protected $fillable = [
        'campus_id',
        'code',
        'name',
        'degree_level',
        'degree_title',
        'faculty',
        'accreditation',
        'prospectus',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => CampusStatus::class,
        ];
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }
}
