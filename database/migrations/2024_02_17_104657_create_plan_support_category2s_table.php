<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlanSupportCategory2sTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('plan_support_category2', function (Blueprint $table) {
            $table->id();

            $table->integer('plan_id')->unsigned()->index();
            $table->integer('support_category2_id')->unsigned()->index();

            $table->tinyInteger("signal")->default(1);
            $table->text("memo")->nullable();

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
        Schema::dropIfExists('plan_support_category2');
    }
}
