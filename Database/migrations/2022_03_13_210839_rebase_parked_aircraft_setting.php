<?php

use App\Contracts\Migration;
use Illuminate\Support\Facades\Schema;

class RebaseParkedAircraftSetting extends Migration
{
    public function up()
    {
        if (Schema::hasTable('disposable_settings')) {
            DB::table('disposable_settings')->updateOrInsert(
                [
                    'key' => 'dspecial.rebase_parked_aircraft',
                ],
                [
                    'group'      => 'Cron',
                    'name'       => 'Return Aircraft to Hubs (days)',
                    'field_type' => 'numeric',
                    'default'    => '0',
                    'order'      => '2005',
                ]
            );
        }
    }
}
