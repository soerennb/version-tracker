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
        Schema::table('deployment_events', function (Blueprint $table): void {
            $table->foreign('deployment_id')
                ->references('id')
                ->on('deployments')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deployment_events', function (Blueprint $table): void {
            $table->dropForeign(['deployment_id']);
        });
    }
};
