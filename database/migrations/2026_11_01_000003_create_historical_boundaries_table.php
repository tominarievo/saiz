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
        Schema::create('historical_boundaries', function (Blueprint $table) {
            $table->id();
            $table->string('prefecture_code', 2)->index();
            $table->string('prefecture_name');
            $table->date('boundary_date')->index();
            $table->string('resolution', 1)->default('h');
            $table->string('source');
            $table->string('license');
            $table->json('bbox')->nullable();
            $table->longText('topojson');
            $table->unsignedInteger('feature_count')->default(0);
            $table->timestamps();

            $table->unique(['prefecture_code', 'boundary_date', 'resolution'], 'historical_boundaries_pref_date_res_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historical_boundaries');
    }
};
