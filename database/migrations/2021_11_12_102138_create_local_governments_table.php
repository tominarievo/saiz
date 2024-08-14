<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLocalGovernmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('local_governments', function (Blueprint $table) {
            $table->id();
            $table->integer('order')->default(0);
            $table->bigInteger('prefecture_id');
            $table->string('prefecture_code')->index();
            $table->string('code');
            $table->string('name');
            $table->tinyInteger('government_type')->index();
            $table->boolean('is_designated_city')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('local_governments');
    }
}
