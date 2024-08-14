<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 支援団体ビューでガントチャートの予定の表示重複をふせぐために表示する行位置を特定するインデックスの追加。
 */
class AddLineNoToPlans extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->integer("org_view_line_index")->default(0)->after("support_category1_id")->comment("支援団体ビューの予定行index");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn("org_view_line_index");
        });
    }
}
