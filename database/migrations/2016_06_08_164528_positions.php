<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Positions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('positions', function(Blueprint $table) {
            $table->increments('_internal_id');
            $table->integer('id')->unsigned()->unique();
            $table->string('position_name')->nullable();
            $table->string('position_short_name')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('person_id')->unsigned()->nullable();
            $table->foreign('person_id')->references('_internal_id')->on('persons')->onDelete('set null')->onUpdate('cascade');
            $table->integer('team_id')->unsigned();
            $table->foreign('team_id')->references('_internal_id')->on('teams')->onDelete('cascade')->onUpdate('cascade');
            $table->integer('parent_id')->unsigned()->nullable();
            $table->foreign('parent_id')->references('_internal_id')->on('positions')->onDelete('cascade')->onUpdate('cascade');
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
        Schema::drop('positions');
    }
}
