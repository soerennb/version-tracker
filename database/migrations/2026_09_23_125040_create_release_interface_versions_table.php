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
        Schema::create('release_interface_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('release_composition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('component_version_id')->constrained()->restrictOnDelete();
            $table->timestamps();
            $table->unique(['release_composition_id', 'component_version_id'], 'release_interface_version_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('release_interface_versions');
    }
};
