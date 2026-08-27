<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('is_custom')->default(false)->index();
            $table->foreignId('created_for_project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('project_service', function (Blueprint $table) {
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
    }

    public function down(): void
    {
        Schema::dropIfExists('project_service');
        Schema::dropIfExists('services');
    }
};
