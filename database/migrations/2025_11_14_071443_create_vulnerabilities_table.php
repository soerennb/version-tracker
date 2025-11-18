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
        Schema::create('vulnerabilities', function (Blueprint $table) {
            $table->id();
            $table->string('cve_id');
            $table->foreignId('affected_version_id')
                ->constrained('versions')
                ->cascadeOnDelete();
            $table->string('severity');
            $table->text('description');
            $table->date('published_date');
            $table->timestamps();

            $table->unique('cve_id');
            $table->index('affected_version_id');
            $table->index('severity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vulnerabilities');
    }
};
