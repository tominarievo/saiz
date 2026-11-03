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
        Schema::create('road_regulations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('disaster_id')->constrained('disasters')->cascadeOnDelete();
            $table->string('road_name');
            $table->string('section')->nullable();
            $table->string('regulation_type', 32);
            $table->json('vehicle_classes')->nullable();
            $table->string('reason')->nullable();
            $table->json('geometry')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('confidence_rank', 1)->default('C');
            $table->string('source_org');
            $table->string('source_url', 2048);
            $table->timestamp('source_published_at')->nullable();
            $table->timestamp('scraped_at')->nullable();
            $table->string('raw_file_path', 2048)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['disaster_id', 'regulation_type']);
            $table->index(['disaster_id', 'confidence_rank']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('road_regulations');
    }
};
