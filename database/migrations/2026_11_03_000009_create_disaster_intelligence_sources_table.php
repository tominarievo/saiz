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
        Schema::create('disaster_intelligence_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('disaster_id')->constrained('disasters')->cascadeOnDelete();
            $table->string('type', 32);
            $table->string('url', 2048);
            $table->string('url_hash', 40);
            $table->string('source_org');
            $table->timestamp('published_at')->nullable();
            $table->string('credibility_rank', 1)->default('C');
            $table->text('credibility_reason')->nullable();
            $table->string('status', 16)->default('active');
            $table->timestamp('discovered_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['disaster_id', 'type', 'url_hash'], 'intelligence_sources_unique');
            $table->index(['disaster_id', 'type', 'status']);
        });

        foreach (['road_regulations', 'waste_sites', 'meeting_insights'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->string('source_credibility', 1)->nullable()->after('source_url');
                $table->text('source_credibility_reason')->nullable()->after('source_credibility');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['road_regulations', 'waste_sites', 'meeting_insights'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn(['source_credibility', 'source_credibility_reason']);
            });
        }

        Schema::dropIfExists('disaster_intelligence_sources');
    }
};
