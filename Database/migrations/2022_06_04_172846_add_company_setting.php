<?php

use App\Contracts\Migration;
use Illuminate\Support\Facades\Schema;

class AddCompanySetting extends Migration
{
    public function up()
    {
        if (Schema::hasTable('disposable_settings')) {
            // Monthly Assignments
            DB::table('disposable_settings')->updateOrInsert(
                [
                    'key' => 'turksim.assignments_usecompany',
                ],
                [
                    'group'      => 'Assignments',
                    'name'       => 'Use Pilot Company',
                    'field_type' => 'check',
                    'default'    => 'false',
                    'order'      => '7830',
                ]
            );
        }
    }
}
