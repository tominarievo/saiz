<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlanCommentReadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('plan_comment_reads', function (Blueprint $table) {
            $table->id();

            $table->integer('plan_comment_id')->unsigned()->index();
            $table->integer('plan_id')->unsigned()->index();
            $table->integer('user_id')->unsigned()->index();
            $table->boolean('read_status')->default(false);
            $table->timestamp('read_timestamp')->nullable();

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
        Schema::dropIfExists('plan_comment_reads');
    }
}
