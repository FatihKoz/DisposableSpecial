<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\DisposableSpecial\Models\DS_Maintenance;

return new class() extends Migration {
    public function up()
    {
        // Add Remaining Time and Cycle Fields
        if (Schema::hasTable('disposable_maintenance')) {
            Schema::table('disposable_maintenance', function (Blueprint $table) {
                $table->mediumText('last_state')->nullable()->after('last_time');
                $table->integer('rem_ta')->nullable()->after('op_type');
                $table->integer('rem_tb')->nullable()->after('rem_ta');
                $table->integer('rem_tc')->nullable()->after('rem_tb');
                $table->integer('rem_ca')->nullable()->after('rem_tc');
                $table->integer('rem_cb')->nullable()->after('rem_ca');
                $table->integer('rem_cc')->nullable()->after('rem_cb');
            });
        }

        // Update All Maintenance Records
        if (Schema::hasTable('disposable_maintenance')) {
            $records = DS_Maintenance::orderBy('id')->get();

            foreach ($records as $record) {
                $record->rem_ta = $record->limits->time_a - $record->time_a;
                $record->rem_tb = $record->limits->time_b - $record->time_b;
                $record->rem_tc = $record->limits->time_c - $record->time_c;
                $record->rem_ca = $record->limits->cycle_a - $record->cycle_a;
                $record->rem_cb = $record->limits->cycle_b - $record->cycle_b;
                $record->rem_cc = $record->limits->cycle_c - $record->cycle_c;

                $record->save();
            }
        }
    }
};
