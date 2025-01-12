<?php

use App\Contracts\Migration;
use Illuminate\Support\Facades\Schema;

class AddFreeflightCompanySetting extends Migration
{
    public function up()
    {
        if (Schema::hasTable('disposable_settings')) {
            // Monthly Assignments
            DB::table('disposable_settings')->updateOrInsert(
                [
                    'key' => 'dspecial.freeflights_companyfleet',
                ],
                [
                    'group'      => 'Free Flights',
                    'name'       => 'Allow fleet of selected Airline only',
                    'field_type' => 'check',
                    'default'    => 'false',
                    'order'      => '1002',
                ]
            );
        }
    }
}
