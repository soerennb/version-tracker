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
        Schema::table('vulnerabilities', function (Blueprint $table) {
            $table->decimal('cvss_score', 3, 1)->nullable()->after('severity');
            $table->string('source')->nullable()->after('description');
            $table->string('source_url')->nullable()->after('source');
            $table->string('affected_range')->nullable()->after('source_url');
            $table->foreignId('fixed_version_id')
                ->nullable()
                ->after('affected_range')
                ->constrained('versions')
                ->nullOnDelete();
            $table->string('status')->default('open')->after('fixed_version_id')->index();
            $table->string('exploitability')->default('unknown')->after('status')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vulnerabilities', function (Blueprint $table) {
            $table->dropConstrainedForeignId('fixed_version_id');
            $table->dropColumn([
                'cvss_score',
                'source',
                'source_url',
                'affected_range',
                'status',
                'exploitability',
            ]);
        });
    }
};
