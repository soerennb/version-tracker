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
        Schema::create('component_findings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sbom_document_id')->constrained('sbom_documents')->cascadeOnDelete();
            $table->foreignId('sbom_component_id')->nullable()->constrained('sbom_components')->nullOnDelete();
            $table->string('external_id', 255);
            $table->string('source', 64)->nullable();
            $table->string('severity', 32)->nullable()->index();
            $table->decimal('cvss_score', 3, 1)->nullable();
            $table->string('exploitability', 32)->default('unknown')->index();
            $table->string('status', 32)->default('open')->index();
            $table->text('description')->nullable();
            $table->string('affected_range', 255)->nullable();
            $table->string('fixed_version', 255)->nullable();
            $table->string('source_url', 1024)->nullable();
            $table->json('details')->nullable();
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->unique(['sbom_document_id', 'sbom_component_id', 'external_id'], 'component_findings_identity_unique');
            $table->index(['sbom_document_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('component_findings');
    }
};
