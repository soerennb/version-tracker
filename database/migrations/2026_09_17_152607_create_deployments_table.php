<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('deployments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('software_id')
                ->constrained('software')
                ->restrictOnDelete();
            $table->foreignId('version_id')
                ->constrained('versions')
                ->restrictOnDelete();
            $table->foreignId('environment_id')
                ->constrained('environments')
                ->restrictOnDelete();
            $table->string('status', 40)->default('planned')->index();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('executed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('change_reference', 150)->nullable();
            $table->timestamp('maintenance_window_start')->nullable();
            $table->timestamp('maintenance_window_end')->nullable();
            $table->string('external_reference', 255)->nullable()->unique();
            $table->string('source', 20)->default('web');
            $table->text('notes')->nullable();
            $table->text('result')->nullable();
            $table->string('relation_type', 20)->nullable();
            $table->unsignedBigInteger('related_deployment_id')->nullable()->index();
            $table->timestamps();

            $table->index(['environment_id', 'status', 'scheduled_at']);
            $table->index(['software_id', 'environment_id', 'completed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deployments');
    }
};
