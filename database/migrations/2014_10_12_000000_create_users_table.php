<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');

            $table->boolean('is_valid')->default(false);
            $table->boolean('is_system_admin')->default(false);

            $table->integer('organization_id')->unsigned()->nullable();
            $table->foreign('organization_id')->references('id')->on('organizations');

            $table->string('username', 190);
            $table->integer('role_id')->unsigned()->nullable();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('password');
            $table->boolean('is_draft_password')->default(false);

            $table->rememberToken();

            $table->softDeletes();
            $table->timestamps();

            $table->integer('created_by')->unsigned()->nullable();

            $table->integer('updated_by')->unsigned()->nullable();

            $table->integer('deleted_by')->unsigned()->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
