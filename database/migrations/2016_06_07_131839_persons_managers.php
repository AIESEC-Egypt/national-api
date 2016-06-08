<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class PersonsManagers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('persons_managers', function(Blueprint $table) {
            $table->integer('person_id')->unsigned();
            $table->foreign('person_id')->references('_internal_id')->on('persons')->onDelete('cascade')->onUpdate('cascade');
            $table->integer('manager_id')->unsigned();
            $table->foreign('manager_id')->references('_internal_id')->on('persons')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('persons_managers');
    }
}
