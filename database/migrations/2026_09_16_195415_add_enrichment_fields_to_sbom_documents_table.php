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
        Schema::table('sbom_documents', function (Blueprint $table): void {
            $table->timestamp('last_enriched_at')->nullable()->after('parsed_at');
            $table->string('enrichment_status', 32)->default('pending')->after('last_enriched_at')->index();
            $table->text('enrichment_error')->nullable()->after('enrichment_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sbom_documents', function (Blueprint $table): void {
            $table->dropIndex(['enrichment_status']);
            $table->dropColumn(['last_enriched_at', 'enrichment_status', 'enrichment_error']);
        });
    }
};
