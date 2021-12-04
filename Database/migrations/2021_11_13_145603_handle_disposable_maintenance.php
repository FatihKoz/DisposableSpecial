<?php

use App\Contracts\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class HandleDisposableMaintenance extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('turksim_maintenance') && !Schema::hasTable('disposable_maintenance')) {
            Schema::create('turksim_maintenance', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('aircraft_id');
                $table->decimal('curr_state', $precision = 5, $scale = 2);
                $table->integer('time_a')->nullable();
                $table->integer('time_b')->nullable();
                $table->integer('time_c')->nullable();
                $table->integer('cycle_a')->nullable();
                $table->integer('cycle_b')->nullable();
                $table->integer('cycle_c')->nullable();
                $table->string('act_note', 100)->nullable();
                $table->timestamp('act_start')->nullable();
                $table->timestamp('act_end')->nullable();
                $table->timestamp('last_a')->nullable();
                $table->timestamp('last_b')->nullable();
                $table->timestamp('last_c')->nullable();
                $table->string('last_note', 100)->nullable();
                $table->timestamp('last_time')->nullable();
                $table->string('op_type', 20)->nullable();
                $table->timestamps();
                $table->index('id');
                $table->unique('id');
                $table->unique('aircraft_id');
            });
        }

        if (Schema::hasTable('turksim_maintenance') && !Schema::hasTable('disposable_maintenance')) {
            $maint_check = DB::table('turksim_maintenance')->count('id');

            if ($maint_check == 0) {
                $utc_now = Carbon::now('UTC');

                $aircraft_array = DB::table('aircraft')->pluck('id')->toArray();
                $pirep_records = DB::table('pireps')
                    ->select('aircraft_id')
                    ->selectRaw('sum(flight_time) as tot_time')->selectRaw('count(id) as tot_cycle')
                    ->where(['state' => 2, 'status' => 'ONB'])
                    ->whereIn('aircraft_id', $aircraft_array)
                    ->groupBy('aircraft_id')
                    ->get();

                $records = [];
                foreach ($pirep_records as $maint) {
                    $records[] = [
                        'aircraft_id' => $maint->aircraft_id,
                        'curr_state'  => 100 - (round($maint->tot_time / 10000, 2) + round($maint->tot_cycle / 100, 2)),
                        'time_a'      => $maint->tot_time,
                        'time_b'      => $maint->tot_time,
                        'time_c'      => $maint->tot_time,
                        'cycle_a'     => $maint->tot_cycle,
                        'cycle_b'     => $maint->tot_cycle,
                        'cycle_c'     => $maint->tot_cycle,
                        'created_at'  => $utc_now,
                    ];
                }

                // DB::table('turksim_maintenance')->truncate();
                DB::table('turksim_maintenance')->insert($records);
            }
        }

        if (Schema::hasTable('turksim_maintenance') && !Schema::hasTable('disposable_maintenance')) {
            Schema::table('turksim_maintenance', function (Blueprint $table) {
                $table->dropIndex(['id']);
                $table->dropUnique(['id']);
                $table->dropUnique(['aircraft_id']);
            });

            Schema::rename('turksim_maintenance', 'disposable_maintenance');

            Schema::table('disposable_maintenance', function (Blueprint $table) {
                $table->index('id');
                $table->unique('id');
                $table->unique('aircraft_id');
            });
        }

        if (Schema::hasTable('disposable_settings')) {
            $update_check = DB::table('disposable_settings')->where('key', 'turksim.maint_lndhard')->count();
            // Maintenance - Rename Old Settings
            if ($update_check == 0) {
                DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.expense_maint_lndhard'], ['key' => 'turksim.maint_lndhard', 'group' => 'Maintenance', 'name' => 'Hard Landing Check', 'field_type' => 'check', 'default' => 'false', 'order' => '9001']);
                DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.expense_maint_lndsoft'], ['key' => 'turksim.maint_lndsoft', 'group' => 'Maintenance', 'name' => 'Soft Landing Check', 'field_type' => 'check', 'default' => 'false', 'order' => '9002']);
                DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.expense_maint_lnduser'], ['key' => 'turksim.maint_chguser', 'group' => 'Maintenance', 'name' => 'Charge Pilot For Checks', 'field_type' => 'check', 'default' => 'false', 'order' => '9005']);
                DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.expense_maint_lndhard_limit'], ['key' => 'turksim.maint_lndhard_limit', 'group' => 'Maintenance', 'name' => 'Hard Landing Limit', 'field_type' => 'numeric', 'default' => '500', 'order' => '9101']);
                DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.expense_maint_lndsoft_limit'], ['key' => 'turksim.maint_lndsoft_limit', 'group' => 'Maintenance', 'name' => 'Soft Landing Limit', 'field_type' => 'numeric', 'default' => '50', 'order' => '9102']);
            }
            // Maintenance - Add New Settings
            DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.maint_strtail'], ['group' => 'Maintenance', 'name' => 'Tail Strike Check', 'field_type' => 'check', 'default' => 'false', 'order' => '9003']);
            DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.maint_strwing'], ['group' => 'Maintenance', 'name' => 'Engine/Wing Strike Check', 'field_type' => 'check', 'default' => 'false', 'order' => '9004']);
            DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.maint_strtail_limit'], ['group' => 'Maintenance', 'name' => 'Tail Strike Max. Pitch', 'field_type' => 'decimal', 'default' => '15.00', 'order' => '9103']);
            DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.maint_strwing_limit'], ['group' => 'Maintenance', 'name' => 'Engine/Wing Strike Max. Roll', 'field_type' => 'decimal', 'default' => '10.00', 'order' => '9104']);
            DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.maint_unitrate'], ['group' => 'Maintenance', 'name' => 'Base Unit Rate (per wgt)', 'field_type' => 'decimal', 'default' => '0.3775', 'order' => '9201']);
            DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.maint_acstate_control'], ['group' => 'Maintenance', 'name' => 'Control Aircraft Status', 'field_type' => 'check', 'default' => 'false', 'order' => '9006']);
            DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.maint_hours_gen'], ['group' => 'Maintenance', 'name' => 'Duration For Generic Checks', 'field_type' => 'decimal', 'default' => '1.00', 'order' => '9301']);
            DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.maint_hours_a'], ['group' => 'Maintenance', 'name' => 'A Check Duration', 'field_type' => 'decimal', 'default' => '10.00', 'order' => '9313']);
            DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.maint_hours_b'], ['group' => 'Maintenance', 'name' => 'B Check Duration', 'field_type' => 'decimal', 'default' => '48.00', 'order' => '9323']);
            DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.maint_hours_c'], ['group' => 'Maintenance', 'name' => 'C Check Duration', 'field_type' => 'decimal', 'default' => '120.00', 'order' => '9333']);
            DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.maint_lim_at'], ['group' => 'Maintenance', 'name' => 'A Check Time Limit', 'field_type' => 'numeric', 'default' => '250', 'order' => '9311']);
            DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.maint_lim_ac'], ['group' => 'Maintenance', 'name' => 'A Check Cycle Limit', 'field_type' => 'numeric', 'default' => '500', 'order' => '9312']);
            DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.maint_lim_bt'], ['group' => 'Maintenance', 'name' => 'B Check Time Limit', 'field_type' => 'numeric', 'default' => '500', 'order' => '9321']);
            DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.maint_lim_bc'], ['group' => 'Maintenance', 'name' => 'B Check Cycle Limit', 'field_type' => 'numeric', 'default' => '1000', 'order' => '9322']);
            DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.maint_lim_ct'], ['group' => 'Maintenance', 'name' => 'C Check Time Limit', 'field_type' => 'numeric', 'default' => '2500', 'order' => '9331']);
            DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.maint_lim_cc'], ['group' => 'Maintenance', 'name' => 'C Check Cycle Limit', 'field_type' => 'numeric', 'default' => '5000', 'order' => '9332']);
            // Maintenance - Delete Deprecated Settings
            DB::table('disposable_settings')->where('key', 'turksim.expense_maint_lndhard_cost')->delete();
            DB::table('disposable_settings')->where('key', 'turksim.expense_maint_lndsoft_cost')->delete();
        }
    }
}
