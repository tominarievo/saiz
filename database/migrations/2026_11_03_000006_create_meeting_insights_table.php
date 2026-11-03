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
        Schema::create('meeting_insights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('disaster_id')->constrained('disasters')->cascadeOnDelete();
            $table->string('meeting_name');
            $table->timestamp('held_at')->nullable();
            $table->json('decisions')->nullable();
            $table->json('policy_changes')->nullable();
            $table->json('unmet_needs')->nullable();
            $table->unsignedInteger('source_page')->nullable();
            $table->string('source_org');
            $table->string('source_url', 2048);
            $table->timestamp('source_published_at')->nullable();
            $table->timestamp('scraped_at')->nullable();
            $table->string('raw_file_path', 2048)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['disaster_id', 'held_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meeting_insights');
    }
};
