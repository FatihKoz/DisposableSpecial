<?php

use App\Contracts\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDisposableEventsTables extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('disposable_events')) {
            Schema::create('disposable_events', function (Blueprint $table) {
                $table->increments('id');
                $table->string('event_name', 150);
                $table->string('event_code', 5);
                $table->text('event_desc')->nullable();
                $table->date('start_date');
                $table->date('end_date')->nullable();
                $table->boolean('public');
                $table->timestamps();

                $table->index('id');
                $table->unique('id');
            });
        }

        if (!Schema::hasTable('disposable_event_user')) {
            Schema::create('disposable_event_user', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('event_id');
                $table->integer('user_id');
            });
        }
    }
}
