<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOrnigazation extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $list = [
            "organizations",
            "disasters",
            "shelters",
            "support_category1s",
            "support_category2s",
        ];

        foreach ($list as $table_name) {
            Schema::table($table_name, function (Blueprint $table) {
                $table->string("npo_col_1")->nullable();
                $table->string("npo_col_2")->nullable();
                $table->string("npo_col_3")->nullable();
                $table->string("npo_col_4")->nullable();
                $table->string("npo_col_5")->nullable();
                $table->string("npo_col_6")->nullable();
                $table->string("npo_col_7")->nullable();
                $table->string("npo_col_8")->nullable();
                $table->string("npo_col_9")->nullable();
                $table->string("npo_col_10")->nullable();
                $table->string("npo_col_11")->nullable();
                $table->string("npo_col_12")->nullable();
                $table->string("npo_col_13")->nullable();
                $table->string("npo_col_14")->nullable();
                $table->string("npo_col_15")->nullable();
                $table->string("npo_col_16")->nullable();
                $table->string("npo_col_17")->nullable();
                $table->string("npo_col_18")->nullable();
                $table->string("npo_col_19")->nullable();
                $table->string("npo_col_20")->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $list = [
            "organizations",
            "disasters",
            "shelters",
            "support_category1s",
            "support_category2s",
        ];

        foreach ($list as $table_name) {

            Schema::table($table_name, function (Blueprint $table) {
                $table->dropColumn("npo_col_1");
                $table->dropColumn("npo_col_2");
                $table->dropColumn("npo_col_3");
                $table->dropColumn("npo_col_4");
                $table->dropColumn("npo_col_5");
                $table->dropColumn("npo_col_6");
                $table->dropColumn("npo_col_7");
                $table->dropColumn("npo_col_8");
                $table->dropColumn("npo_col_9");
                $table->dropColumn("npo_col_10");
                $table->dropColumn("npo_col_11");
                $table->dropColumn("npo_col_12");
                $table->dropColumn("npo_col_13");
                $table->dropColumn("npo_col_14");
                $table->dropColumn("npo_col_15");
                $table->dropColumn("npo_col_16");
                $table->dropColumn("npo_col_17");
                $table->dropColumn("npo_col_18");
                $table->dropColumn("npo_col_19");
                $table->dropColumn("npo_col_20");
            });
        }

    }
}
