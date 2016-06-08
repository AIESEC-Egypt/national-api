<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Terms extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('terms', function(Blueprint $table)
        {
            $table->increments('_internal_id');
            $table->integer('id')->unique()->unsigned()->nullable();
            $table->integer('entity_id')->unsigned();
            $table->foreign('entity_id')->references('_internal_id')->on('entities')->onDelete('cascade')->onUpdate('cascade');
            $table->enum('term_type', ['local', 'national']);
            $table->string('short_name');
            $table->date('start_date');
            $table->date('end_date');
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
        Schema::drop('terms');
    }
}
