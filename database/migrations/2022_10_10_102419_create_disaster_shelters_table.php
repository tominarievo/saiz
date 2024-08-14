<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 災害と支援先の中間テーブル
 */
class CreateDisasterSheltersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('disaster_shelter', function (Blueprint $table) {
            $table->id();

            $table->integer('disaster_id')->unsigned()->index();
            $table->integer('shelter_id')->unsigned()->index();

            $table->boolean("status")->default(false);

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
        Schema::dropIfExists('disaster_shelter');
    }
}
