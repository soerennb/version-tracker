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
        Schema::create('sbom_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sbom_document_id')->constrained('sbom_documents')->cascadeOnDelete();
            $table->string('bom_ref', 255);
            $table->string('package_type', 64)->nullable();
            $table->string('group_name', 255)->nullable();
            $table->string('name', 255);
            $table->string('version', 255)->nullable();
            $table->string('purl', 1024)->nullable();
            $table->string('cpe', 1024)->nullable();
            $table->string('supplier', 255)->nullable();
            $table->json('licenses')->nullable();
            $table->json('hashes')->nullable();
            $table->json('properties')->nullable();
            $table->timestamps();

            $table->unique(['sbom_document_id', 'bom_ref']);
            $table->index(['name', 'version']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sbom_components');
    }
};
