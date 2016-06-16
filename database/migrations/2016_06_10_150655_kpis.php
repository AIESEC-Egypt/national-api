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
            $table->enum('type', ['tasks', 'teams', 'positions', 'persons']);
            $table->string('subtype')->nullable()->default(null);
            $table->morphs('measurable');
            $table->enum('unit', ['number', 'percentage', 'minutes']);
            $table->unique(['type', 'subtype', 'measurable_type', 'measurable_id']);
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
