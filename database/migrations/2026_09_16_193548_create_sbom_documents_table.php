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
        Schema::create('sbom_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('version_id')->constrained('versions')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('filename', 255);
            $table->string('format', 32);
            $table->string('spec_version', 32)->nullable();
            $table->string('serial_number', 255)->nullable();
            $table->string('document_hash', 64);
            $table->string('idempotency_key', 191)->nullable();
            $table->string('source', 32)->default('manual');
            $table->string('status', 32)->default('processed')->index();
            $table->unsignedInteger('component_count')->default(0);
            $table->unsignedInteger('finding_count')->default(0);
            $table->timestamp('parsed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->longText('payload');
            $table->timestamps();

            $table->unique(['version_id', 'document_hash']);
            $table->unique(['version_id', 'idempotency_key']);
            $table->index(['version_id', 'status', 'parsed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sbom_documents');
    }
};
