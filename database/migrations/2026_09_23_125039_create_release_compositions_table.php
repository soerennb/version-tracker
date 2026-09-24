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
        Schema::create('release_compositions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('version_id')->unique()->constrained('versions')->cascadeOnDelete();
            $table->foreignId('baseline_version_id')->constrained('component_versions')->restrictOnDelete();
            $table->foreignId('eforms_component_version_id')->constrained('component_versions')->restrictOnDelete();
            $table->foreignId('active_eforms_sdk_version_id')->constrained('component_versions')->restrictOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('release_compositions');
    }
};
