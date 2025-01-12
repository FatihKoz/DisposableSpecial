<?php

use App\Contracts\Migration;
use Illuminate\Support\Facades\Schema;

class AddFreeflightFinanceSetting extends Migration
{
    public function up()
    {
        if (Schema::hasTable('disposable_settings')) {
            // Minimum required amount for FreeFlights
            DB::table('disposable_settings')->updateOrInsert(
                [
                    'key' => 'dspecial.freeflights_reqbalance',
                ],
                [
                    'group'      => 'Free Flights',
                    'name'       => 'Required balance for flight',
                    'field_type' => 'numeric',
                    'default'    => '0',
                    'order'      => '1004',
                ]
            );
            // Cost of each FreeFlight save/update (no matter if it is performed or not)
            DB::table('disposable_settings')->updateOrInsert(
                [
                    'key' => 'dspecial.freeflights_costperedit',
                ],
                [
                    'group'      => 'Free Flights',
                    'name'       => 'Cost of each flight (save/update)',
                    'field_type' => 'numeric',
                    'default'    => '0',
                    'order'      => '1005',
                ]
            );
        }
    }
}
