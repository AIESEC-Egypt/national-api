<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Persons extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('persons', function(Blueprint $table)
        {
            $table->increments('_internal_id');
            $table->integer('id')->unique();
            $table->string('email')->unique();
            $table->string('first_name');
            $table->string('middle_name')->nullable()->default(null);
            $table->string('last_name');
            $table->date('dob');
            $table->boolean('interviewed');
            $table->string('profile_picture_url');
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
        Schema::drop('persons');
    }
}