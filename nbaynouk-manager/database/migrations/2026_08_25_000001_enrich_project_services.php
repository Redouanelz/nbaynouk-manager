<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('services', 'is_custom')) {
            Schema::table('services', function (Blueprint $table) {
                $table->boolean('is_custom')->default(false)->index();
                $table->foreignId('created_for_project_id')->nullable()->constrained('projects')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('project_service', 'id')) {
            Schema::create('project_service_enriched', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained()->cascadeOnDelete();
                $table->foreignId('service_id')->constrained()->restrictOnDelete();
                $table->string('status')->default('pending')->index();
                $table->text('notes')->nullable();
                $table->dateTime('completed_at')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
                $table->unique(['project_id', 'service_id']);
                $table->index(['project_id', 'status', 'is_active']);
            });

            DB::table('project_service')->orderBy('project_id')->each(function (object $row): void {
                DB::table('project_service_enriched')->insert([
                    'project_id' => $row->project_id,
                    'service_id' => $row->service_id,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ]);
            });
            Schema::drop('project_service');
            Schema::rename('project_service_enriched', 'project_service');
        }
    }

    public function down(): void
    {
        // Conservation volontaire des données opérationnelles lors d'un rollback applicatif.
    }
};
