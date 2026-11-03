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
        Schema::create('evacuation_shelters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('local_government_id')->constrained('local_governments')->cascadeOnDelete();
            $table->string('common_id', 32)->nullable();
            $table->string('name');
            $table->string('address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('shelter_type', 32);
            $table->json('disaster_types')->nullable();
            $table->string('note')->nullable();
            $table->string('source_name');
            $table->string('source_url', 2048);
            $table->timestamp('fetched_at')->nullable();
            $table->timestamps();

            $table->unique(['local_government_id', 'common_id', 'shelter_type'], 'evacuation_shelters_unique');
            $table->index(['local_government_id', 'shelter_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evacuation_shelters');
    }
};
