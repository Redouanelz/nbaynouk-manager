<?php

namespace App\Models;

use App\Enums\ProjectServiceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ProjectService extends Pivot
{
    use HasFactory;

    protected $table = 'project_service';

    public $incrementing = true;

    protected $attributes = ['status' => 'pending', 'is_active' => true];

    protected $fillable = ['project_id', 'service_id', 'status', 'notes', 'completed_at', 'is_active'];

    protected function casts(): array
    {
        return [
            'status' => ProjectServiceStatus::class,
            'completed_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ProjectServiceAttachment::class, 'project_service_id');
    }
}
