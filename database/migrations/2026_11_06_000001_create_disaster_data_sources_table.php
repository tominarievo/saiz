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
        Schema::create('disaster_data_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('disaster_id')->constrained('disasters')->cascadeOnDelete();
            $table->string('category', 32);
            $table->string('source_key', 40);
            $table->string('source_name')->nullable();
            $table->text('source_url')->nullable();
            $table->string('title')->nullable();
            $table->timestamp('collected_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['disaster_id', 'category', 'source_key'], 'disaster_data_sources_unique');
            $table->index(['disaster_id', 'category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('disaster_data_sources');
    }
};
