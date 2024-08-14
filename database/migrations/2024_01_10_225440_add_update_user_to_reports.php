<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUpdateUserToReports extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->integer("update_user_id")->nullable()->index()->comment("更新ユーザーID");
            $table->boolean("updated_by_admin")->default(false)->index()->comment("代理入力フラグ");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn("update_user_id");
            $table->dropColumn("updated_by_admin");
        });
    }
}
