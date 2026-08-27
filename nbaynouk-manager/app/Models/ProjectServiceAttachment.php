<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectServiceAttachment extends Model
{
    use HasFactory;

    protected $fillable = ['project_service_id', 'file_path', 'original_name', 'mime_type', 'file_size'];

    public function projectService(): BelongsTo
    {
        return $this->belongsTo(ProjectService::class, 'project_service_id');
    }
}
