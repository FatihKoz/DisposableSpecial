<?php

use App\Contracts\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class HandleDisposableAssignments extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('turksim_assignments') && !Schema::hasTable('disposable_assignments')) {
            Schema::create('turksim_assignments', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('user_id');
                $table->smallInteger('assignment_year');
                $table->smallInteger('assignment_month');
                $table->smallInteger('assignment_order');
                $table->string('flight_id', 150);
                $table->string('pirep_id', 150)->nullable();
                $table->timestamp('pirep_date')->nullable();
                $table->timestamps();
                $table->index('id');
                $table->unique('id');
            });
        }

        if (Schema::hasTable('turksim_assignments') && !Schema::hasTable('disposable_assignments')) {
            Schema::table('turksim_assignments', function (Blueprint $table) {
                $table->dropIndex('turksim_assignments_id_index');
                $table->dropUnique('turksim_assignments_id_unique');
            });

            Schema::rename('turksim_assignments', 'disposable_assignments');

            Schema::table('disposable_assignments', function (Blueprint $table) {
                $table->index('id');
                $table->unique('id');
            });
        }

        if (Schema::hasTable('disposable_settings')) {
            // TurkSim Flight Assignments
            DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.assignments_auto'], ['group' => 'Assignments', 'name' => 'Auto Assign Flights', 'field_type' => 'check', 'default' => 'false', 'order' => '7801']);
            DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.assignments_count'], ['group' => 'Assignments', 'name' => 'Monthly Flight Count', 'field_type' => 'numeric', 'default' => '4', 'order' => '7811']);
            DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.assignments_flown'], ['group' => 'Assignments', 'name' => 'Avoid Already Flown Flights', 'field_type' => 'check', 'default' => 'false', 'order' => '7821']);
            DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.assignments_tours'], ['group' => 'Assignments', 'name' => 'Avoid Tour Flights', 'field_type' => 'check', 'default' => 'false', 'order' => '7822']);
            DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.assignments_usehubs'], ['group' => 'Assignments', 'name' => 'Use Hubs as Starting Point', 'field_type' => 'check', 'default' => 'false', 'order' => '7831']);
            DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.assignments_avgtime'], ['group' => 'Assignments', 'name' => 'Use Average Flight Times', 'field_type' => 'check', 'default' => 'false', 'order' => '7832']);
            DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.assignments_margin'], ['group' => 'Assignments', 'name' => 'Average Time Margin (mins)', 'field_type' => 'numeric', 'default' => '30', 'order' => '7833']);
            DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.assignments_preficao'], ['group' => 'Assignments', 'name' => 'Use Preferred ICAO Types', 'field_type' => 'check', 'default' => 'false', 'order' => '7834']);
            DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.assignments_reward'], ['group' => 'Assignments', 'name' => 'Reward Flown Assignments', 'field_type' => 'check', 'default' => 'false', 'order' => '7861']);
            DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.assignments_multiplier'], ['group' => 'Assignments', 'name' => 'Reward Multiplier', 'field_type' => 'numeric', 'default' => '10', 'order' => '7862']);
            DB::table('disposable_settings')->updateOrInsert(['key' => 'turksim.assignments_months'], ['group' => 'Assignments', 'name' => 'Frontend Visible Months', 'field_type' => 'select', 'default' => '4', 'options' => '1,2,3,4,5,6,7,8,9,10,11,12', 'order' => '7891']);
        }
    }
}
