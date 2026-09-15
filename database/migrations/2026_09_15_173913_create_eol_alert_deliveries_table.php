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
        Schema::create('eol_alert_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('version_id')->constrained()->cascadeOnDelete();
            $table->date('eol_date');
            $table->unsignedTinyInteger('window_days');
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamps();
            $table->unique(['version_id', 'eol_date', 'window_days']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('eol_alert_deliveries');
    }
};
