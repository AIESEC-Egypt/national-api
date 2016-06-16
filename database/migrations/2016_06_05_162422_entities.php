<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Entities extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('entities', function(Blueprint $table)
        {
            $table->increments('_internal_id');
            $table->integer('id')->unique()->unsigned()->nullable();
            $table->string('name');
            $table->string('full_name');
            $table->string('email')->nullable();
            $table->integer('parent_id')->unsigned()->nullable();
            $table->foreign('parent_id')->references('_internal_id')->on('entities')->onDelete('cascade')->onUpdate('cascade');
            $table->enum('type', ['lc', 'mc', 'region', 'ai']);
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
        Schema::drop('entities');
    }
}
