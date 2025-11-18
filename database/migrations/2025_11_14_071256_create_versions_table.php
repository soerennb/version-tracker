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
        Schema::create('versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('software_id')
                ->constrained('software')
                ->cascadeOnDelete();
            $table->string('version_number', 50);
            $table->date('release_date');
            $table->string('status')->default('draft')->index();
            $table->string('approval_status')->default('pending')->index();
            $table->date('eol_date')->nullable();
            $table->date('lts_date')->nullable();
            $table->string('support_status')->nullable();
            $table->timestamps();

            $table->index('software_id');
            $table->index('version_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('versions');
    }
};
