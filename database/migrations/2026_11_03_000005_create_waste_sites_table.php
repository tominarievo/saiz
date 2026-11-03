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
        Schema::create('waste_sites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('disaster_id')->constrained('disasters')->cascadeOnDelete();
            $table->string('name');
            $table->string('status', 16)->default('unknown');
            $table->json('geometry')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamp('acceptance_start_at')->nullable();
            $table->timestamp('acceptance_end_at')->nullable();
            $table->string('vehicle_limit')->nullable();
            $table->decimal('max_payload_tons', 5, 2)->nullable();
            $table->boolean('entry_allowed')->default(true);
            $table->json('accepted_items')->nullable();
            $table->json('rejected_items')->nullable();
            $table->json('support_conditions')->nullable();
            $table->string('source_org');
            $table->string('source_url', 2048);
            $table->timestamp('source_published_at')->nullable();
            $table->timestamp('scraped_at')->nullable();
            $table->string('raw_file_path', 2048)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['disaster_id', 'status']);
            $table->index(['disaster_id', 'max_payload_tons']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('waste_sites');
    }
};
