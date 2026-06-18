<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LmsMaterial extends Model
{
    use HasFactory;

    protected $fillable = ['lms_module_id', 'title', 'material_type', 'content', 'file_path', 'external_url', 'sort_order', 'status'];

    public function module(): BelongsTo
    {
        return $this->belongsTo(LmsModule::class, 'lms_module_id');
    }
}
