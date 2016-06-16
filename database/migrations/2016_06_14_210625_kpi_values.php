<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class KpiValues extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('kpi_values', function(Blueprint $table) {
            $table->increments('id');
            $table->integer('kpi_id')->unsigned();
            $table->foreign('kpi_id')->references('id')->on('kpis')->onDelete('cascade')->onUpdate('cascade');
            $table->integer('date_id')->unsigned();
            $table->foreign('date_id')->references('id')->on('kpi_values_date')->onDelete('cascade')->onUpdate('cascade');
            $table->float('value');
            $table->timestamp('calculated_at');
            $table->unique(['kpi_id', 'date_id']);
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
        Schema::drop('kpi_values');
    }
}
