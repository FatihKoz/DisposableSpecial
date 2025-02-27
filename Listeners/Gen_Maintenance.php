<?php

namespace Modules\DisposableSpecial\Listeners;

use App\Contracts\Listener;
use App\Events\PirepAccepted;
use App\Events\PirepFiled;
use App\Events\PirepRejected;
use App\Models\JournalTransaction;
use App\Models\Enums\AircraftStatus;
use App\Models\Enums\PirepSource;
use Illuminate\Support\Facades\Log;
use Modules\DisposableSpecial\Listeners\Expense_Maintenance;
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
        $this->handle_accepted($event, 'FILED');
    }

    // Pirep Accepted
    public function handle_accepted($event, $op_type = null)
    {
        $pirep = $event->pirep;
        $aircraft = $pirep->aircraft;

        if (empty($aircraft) || empty($pirep->flight_time)) {
            return;
        }
        $maint = DS_Maintenance::firstOrNew(['aircraft_id' => $aircraft->id]);

        if (empty($op_type) && $maint->op_type === 'FILED') {
            return;
        }
        // Operation Type
        if ($op_type != 'FILED') {
            $op_type = 'ACCEPTED';
        }
        $maint->op_type = $op_type;

        // Record current values as backup
        $maint->last_state = $maint->curr_state.','.$maint->cycle_a.','.$maint->cycle_b.','.$maint->cycle_c.','.$maint->time_a.','.$maint->time_b.','.$maint->time_c;

        // Increase Times
        $maint->time_a = $maint->time_a + $pirep->flight_time;
        $maint->time_b = $maint->time_b + $pirep->flight_time;
        $maint->time_c = $maint->time_c + $pirep->flight_time;
        // Increase Cycles
        $maint->cycle_a = $maint->cycle_a + 1;
        $maint->cycle_b = $maint->cycle_b + 1;
        $maint->cycle_c = $maint->cycle_c + 1;
        // Decrease Remaining Times
        $maint->rem_ta = $maint->limits->time_a - $maint->time_a;
        $maint->rem_tb = $maint->limits->time_b - $maint->time_b;
        $maint->rem_tc = $maint->limits->time_c - $maint->time_c;
        // Decrease Remaining Cycles
        $maint->rem_ca = $maint->limits->cycle_a - $maint->cycle_a;
        $maint->rem_cb = $maint->limits->cycle_b - $maint->cycle_b;
        $maint->rem_cc = $maint->limits->cycle_c - $maint->cycle_c;
        // Decrease Current State
        $maint->curr_state = $maint->curr_state - round(($pirep->flight_time / 10000) + 0.01, 2);
        // Acars Specific Checks
        if ($pirep->source === PirepSource::ACARS) {
            // Read values from pirep fields
            $landing_rate = optional($pirep->fields->where('slug', 'landing-rate')->first())->value;
            $landing_pitch = optional($pirep->fields->where('slug', 'landing-pitch')->first())->value;
            $landing_roll = optional($pirep->fields->where('slug', 'landing-roll')->first())->value;
            $takeoff_pitch = optional($pirep->fields->where('slug', 'takeoff-pitch')->first())->value;
            $takeoff_roll = optional($pirep->fields->where('slug', 'takeoff-roll')->first())->value;

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
        Log::info('Disposable Special | Maintenance Records for '.$aircraft->registration.' (ID:'.$aircraft->id.') increased Pirep ID:'.$pirep->id.' '.$op_type);

        // Check Remaining Cycle and Time to start Periodic Maintenance, also do the Line Check if needed
        if ($maint->rem_cc < 1 || $maint->rem_tc < 1) {
            $maintsys = app(Expense_Maintenance::class);
            $maintsys->MaintenanceChecks('C Check', $aircraft, false, DS_Setting('turksim.maint_acstate_control', false));
            Log::info('Disposable Special | C Check started for '.$aircraft->registration.' (ID:'.$aircraft->id.')');
        } elseif ($maint->rem_cb < 1 || $maint->rem_tb < 1) {
            $maintsys = app(Expense_Maintenance::class);
            $maintsys->MaintenanceChecks('B Check', $aircraft, false, DS_Setting('turksim.maint_acstate_control', false));
            Log::info('Disposable Special | B Check started for '.$aircraft->registration.' (ID:'.$aircraft->id.')');
        } elseif ($maint->rem_ca < 1 || $maint->rem_ta < 1) {
            $maintsys = app(Expense_Maintenance::class);
            $maintsys->MaintenanceChecks('A Check', $aircraft, false, DS_Setting('turksim.maint_acstate_control', false));
            Log::info('Disposable Special | A Check started for '.$aircraft->registration.' (ID:'.$aircraft->id.')');
        } elseif ($maint->curr_state < 75) {
            $maintsys = app(Expense_Maintenance::class);
            $maintsys->MaintenanceChecks('Line Check', $aircraft, false, DS_Setting('turksim.maint_acstate_control', false));
            Log::info('Disposable Special | Line Check started for '.$aircraft->registration.' (ID:'.$aircraft->id.')');
        } else {
            Log::info('Disposable Special | No Maintenance needed for '.$aircraft->registration.' (ID:'.$aircraft->id.')');
        }
    }

    // Pirep Rejected
    public function handle_rejected(PirepRejected $event)
    {
        $pirep = $event->pirep;
        $aircraft = $pirep->aircraft;

        if (empty($aircraft) || empty($pirep->flight_time)) {
            return;
        }
        $maint = DS_Maintenance::where('aircraft_id', $aircraft->id)->first();

        if (empty($maint)) {
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
        // Increase Remaining Times
        $maint->rem_ta = $maint->rem_ta + $pirep->flight_time;
        $maint->rem_tb = $maint->rem_tb + $pirep->flight_time;
        $maint->rem_tc = $maint->rem_tc + $pirep->flight_time;
        // Increase Remaining Cycles
        $maint->rem_ca = $maint->rem_ca + 1;
        $maint->rem_cb = $maint->rem_cb + 1;
        $maint->rem_cc = $maint->rem_cc + 1;
        // Increase Current State
        $maint->curr_state = $maint->curr_state + round(($pirep->flight_time / 10000) + 0.01, 2);
        // Acars Specific Checks
        if ($pirep->source === PirepSource::ACARS) {
            // Read values from pirep fields
            $landing_rate = optional($pirep->fields->where('slug', 'landing-rate')->first())->value;
            $landing_pitch = optional($pirep->fields->where('slug', 'landing-pitch')->first())->value;
            $landing_roll = optional($pirep->fields->where('slug', 'landing-roll')->first())->value;
            $takeoff_pitch = optional($pirep->fields->where('slug', 'takeoff-pitch')->first())->value;
            $takeoff_roll = optional($pirep->fields->where('slug', 'takeoff-roll')->first())->value;

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

        // Revert back the financials if needed
        $transaction = JournalTransaction::where('ref_model_id', $aircraft->id)
            ->where('memo', 'LIKE', '%'.$maint->act_note.'%')
            ->where('ref_model', 'LIKE', '%Aircraft')
            ->where('post_date', $maint->updated_at->format('Y-m-d'))->first();

        if ($transaction) {
            Log::info('Disposable Special | Financial Records for '.$aircraft->registration.' (ID:'.$aircraft->id.') maintenance action deleted');
            $transaction->delete();
        }

        // Operation Type
        $maint->act_note = null;
        $maint->act_start = null;
        $maint->act_end = null;
        $maint->op_type = 'REJECTED';
        // Check and Fix Aircraft Status
        if ($aircraft->status === AircraftStatus::MAINTENANCE) {
            $aircraft->status = AircraftStatus::ACTIVE;
            Log::info('Disposable Special | '.$aircraft->registration.' (ID:'.$aircraft->id.') Released back to service');
            $aircraft->save();
        }

        // Save and ammend Log
        $maint->save();
        Log::info('Disposable Special | Maintenance Records for '.$aircraft->registration.' (ID:'.$aircraft->id.') decreased Pirep ID:'.$pirep->id.' REJECTED');
    }
}
