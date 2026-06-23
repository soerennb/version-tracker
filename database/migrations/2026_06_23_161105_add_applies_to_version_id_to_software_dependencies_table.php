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
        Schema::table('software_dependencies', function (Blueprint $table) {
            $table->foreignId('applies_to_version_id')
                ->nullable()
                ->after('depends_on_software_id')
                ->constrained('versions')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('software_dependencies', function (Blueprint $table) {
            $table->dropConstrainedForeignId('applies_to_version_id');
        });
    }
};
