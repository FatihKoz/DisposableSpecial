<?php

use App\Contracts\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCronSettings extends Migration
{
    public function up()
    {
        if (Schema::hasTable('disposable_settings')) {
            // Free Flights
            DB::table('disposable_settings')->updateOrInsert(['key' => 'dspecial.freeflights_main'], ['group' => 'Free Flights', 'name' => 'Web Based Free Flights', 'field_type' => 'check', 'default' => 'true', 'order' => '1001']);
            // Cron and Database Related
            DB::table('disposable_settings')->updateOrInsert(['key' => 'dspecial.database_cleanup'], ['group' => 'Cron', 'name' => 'Automated Database Cleaning', 'field_type' => 'check', 'default' => 'false', 'order' => '2001']);
            DB::table('disposable_settings')->updateOrInsert(['key' => 'dspecial.delete_nonflown_members'], ['group' => 'Cron', 'name' => 'Delete new members without flights (days)', 'field_type' => 'numeric', 'default' => '0', 'order' => '2002']);
            DB::table('disposable_settings')->updateOrInsert(['key' => 'dspecial.old_simbrief_ofp'], ['group' => 'Cron', 'name' => 'Delete old SimBrief OFP packs (days)', 'field_type' => 'numeric', 'default' => '0', 'order' => '2003']);
            DB::table('disposable_settings')->updateOrInsert(['key' => 'dspecial.old_acars_posreps'], ['group' => 'Cron', 'name' => 'Delete old Acars Position Reports (days)', 'field_type' => 'numeric', 'default' => '0', 'order' => '2004']);
        }
    }
}
