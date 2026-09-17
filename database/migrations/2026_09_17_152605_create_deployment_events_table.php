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
        Schema::create('deployment_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('deployment_id')->index();
            $table->foreignId('actor_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('type', 40);
            $table->string('from_status', 40)->nullable();
            $table->string('to_status', 40)->nullable();
            $table->text('comment')->nullable();
            $table->json('metadata')->nullable();
            $table->string('interface', 16)->nullable();
            $table->unsignedBigInteger('api_token_id')->nullable()->index();
            $table->timestamps();

            $table->index(['deployment_id', 'created_at']);
            $table->index(['type', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deployment_events');
    }
};
