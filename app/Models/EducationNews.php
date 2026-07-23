<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class EducationNews extends Model
{
    use HasFactory;

    protected $table = 'education_news';

    protected $fillable = [
        'campus_id',
        'title',
        'slug',
        'category',
        'topik_artikel',
        'excerpt',
        'meta_description',
        'content',
        'image_path',
        'author_name',
        'source_name',
        'source_url',
        'source_hash',
        'source_published_at',
        'generated_by_ai',
        'ai_generated',
        'tipe_konten',
        'keyword_utama',
        'keyword_tambahan',
        'target_pembaca',
        'nama_kampus',
        'lokasi',
        'gaya_bahasa',
        'tipe_artikel',
        'panjang_artikel',
        'ai_generated_at',
        'ai_prompt_json',
        'status',
        'published_at',
        'published_by_user_id',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'source_published_at' => 'datetime',
            'generated_by_ai' => 'boolean',
            'ai_generated' => 'boolean',
            'ai_generated_at' => 'datetime',
            'ai_prompt_json' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (EducationNews $news): void {
            if (blank($news->created_by_user_id) && auth()->check()) {
                $news->created_by_user_id = auth()->id();
            }
        });

        static::saving(function (EducationNews $news): void {
            if (
                $news->status === 'published'
                && blank($news->published_by_user_id)
                && auth()->check()
            ) {
                $news->published_by_user_id = auth()->id();
            }
        });
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function campuses(): BelongsToMany
    {
        return $this->belongsToMany(Campus::class, 'campus_education_news')->withTimestamps();
    }

    public function publishedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by_user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
