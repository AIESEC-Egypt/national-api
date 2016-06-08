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
            $table->integer('id')->unique()->unsigned()->nullable();
            $table->string('email')->unique();
            $table->string('first_name');
            $table->string('middle_name')->nullable()->default(null);
            $table->string('last_name');
            $table->date('dob')->nullable()->default(null);
            $table->boolean('interviewed');
            $table->boolean('is_employee')->default(false);
            $table->string('profile_picture_url');
            $table->string('status')->nullable();
            $table->string('phone')->nullable();
            $table->dateTime('contacted_at')->nullable();
            $table->integer('contacted_by')->unsigned()->nullable();
            $table->foreign('contacted_by')->references('_internal_id')->on('persons')->onDelete('set null')->onUpdate('cascade');
            $table->string('cv_url')->nullable();
            $table->string('location')->nullable();
            $table->integer('nps_score')->nullable();
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
