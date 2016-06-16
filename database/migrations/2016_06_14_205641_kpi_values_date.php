<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class KpiValuesDate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('kpi_values_date', function(Blueprint $table) {
            $table->increments('id');
            $table->timestamp('date');
            $table->string('day', 10);
            $table->string('week', 7);
            $table->string('month', 7);
            $table->string('quarter', 6);
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('dayOfMonth');
            $table->unsignedTinyInteger('dayOfWeek');
            $table->unsignedTinyInteger('weekOfMonth');
            $table->unsignedTinyInteger('weekOfYear');
            $table->unsignedTinyInteger('monthOfYear');
            $table->unsignedTinyInteger('quarterOfYear');
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
        Schema::drop('kpi_values_date');
    }
}
