<?php

use App\Contracts\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class() extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('disposable_events')) {
            Schema::create('disposable_events', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name', 250);
                $table->unsignedInteger('type');
                $table->string('thumb_url')->nullable();
                $table->string('image_url')->nullable();
                $table->text('desc')->nullable();
                $table->text('rules')->nullable();
                $table->boolean('public')->default(true);
                $table->boolean('active')->default(false);
                $table->boolean('visible')->default(false);
                $table->datetime('start_at');
                $table->datetime('end_at');
                $table->timestamps();

                $table->index('id');
                $table->unique('id');
            });
        }

        if (!Schema::hasTable('disposable_event_meta')) {
            Schema::create('disposable_event_meta', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('event_id');
                $table->string('name');
                $table->string('slug');
                $table->string('value');
                $table->timestamps();

                $table->index('id');
                $table->unique('id');
            });
        }

        if (!Schema::hasTable('disposable_event_users')) {
            Schema::create('disposable_event_users', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('event_id');
                $table->unsignedInteger('user_id');
                $table->boolean('attend')->nullable();
                $table->boolean('completed')->default(false);
                $table->timestamps();

                $table->index('id');
                $table->unique('id');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('disposable_events');
        Schema::dropIfExists('disposable_event_users');
        Schema::dropIfExists('disposable_event_meta');
    }
};
