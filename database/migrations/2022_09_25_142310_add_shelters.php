<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddShelters extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shelters', function (Blueprint $table) {

            $table->boolean("status")->default(0);
            $table->boolean("is_designated")->default(0)->comment('指定避難所');
            $table->string("representative")->nullable()->comment('代表者');

            $table->double("lat")->default(0)->comment('緯度');
            $table->double("lng")->default(0)->comment('経度');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shelters', function (Blueprint $table) {

            $table->dropColumn("status");
            $table->dropColumn("is_designated");
            $table->dropColumn("representative");

            $table->dropColumn("lat");
            $table->dropColumn("lat");

        });
    }
}
