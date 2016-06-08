<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Tasks extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tasks', function(Blueprint $table)
        {
            $table->increments('id');
            $table->string('name');
            $table->integer('priority');
            $table->integer('person_id')->unsigned();
            $table->foreign('person_id')->references('_internal_id')->on('persons')->onDelete('cascade')->onUpdate('cascade');
            $table->integer('added_by')->unsigned();
            $table->foreign('added_by')->references('_internal_id')->on('persons')->onDelete('restrict')->onUpdate('cascade');
            $table->integer('estimated')->unsigned();
            $table->integer('needed')->unsigned()->nullable();
            $table->timestamp('due')->nullable();
            $table->boolean('done')->default(false);
            $table->timestamp('done_at')->nullable();
            $table->boolean('approved')->default(false);
            $table->integer('approved_by')->unsigned()->nullable();
            $table->foreign('approved_by')->references('_internal_id')->on('persons')->onDelete('restrict')->onUpdate('cascade');
            $table->timestamp('approved_at')->nullable();
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
        Schema::drop('tasks');
    }
}
