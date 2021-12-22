<?php

use App\Contracts\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class CreateDisposableEvents extends Migration
{

    public function up()
    {
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

    public function down()
    {
        Schema::dropIfExists('disposable_events');
    }
}
