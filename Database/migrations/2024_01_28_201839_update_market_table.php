<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up()
    {
        // Convert price to decimal to allow more precision
        if (Schema::hasTable('disposable_marketitems')) {
            Schema::table('disposable_marketitems', function (Blueprint $table) {
                $table->unsignedDecimal('price', $precision = 12, $scale = 2)->change();
            });
        }
    }
};
