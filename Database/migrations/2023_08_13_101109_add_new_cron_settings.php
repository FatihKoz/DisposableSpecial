<?php

use App\Contracts\Migration;
use Illuminate\Support\Facades\Schema;

class AddNewCronSettings extends Migration
{
    public function up()
    {
        if (Schema::hasTable('disposable_settings')) {
            // Tour Flight Visibility
            DB::table('disposable_settings')->updateOrInsert(['key' => 'dspecial.keep_tf_invisible'], ['group' => 'Cron', 'name' => 'Keep Tour Flights Invisible (Flights Page)', 'field_type' => 'check', 'default' => 'false', 'order' => '2005']);
        }
    }
}
