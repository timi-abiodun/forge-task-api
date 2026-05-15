<?php

namespace App\Models;

use App\Enums\ProjectStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToOrganisation;

class Project extends Model
{
    use HasUuids, HasFactory, BelongsToOrganisation;

    protected $fillable = ['name', 'description', 'status'];

    protected $casts = [
        'status' => ProjectStatus::class,
    ];

    // --- Relationships ---

    // Organisation project belongs to
    public function organisation(): BelongsTo
    {
        return $this->belongsTo(Organisation::class, 'organisation_id');
    }
    
    // Tasks under project
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'project_id');
    }

    // Attachments under this Project
    public function attachments(): HasManyThrough
    {
        return $this->hasManyThrough(Attachment::class, Task::class);
    }

}
