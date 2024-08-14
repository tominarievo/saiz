<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSupport2 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('support_category2s', function (Blueprint $table) {
            //
            $table->string("level1")->nullable();
            $table->string("level2")->nullable();
            $table->string("level3")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('support_category2s', function (Blueprint $table) {
            //
            $table->dropColumn("level1");
            $table->dropColumn("level2");
            $table->dropColumn("level3");
        });
    }
}
