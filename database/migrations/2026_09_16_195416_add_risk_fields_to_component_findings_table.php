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
        Schema::table('component_findings', function (Blueprint $table): void {
            $table->decimal('epss_score', 5, 4)->nullable()->after('cvss_score');
            $table->boolean('is_kev')->default(false)->after('epss_score')->index();
            $table->decimal('risk_score', 5, 2)->nullable()->after('is_kev')->index();
            $table->json('risk_factors')->nullable()->after('risk_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('component_findings', function (Blueprint $table): void {
            $table->dropIndex(['is_kev']);
            $table->dropIndex(['risk_score']);
            $table->dropColumn(['epss_score', 'is_kev', 'risk_score', 'risk_factors']);
        });
    }
};
