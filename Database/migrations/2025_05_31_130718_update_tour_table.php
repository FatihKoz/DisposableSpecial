<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up()
    {
        // Add token id field
        if (Schema::hasTable('disposable_tours')) {
            Schema::table('disposable_tours', function (Blueprint $table) {
                $table->string('tour_fplremark', 100)->nullable()->after('tour_token');
            });
        }
    }
};
