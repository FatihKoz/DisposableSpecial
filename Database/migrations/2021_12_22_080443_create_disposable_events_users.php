<?php

use App\Contracts\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class CreateDisposableEventsUsers extends Migration
{

    public function up()
    {
        Schema::create('disposable_events_users', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('event_id');
            $table->integer('user_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('disposable_events_users');
    }
}
