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
        Schema::create('volunteer_centers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('local_government_id')->constrained('local_governments')->cascadeOnDelete();
            $table->string('name');
            $table->string('address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('phone')->nullable();
            $table->string('url', 2048)->nullable();
            $table->string('latest_recruitment_title')->nullable();
            $table->text('latest_recruitment_summary')->nullable();
            $table->string('latest_recruitment_url', 2048)->nullable();
            $table->timestamp('recruitment_updated_at')->nullable();
            $table->string('source_name');
            $table->string('source_url', 2048);
            $table->timestamp('fetched_at')->nullable();
            $table->timestamps();

            $table->unique(['local_government_id', 'name']);
            $table->index(['local_government_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('volunteer_centers');
    }
};
