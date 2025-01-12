<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up()
    {
        if (!Schema::hasTable('disposable_marketitems')) {
            Schema::create('disposable_marketitems', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name', 250);
                $table->unsignedInteger('price');
                $table->mediumtext('description')->nullable();
                $table->mediumtext('notes')->nullable();
                $table->string('image_url', 250)->nullable();
                $table->string('category', 10)->nullable();
                $table->unsignedInteger('dealer_id');
                $table->boolean('active')->default(1);
                $table->boolean('notifications')->default(0);
                $table->timestamps();
                $table->softdeletes();
            });
        }

        if (!Schema::hasTable('disposable_marketitem_owner')) {
            Schema::create('disposable_marketitem_owner', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('marketitem_id');
                $table->unsignedInteger('user_id');
            });
        }
    }
};
