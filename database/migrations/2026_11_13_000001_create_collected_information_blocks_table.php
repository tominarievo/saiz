<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collected_information_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('disaster_id')->constrained('disasters')->cascadeOnDelete();
            $table->string('type', 32);
            $table->string('block_key', 2048);
            $table->string('block_hash', 64);
            $table->string('source_url', 2048)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->unique(['disaster_id', 'type', 'block_hash'], 'collected_blocks_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collected_information_blocks');
    }
};
