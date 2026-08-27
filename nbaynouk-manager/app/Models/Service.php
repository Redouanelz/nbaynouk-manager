<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'is_custom', 'created_for_project_id'];

    protected function casts(): array
    {
        return ['is_custom' => 'boolean'];
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class)->withTimestamps();
    }

    public function projectServices(): HasMany
    {
        return $this->hasMany(ProjectService::class);
    }

    public function createdForProject(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'created_for_project_id');
    }
}
