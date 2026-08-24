<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Project;

class ActivityLogService
{
    public function record(Project $project, string $type, string $description, array $metadata = []): ActivityLog
    {
        return $project->activityLogs()->create([
            'type' => $type,
            'description' => $description,
            'metadata' => $metadata ?: null,
            'occurred_at' => now(),
        ]);
    }
}
