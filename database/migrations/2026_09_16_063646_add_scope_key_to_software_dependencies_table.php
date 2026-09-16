<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $dependencies = DB::table('software_dependencies')
            ->select([
                'id',
                'software_id',
                'depends_on_software_id',
                'applies_to_version_id',
                'dependency_type',
            ])
            ->orderBy('id')
            ->get();
        $scopeKeys = [];
        $updates = [];

        foreach ($dependencies as $dependency) {
            $scopeKey = hash('sha256', (string) json_encode([
                'software_id' => (int) $dependency->software_id,
                'depends_on_software_id' => (int) $dependency->depends_on_software_id,
                'applies_to_version_id' => $dependency->applies_to_version_id === null
                    ? null
                    : (int) $dependency->applies_to_version_id,
                'dependency_type' => strtolower(trim((string) $dependency->dependency_type)),
            ], JSON_THROW_ON_ERROR));

            if (isset($scopeKeys[$scopeKey])) {
                throw new RuntimeException(sprintf(
                    'Cannot add dependency scope uniqueness: records %d and %d have the same scope.',
                    $scopeKeys[$scopeKey],
                    $dependency->id,
                ));
            }

            $scopeKeys[$scopeKey] = $dependency->id;
            $updates[$dependency->id] = $scopeKey;
        }

        Schema::table('software_dependencies', function (Blueprint $table) {
            $table->string('scope_key', 64)->default('')->after('dependency_type');
        });

        foreach ($updates as $dependencyId => $scopeKey) {
            DB::table('software_dependencies')
                ->where('id', $dependencyId)
                ->update(['scope_key' => $scopeKey]);
        }

        Schema::table('software_dependencies', function (Blueprint $table) {
            $table->unique('scope_key', 'software_dependencies_scope_key_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('software_dependencies', function (Blueprint $table) {
            $table->dropUnique('software_dependencies_scope_key_unique');
            $table->dropColumn('scope_key');
        });
    }
};
