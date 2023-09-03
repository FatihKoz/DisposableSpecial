<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('disposable_marketitem_owner')) {
            Schema::table('disposable_marketitem_owner', function (Blueprint $table) {
                $table->timestamps();
            });
        }

        // Update previous records with current datetime (backwards compatibility)
        if (Schema::hasTable('disposable_marketitem_owner')) {
            DB::table('disposable_marketitem_owner')->whereNull('created_at')->update(['created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
        }
    }
};
