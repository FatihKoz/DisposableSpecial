<?php

use App\Contracts\Migration;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up()
    {
        if (Schema::hasTable('disposable_settings')) {
            // Settings for Hourly Local Database Backup
            DB::table('disposable_settings')->updateOrInsert(
                ['key' => 'dspecial.database_backup'],
                ['group' => 'Cron', 'name' => 'Hourly Local DB Backup', 'field_type' => 'check', 'default' => 'false', 'order' => '2010']
            );
        }
    }
};
