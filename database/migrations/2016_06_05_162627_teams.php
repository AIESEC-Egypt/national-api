<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Teams extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('teams', function(Blueprint $table){
            $table->increments('_internal_id');
            $table->integer('id')->unique()->unsigned()->nullable();
            $table->string('title');
            $table->enum('team_type', ['normal', 'eb']);
            $table->string('subtitle')->nullable();
            $table->integer('term_id')->unsigned();
            $table->foreign('term_id')->references('_internal_id')->on('terms')->onDelete('cascade')->onUpdate('cascade');
            $table->integer('department_id')->unsigned()->nullable();
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade')->onUpdate('cascade');
            $table->integer('function_id')->unsigned()->nullable();
            $table->foreign('function_id')->references('id')->on('functions')->onDelete('cascade')->onUpdate('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('teams');
    }
}
