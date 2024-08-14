<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlanCommentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('plan_comments', function (Blueprint $table) {
            $table->id();

            $table->integer('plan_id')->unsigned()->index();
            $table->integer('user_id')->unsigned()->index();
            $table->timestamp('post_datetime')->nullable();
            $table->text('comment')->nullable();

            $table->softDeletes();
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
        Schema::dropIfExists('plan_comments');
    }
}
