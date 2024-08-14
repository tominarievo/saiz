<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDisasters extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('disasters', function (Blueprint $table) {

            $table->boolean("status")->default(0);
            $table->boolean('is_catastrophic_disaster')->default(false)->comment('激甚災害');
            $table->timestamp('event_date')->nullable()->comment('発生日');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('disasters', function (Blueprint $table) {
            $table->dropColumn("status");
            $table->dropColumn("is_catastrophic_disaster");
            $table->dropColumn("event_date");
        });
    }
}
