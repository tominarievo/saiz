<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 支援団体と支援種別の中間テーブル。
 *
 */
class CreateOrganizationSeedsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('organization_seed', function (Blueprint $table) {
            $table->id();

            $table->integer('organization_id')->unsigned()->index();
            $table->integer('support_category2_id')->unsigned()->index();

            $table->integer('support_category1_id')->unsigned()->index();
            $table->text("comment")->nullable();

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
        Schema::dropIfExists('organization_seed');
    }
}
