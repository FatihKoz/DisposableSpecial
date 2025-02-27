<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up()
    {
        if (Schema::hasTable('disposable_settings')) {
            // New Settings for Mission Flights
            DB::table('disposable_settings')->updateOrInsert(
                [
                    'key' => 'turksim.missions_reward',
                ],
                [
                    'group'      => 'Missions',
                    'name'       => 'Reward Mission Flights',
                    'field_type' => 'check',
                    'default'    => false,
                    'order'      => '2001',
                ]
            );
            DB::table('disposable_settings')->updateOrInsert(
                [
                    'key' => 'turksim.missions_multiplier',
                ],
                [
                    'group'      => 'Missions',
                    'name'       => 'MF Multiplier',
                    'field_type' => 'numeric',
                    'default'    => 10,
                    'order'      => '2002',
                ]
            );
        }
    }
};
