<?php

namespace Modules\DisposableSpecial\Listeners;

use App\Contracts\Listener;
use App\Events\PirepFiled;
use App\Events\PirepAccepted;
use App\Events\PirepRejected;
use App\Models\Enums\PirepSource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\DisposableSpecial\Models\DS_Maintenance;

class Gen_Maintenance extends Listener
{
    // Callback to proper method
    public static $callbacks = [
        PirepFiled::class    => 'handle_filed',
        PirepAccepted::class => 'handle_accepted',
        PirepRejected::class => 'handle_rejected',
    ];

    // Pirep Filed
    public function handle_filed(PirepFiled $event)
    {
        // Log::debug('Disposable Maintenance, Received PIREP FILED Event for Pirep ID:' . $event->pirep->id);
        $this->handle_accepted($event, 'FILED');
    }

    // Pirep Accepted
    public function handle_accepted($event, $op_type = null)
    {
        $pirep = $event->pirep;
        $aircraft = $pirep->aircraft;

        if (empty($aircraft) || empty($pirep->flight_time)) {
            Log::debug('Disposable Maintenance, Aircraft or Flight Time is empty for Pirep ID:' . $pirep->id . ' NOTHING DONE');
            return;
        }

        $maint = DS_Maintenance::firstOrNew(['aircraft_id' => $aircraft->id]);

        if (empty($op_type) && $maint->op_type === 'FILED') {
            Log::debug('Disposable Maintenance, Records already changed when Pirep ID:' . $pirep->id . ' was ' . $maint->op_type . ' NOTHING DONE');
            return;
        }

        // Operation Type
        if ($op_type != 'FILED') {
            $op_type = 'ACCEPTED';
        }
        $maint->op_type = $op_type;

        // Increase Times
        $maint->time_a = $maint->time_a + $pirep->flight_time;
        $maint->time_b = $maint->time_b + $pirep->flight_time;
        $maint->time_c = $maint->time_c + $pirep->flight_time;

        // Increase Cycles
        $maint->cycle_a = $maint->cycle_a + 1;
        $maint->cycle_b = $maint->cycle_b + 1;
        $maint->cycle_c = $maint->cycle_c + 1;

        // Decrease Current State
        $maint->curr_state = $maint->curr_state - round(($pirep->flight_time / 10000) + 0.01, 2);

        // Acars Specific Checks
        if ($pirep->source === PirepSource::ACARS) {
            // Read values from database directly
            $landing_rate = DB::table('pirep_field_values')->select('value')->where(['pirep_id' => $pirep->id, 'slug' => 'landing-rate'])->value('value');
            $landing_pitch = DB::table('pirep_field_values')->select('value')->where(['pirep_id' => $pirep->id, 'slug' => 'landing-pitch'])->value('value');
            $landing_roll = DB::table('pirep_field_values')->select('value')->where(['pirep_id' => $pirep->id, 'slug' => 'landing-roll'])->value('value');
            $takeoff_pitch = DB::table('pirep_field_values')->select('value')->where(['pirep_id' => $pirep->id, 'slug' => 'takeoff-pitch'])->value('value');
            $takeoff_roll = DB::table('pirep_field_values')->select('value')->where(['pirep_id' => $pirep->id, 'slug' => 'takeoff-roll'])->value('value');

            // Hard Landing
            if (is_numeric($landing_rate) && abs($landing_rate) > DS_Setting('turksim.maint_lndhard_limit', 500)) {
                $maint->curr_state = $maint->curr_state - round(abs($landing_rate) / 250, 2);
            } elseif (is_numeric($landing_rate)) {
                $maint->curr_state = $maint->curr_state - round(abs($landing_rate) / 10000, 2);
            }

            // Tail Strike (Landing)
            if (is_numeric($landing_pitch) && abs($landing_pitch) > $maint->limits->pitch) {
                $maint->curr_state = $maint->curr_state - round(abs($landing_pitch) / 10, 2);
            }

            // Tail Strike (TakeOff)
            if (is_numeric($takeoff_pitch) && abs($takeoff_pitch) > $maint->limits->pitch) {
                $maint->curr_state = $maint->curr_state - round(abs($takeoff_pitch) / 10, 2);
            }

            // Wing Strike (Landing)
            if (is_numeric($landing_roll) && abs($landing_roll) > $maint->limits->roll) {
                $maint->curr_state = $maint->curr_state - round(abs($landing_roll) / 10, 2);
            }

            // Wing Strike (TakeOff)
            if (is_numeric($takeoff_roll) && abs($takeoff_roll) > $maint->limits->roll) {
                $maint->curr_state = $maint->curr_state - round(abs($takeoff_roll) / 10, 2);
            }
        }

        // Save and ammend Log
        $maint->save();
        Log::info('Disposable Maintenance, Records for ' . $aircraft->registration . ' (ID:' . $aircraft->id . ') increased Pirep ID:' . $pirep->id . ' ' . $op_type);
    }

    // Pirep Rejected
    public function handle_rejected(PirepRejected $event)
    {
        $pirep = $event->pirep;
        $aircraft = $pirep->aircraft;

        if (empty($aircraft) || empty($pirep->flight_time)) {
            Log::debug('Disposable Maintenance, Aircraft or Flight Time is empty for Pirep ID:' . $pirep->id . ' NOTHING DONE');
            return;
        }

        $maint = DS_Maintenance::where('aircraft_id', $aircraft->id)->first();

        if (empty($maint)) {
            Log::debug('Disposable Maintenance, No Records found for ' . $aircraft->registration . ' (ID:' . $aircraft->id . ') NOTHING DONE');
            return;
        }

        // Decrease Times
        $maint->time_a = $maint->time_a - $pirep->flight_time;
        $maint->time_b = $maint->time_b - $pirep->flight_time;
        $maint->time_c = $maint->time_c - $pirep->flight_time;

        // Decrease Cycles
        $maint->cycle_a = $maint->cycle_a - 1;
        $maint->cycle_b = $maint->cycle_b - 1;
        $maint->cycle_c = $maint->cycle_c - 1;

        // Increase Current State
        $maint->curr_state = $maint->curr_state + round(($pirep->flight_time / 10000) + 0.01, 2);

        // Acars Specific Checks
        if ($pirep->source === PirepSource::ACARS) {
            // Read values from database directly
            $landing_rate = DB::table('pirep_field_values')->select('value')->where(['pirep_id' => $pirep->id, 'slug' => 'landing-rate'])->value('value');
            $landing_pitch = DB::table('pirep_field_values')->select('value')->where(['pirep_id' => $pirep->id, 'slug' => 'landing-pitch'])->value('value');
            $landing_roll = DB::table('pirep_field_values')->select('value')->where(['pirep_id' => $pirep->id, 'slug' => 'landing-roll'])->value('value');
            $takeoff_pitch = DB::table('pirep_field_values')->select('value')->where(['pirep_id' => $pirep->id, 'slug' => 'takeoff-pitch'])->value('value');
            $takeoff_roll = DB::table('pirep_field_values')->select('value')->where(['pirep_id' => $pirep->id, 'slug' => 'takeoff-roll'])->value('value');

            // Hard Landing
            if (is_numeric($landing_rate) && abs($landing_rate) > DS_Setting('turksim.maint_lndhard_limit', 500)) {
                $maint->curr_state = $maint->curr_state + round(abs($landing_rate) / 250, 2);
            } elseif (is_numeric($landing_rate)) {
                $maint->curr_state = $maint->curr_state + round(abs($landing_rate) / 10000, 2);
            }

            // Tail Strike (Landing)
            if (is_numeric($landing_pitch) && abs($landing_pitch) > $maint->limits->pitch) {
                $maint->curr_state = $maint->curr_state + round(abs($landing_pitch) / 10, 2);
            }

            // Tail Strike (TakeOff)
            if (is_numeric($takeoff_pitch) && abs($takeoff_pitch) > $maint->limits->pitch) {
                $maint->curr_state = $maint->curr_state + round(abs($takeoff_pitch) / 10, 2);
            }

            // Wing Strike (Landing)
            if (is_numeric($landing_roll) && abs($landing_roll) > $maint->limits->roll) {
                $maint->curr_state = $maint->curr_state + round(abs($landing_roll) / 10, 2);
            }

            // Wing Strike (TakeOff)
            if (is_numeric($takeoff_roll) && abs($takeoff_roll) > $maint->limits->roll) {
                $maint->curr_state = $maint->curr_state + round(abs($takeoff_roll) / 10, 2);
            }
        }

        // Operation Type
        $maint->act_note = null;
        $maint->act_start = null;
        $maint->act_end = null;
        $maint->op_type = 'REJECTED';

        // Check and Fix Aircraft Status
        if ($aircraft->status === 'M') {
            $aircraft->status = 'A';
            $aircraft->save();
        }

        // Save and ammend Log
        $maint->save();
        Log::info('Disposable Maintenance, Records for ' . $aircraft->registration . ' (ID:' . $aircraft->id . ') decreased Pirep ID:' . $pirep->id . ' REJECTED');
    }
}
