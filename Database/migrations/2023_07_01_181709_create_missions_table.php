<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('disposable_missions')) {
            Schema::create('disposable_missions', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('user_id')->nullable();
                $table->integer('aircraft_id')->nullable();
                $table->string('flight_id', 150)->nullable();
                $table->string('dpt_airport_id', 4)->nullable();
                $table->string('arr_airport_id', 4)->nullable();
                $table->smallInteger('mission_type');
                $table->smallInteger('mission_year');
                $table->smallInteger('mission_month');
                $table->unsignedInteger('mission_order');
                $table->timestamp('mission_valid')->nullable();
                $table->string('pirep_id', 150)->nullable();
                $table->timestamp('pirep_date')->nullable();
                $table->timestamps();
                $table->index('id');
                $table->unique('id');
            });
        }
    }
};
