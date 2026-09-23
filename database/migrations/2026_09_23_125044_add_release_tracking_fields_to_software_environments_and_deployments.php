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
        Schema::table('software', function (Blueprint $table): void {
            $table->boolean('tracks_release_composition')->default(false);
        });
        Schema::table('environments', function (Blueprint $table): void {
            $table->foreignId('customer_id')->nullable()->constrained()->restrictOnDelete();
        });
        Schema::table('deployments', function (Blueprint $table): void {
            $table->foreignId('customization_version_id')->nullable()->constrained('component_versions')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deployments', fn (Blueprint $table) => $table->dropConstrainedForeignId('customization_version_id'));
        Schema::table('environments', fn (Blueprint $table) => $table->dropConstrainedForeignId('customer_id'));
        Schema::table('software', fn (Blueprint $table) => $table->dropColumn('tracks_release_composition'));
    }
};
