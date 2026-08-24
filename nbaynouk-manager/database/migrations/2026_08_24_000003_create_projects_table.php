<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_code_sequences', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->unsignedBigInteger('next_number')->default(1);
        });

        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->restrictOnDelete();
            $table->string('code')->unique();
            $table->string('name')->nullable();
            $table->string('status')->index();
            $table->string('billing_type')->index();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('currency', 3)->default('MAD');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->date('next_payment_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
        Schema::dropIfExists('project_code_sequences');
    }
};
