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
        Schema::table('versions', function (Blueprint $table): void {
            $table->unique(['software_id', 'version_number'], 'versions_software_version_unique');
        });

        Schema::table('text_contents', function (Blueprint $table): void {
            $table->unique(['version_id', 'language'], 'text_contents_version_language_unique');
        });

        Schema::table('software_dependencies', function (Blueprint $table): void {
            $table->unique(
                ['software_id', 'depends_on_software_id', 'applies_to_version_id', 'dependency_type'],
                'software_dependencies_scope_unique',
            );
        });

        Schema::table('vulnerabilities', function (Blueprint $table): void {
            $table->dropUnique('vulnerabilities_cve_id_unique');
            $table->unique(
                ['cve_id', 'affected_version_id'],
                'vulnerabilities_cve_version_unique',
            );
            $table->string('external_id')->nullable()->after('cve_id');
            $table->timestamp('source_updated_at')->nullable()->after('source_url');
            $table->index(['source', 'external_id']);
        });

        Schema::table('file_attachments', function (Blueprint $table): void {
            $table->string('artifact_type')->nullable()->after('filename');
            $table->string('platform')->nullable()->after('artifact_type');
            $table->string('architecture')->nullable()->after('platform');
            $table->string('checksum')->nullable()->after('size');
            $table->string('checksum_algorithm')->nullable()->after('checksum');
            $table->text('signature')->nullable()->after('checksum_algorithm');
            $table->string('verification_status')->default('unverified')->after('signature');
            $table->boolean('is_public')->default(true)->after('verification_status');
            $table->index(['platform', 'architecture']);
            $table->index('verification_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('file_attachments', function (Blueprint $table): void {
            $table->dropIndex(['platform', 'architecture']);
            $table->dropIndex(['verification_status']);
            $table->dropColumn([
                'artifact_type',
                'platform',
                'architecture',
                'checksum',
                'checksum_algorithm',
                'signature',
                'verification_status',
                'is_public',
            ]);
        });

        Schema::table('vulnerabilities', function (Blueprint $table): void {
            $table->dropIndex(['source', 'external_id']);
            $table->dropUnique('vulnerabilities_cve_version_unique');
            $table->unique('cve_id');
            $table->dropColumn(['external_id', 'source_updated_at']);
        });

        Schema::table('software_dependencies', function (Blueprint $table): void {
            $table->dropUnique('software_dependencies_scope_unique');
        });

        Schema::table('text_contents', function (Blueprint $table): void {
            $table->dropUnique('text_contents_version_language_unique');
        });

        Schema::table('versions', function (Blueprint $table): void {
            $table->dropUnique('versions_software_version_unique');
        });
    }
};
