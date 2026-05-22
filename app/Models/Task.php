<?php

namespace App\Models;

use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasUuids,HasFactory;

    protected $fillable = [
        'project_id', 'assigned_by', 'assigned_to',
        'name', 'description', 'status', 
        'due_date', 'completed_at',
    ];

    protected $casts = [
        'status' => TaskStatus::class,
        'due_date' => 'datetime',
        'completed_at' => 'datetime',
    ];

    // --- Relationships ---

    // User this task is assigned by
    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    // User this task is assigned to
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    // Project this task belongs to
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    // Attachments under this Task
    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class, 'task_id');
    }
}