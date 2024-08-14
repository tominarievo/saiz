<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableDisastersCol3 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('disasters', function (Blueprint $table) {
            $table->text('npo_col_3')->nullable()->change();
        });

        Schema::table('shelters', function (Blueprint $table) {
            $table->text('npo_col_18')->nullable()->change();
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->text('npo_col_5')->nullable()->change();
            $table->text('npo_col_6')->nullable()->change();
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
            $table->string('npo_col_3')->nullable()->change();
        });

        Schema::table('shelters', function (Blueprint $table) {
            $table->string('npo_col_18')->nullable()->change();
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->string('npo_col_5')->nullable()->change();
            $table->string('npo_col_6')->nullable()->change();
        });
    }
}
