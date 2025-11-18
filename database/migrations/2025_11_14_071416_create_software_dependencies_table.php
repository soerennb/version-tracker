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
        Schema::create('software_dependencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('software_id')
                ->constrained('software')
                ->cascadeOnDelete();
            $table->foreignId('depends_on_software_id')
                ->constrained('software')
                ->cascadeOnDelete();
            $table->foreignId('min_version_id')
                ->nullable()
                ->constrained('versions')
                ->cascadeOnDelete();
            $table->foreignId('max_version_id')
                ->nullable()
                ->constrained('versions')
                ->cascadeOnDelete();
            $table->string('dependency_type')->default('runtime');
            $table->timestamps();

            $table->index('software_id');
            $table->index('depends_on_software_id');
            $table->index('min_version_id');
            $table->index('max_version_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('software_dependencies');
    }
};
