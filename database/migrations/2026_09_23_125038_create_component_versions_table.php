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
        Schema::create('component_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tracked_component_id')->constrained()->restrictOnDelete();
            $table->string('version_label', 100);
            $table->text('notes')->nullable();
            $table->string('ted_acceptance_status', 20)->default('unknown');
            $table->date('ted_checked_at')->nullable();
            $table->timestamps();
            $table->unique(['tracked_component_id', 'version_label']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('component_versions');
    }
};
