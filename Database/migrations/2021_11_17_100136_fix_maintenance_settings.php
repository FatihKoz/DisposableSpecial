<?php

use App\Contracts\Migration;
use Illuminate\Support\Facades\Schema;

class FixMaintenanceSettings extends Migration
{
    public function up()
    {
        if (Schema::hasTable('disposable_settings')) {
            // Maintenance - Delete Deprecated Settings
            DB::table('disposable_settings')->where('key', 'turksim.expense_maint_lndhard')->delete();
            DB::table('disposable_settings')->where('key', 'turksim.expense_maint_lndsoft')->delete();
            DB::table('disposable_settings')->where('key', 'turksim.expense_maint_lnduser')->delete();
            DB::table('disposable_settings')->where('key', 'turksim.expense_maint_lndhard_limit')->delete();
            DB::table('disposable_settings')->where('key', 'turksim.expense_maint_lndsoft_limit')->delete();
        }
    }
}
