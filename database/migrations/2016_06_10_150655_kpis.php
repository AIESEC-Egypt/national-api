<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Kpis extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('kpis', function(Blueprint $table) {
            $table->increments('id');
            $table->enum('type', ['tasks']);
            $table->string('subtype')->nullable()->default(null);
            $table->integer('person_id')->unsigned();
            $table->foreign('person_id')->references('_internal_id')->on('persons')->onDelete('cascade')->onUpdate('cascade');
            $table->enum('unit', ['number', 'percentage', 'minutes']);
            $table->unique(['type', 'subtype', 'person_id']);
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
        Schema::drop('kpis');
    }
}
