<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class PersonsHomeEntity extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('persons', function(Blueprint $table) {
            $table->integer('home_entity')->unsigned()->nullable()->after('dob');
            $table->foreign('home_entity')->references('_internal_id')->on('entities')->onDelete('set null')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('persons', function(Blueprint $table) {
            $table->dropForeign('persons_home_entity_foreign');
            $table->dropColumn('home_entity');
        });
    }
}
