<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attachment extends Model
{
    use HasUuids, HasFactory;

    protected $fillable = [
        'file_name', 'file_path', 'file_disk',
        'mime_type', 'file_size',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    // --- Relationships ---

    // User attachment was uploaded by
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // Task this attachment is uploaded under
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id');
    }
    
}
