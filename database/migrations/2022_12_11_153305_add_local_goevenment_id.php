<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLocalGoevenmentId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shelters', function (Blueprint $table) {
            $table->integer("prefecture_id")->unsigned()->nullable();
            $table->integer("local_government_id")->unsigned()->nullable();
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->integer("prefecture_id")->unsigned()->nullable();
            $table->integer("local_government_id")->unsigned()->nullable();
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
            $table->dropColumn("local_government_id");
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn("local_government_id");
        });
    }
}
