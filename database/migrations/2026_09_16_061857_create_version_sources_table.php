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
        Schema::create('version_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('software_id')->constrained('software')->cascadeOnDelete();
            $table->foreignId('version_id')->constrained('versions')->cascadeOnDelete();
            $table->string('provider', 50);
            $table->string('source_kind', 50);
            $table->string('external_id', 255);
            $table->string('tag_name', 255);
            $table->string('name')->nullable();
            $table->text('body')->nullable();
            $table->string('source_url')->nullable();
            $table->timestamp('source_updated_at')->nullable();
            $table->string('payload_hash', 64)->nullable();
            $table->string('imported_content_hash', 64)->nullable();
            $table->boolean('is_prerelease')->default(false);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique(['software_id', 'provider', 'source_kind', 'external_id'], 'version_sources_identity_unique');
            $table->index(['version_id', 'provider']);
            $table->index(['software_id', 'provider', 'last_seen_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('version_sources');
    }
};
