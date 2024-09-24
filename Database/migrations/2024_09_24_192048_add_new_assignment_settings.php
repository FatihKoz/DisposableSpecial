<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('disposable_settings')) {
            // New Settings for Assignments
            DB::table('disposable_settings')->updateOrInsert(
                ['key' => 'turksim.assignments_alwayshome'],
                ['group' => 'Assignments', 'name' => 'Always Return to Home', 'field_type' => 'check', 'default' => false, 'order' => '7835']
            );
            DB::table('disposable_settings')->updateOrInsert(
                ['key' => 'turksim.assignments_returnhome'],
                ['group' => 'Assignments', 'name' => 'Last Flight Returns to Home', 'field_type' => 'check', 'default' => false, 'order' => '7836']
            );
        }
    }
};
