<?php

namespace App\Providers;

use App\Enums\ProjectStatus;
use App\Models\Project;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('components.sidebar', function ($view): void {
            $counts = Project::query()->selectRaw(
                'COUNT(*) as total, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as launch, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as suivi',
                [ProjectStatus::Launch->value, ProjectStatus::Suivi->value]
            )->first();

            $view->with('sidebarProjectCounts', [
                'total' => (int) ($counts->total ?? 0),
                'launch' => (int) ($counts->launch ?? 0),
                'suivi' => (int) ($counts->suivi ?? 0),
            ]);
        });
    }
}
