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
        Schema::create('information', function (Blueprint $table) {
            $table->id();

            $table->boolean('status')->default(false);
            $table->timestamp('published_at')->nullable();

            $table->integer('information_category_id')->index()->unsigned()->nullable();
            $table->integer('information_sub_category_id')->index()->unsigned()->nullable();

            $table->boolean('is_pickup')->default(false);
            $table->string('title')->nullable();
            $table->mediumText('content')->nullable();

            $table->text('file_data')->nullable();

            $table->softDeletes();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('information');
    }
};
