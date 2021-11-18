<?php

namespace Modules\DisposableSpecial\Listeners;

use App\Events\PirepFiled;
use App\Models\PirepComment;
use App\Models\Enums\PirepState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class Gen_Comments
{
    // Auto Comment To Pireps
    public function handle(PirepFiled $event)
    {
        $comments = DS_Setting('turksim.auto_comment', false);
        $check_times = DS_Setting('turksim.auto_comment_times', true);
        $poster = DS_Setting('turksim.auto_comment_user', false);

        if ($comments === false) {
            return;
        }

        // Pick A Random Admin
        if ($poster === false) {
            $adm_users = DB::table('role_user')->where('role_id', function ($query) {
                return $query->select('id')->from('roles')->where('name', 'admin')->limit(1);
            })->pluck('user_id');
            $poster = $adm_users->random();
        }

        $pirep = $event->pirep;
        $pirep->loadMissing('aircraft', 'simbrief');
        $aircraft = $pirep->aircraft;
        $simbrief = $pirep->simbrief;
        $pirep_comments = collect();

        // Read necessary field values (from pirep attributes)
        $act_tow = optional($pirep->fields->where('slug', 'takeoff-weight')->first())->value;
        $act_ldw = optional($pirep->fields->where('slug', 'landing-weight')->first())->value;
        $act_lfuel = optional($pirep->fields->where('slug', 'landing-fuel')->first())->value;

        /*
        // Read necessary field values (from db table)
        $act_tow = DB::table('pirep_field_values')->where(['pirep_id' => $pirep->id, 'slug' => 'takeoff-weight'])->value('value');
        $act_ldw = DB::table('pirep_field_values')->where(['pirep_id' => $pirep->id, 'slug' => 'landing-weight'])->value('value');
        $act_lfuel = DB::table('pirep_field_values')->where(['pirep_id' => $pirep->id, 'slug' => 'landing-fuel'])->value('value');
        */

        // Generic Pirep checks
        if ($pirep->fuel_used < 5) {
            $pirep_comments->put('gen_nofuel', 'Reject Reason: Non Reliable or Missing Fuel Information');
            $pirep_state = PirepState::REJECTED;
        }

        if ($pirep->flight_time < 5) {
            $pirep_comments->put('gen_notime', 'Reject Reason: Non Reliable or Missing Block Time Information');
            $pirep_state = PirepState::REJECTED;
        }

        if (!$aircraft) {
            $pirep_comments->put('gen_noac', 'Reject Reason: No Aircraft Registration Provided');
            $pirep_state = PirepState::REJECTED;
        }

        // SimBrief Related Checks
        if ($simbrief && $aircraft) {
            // Match Weights
            if ($simbrief->xml->params->units == 'kgs') {
                $block_fuel = round($pirep->block_fuel / 2.20462262185, 2);
                $fuel_used = round($pirep->fuel_used / 2.20462262185, 2);
                $landing_fuel = is_numeric($act_lfuel) ? round($act_lfuel / 2.20462262185, 2) : round($block_fuel - $fuel_used, 2);
                $act_tow = is_numeric($act_tow) ? round($act_tow / 2.20462262185) : null;
                $act_ldw = is_numeric($act_ldw) ? round($act_ldw / 2.20462262185) : null;
            } else {
                $block_fuel = round($pirep->block_fuel, 2);
                $fuel_used = round($pirep->fuel_used, 2);
                $landing_fuel = is_numeric($act_lfuel) ? round($act_lfuel, 2) : round($block_fuel - $fuel_used, 2);
                $act_tow = is_numeric($act_tow) ? round($act_tow) : null;
                $act_ldw = is_numeric($act_ldw) ? round($act_ldw) : null;
            }

            if ($check_times === true) {
                // Get Departure and Arrival Times
                $dla_margin = DS_Setting('turksim.auto_comment_dlamargin', 20);

                // Read field values (from pirep attributes)
                $blocks_off = optional($pirep->fields->where('slug', 'blocks-off-time-real')->first())->value;
                $blocks_on = optional($pirep->fields->where('slug', 'blocks-on-time-real')->first())->value;

                /*
                // Read field values (from db table)
                $blocks_off = DB::table('pirep_field_values')->where(['pirep_id' => $pirep->id, 'slug' => 'blocks-off-time-real'])->value('value');
                $blocks_on = DB::table('pirep_field_values')->where(['pirep_id' => $pirep->id, 'slug' => 'blocks-on-time-real'])->value('value');
                */

                $sb_dep = Carbon::createFromTimestamp($pirep->simbrief->xml->times->sched_out);
                $sb_arr = Carbon::createFromTimestamp($pirep->simbrief->xml->times->sched_in);
                $act_dep = Carbon::parse($blocks_off);
                $act_arr = Carbon::parse($blocks_on);
                $diff_dep = $act_dep->diffInMinutes($sb_dep);
                $diff_arr = $act_arr->diffInMinutes($sb_arr);

                // Departure Time Check
                if ($diff_dep > $dla_margin) {
                    if ($sb_dep > $act_dep) {
                        $pirep_comments->put('sb_tdepe', 'Early Departure ' . $diff_dep . 'm');
                    } else {
                        $pirep_comments->put('sb_tdepl', 'Late Departure ' . $diff_dep . 'm');
                    }
                }

                // Arrival Time Check
                if ($diff_arr > $dla_margin) {
                    if ($sb_arr > $act_arr) {
                        $pirep_comments->put('sb_tarre', 'Early Arrival ' . $diff_arr . 'm');
                    } else {
                        $pirep_comments->put('sb_tarrl', 'Late Arrival ' . $diff_arr . 'm');
                    }
                }
            }

            // Check Structural TakeOff Weight
            if ($act_tow && $act_tow > $simbrief->xml->weights->max_tow_struct) {
                $pirep_comments->put('sb_wstow', 'Overweight TakeOff');
            }

            // Check Performance Limited TakeOff Weight
            if ($act_tow && $act_tow > $simbrief->xml->weights->max_tow) {
                $pirep_comments->put('sb_wptow', 'Overweight TakeOff (Performance Limited)');
            }

            // Check Landing Weight
            if ($act_ldw && $act_ldw > $simbrief->xml->weights->max_ldw) {
                $pirep_comments->put('sb_wsldw', 'Overweight Landing');
            }

            // OFP Check Aircraft
            if ($aircraft->registration != $simbrief->xml->aircraft->reg) {
                $pirep_comments->put('sb_acreg', 'Wrong Aircraft (Registration Mismatch)');
            }

            // OFP Excessive Block Fuel (Block + 60 mins)
            if ($block_fuel > ($simbrief->xml->fuel->plan_ramp + $simbrief->xml->fuel->avg_fuel_flow)) {
                $pirep_comments->put('sb_excbf', 'Excessive Block Fuel');
            }

            // OFP Minimum Block Fuel
            if ($block_fuel < ($simbrief->xml->fuel->plan_ramp - $simbrief->xml->fuel->taxi)) {
                $pirep_comments->put('sb_minbf', 'TakeOff Below OFP Minimum Block Fuel');
            }

            // OFP Minimum Diversion Fuel
            if ($landing_fuel < ($simbrief->xml->fuel->reserve + $simbrief->xml->fuel->alternate_burn)) {
                $pirep_comments->put('sb_mindf', 'Landing Below OFP Minimum Diversion Fuel');
            }

            // OFP Minimum Reserve Fuel
            if ($landing_fuel < $simbrief->xml->fuel->reserve) {
                $pirep_comments->put('sb_minrf', 'Landing Below OFP Minimum Reserve Fuel');
            }
        }

        // Non SimBrief Related Checks
        elseif (!$simbrief && $aircraft) {
            // Minimum Block Fuel
            if ($pirep->block_fuel < ($pirep->fuel_used + round(($pirep->fuel_used / $pirep->flight_time) * 30, 2))) {
                $pirep_comments->put('gen_minbf', 'TakeOff Below Minimum Required Block Fuel');
            }
            // Minimum Remaining Fuel
            if (($pirep->block_fuel - $pirep->fuel_used) < round(($pirep->fuel_used / $pirep->flight_time) * 30, 2)) {
                $pirep_comments->put('gen_minrf', 'Landing Below Minimum Required Remaining Fuel');
            }
        }

        // Post Comments
        if ($pirep_comments->count() > 0) {
            foreach ($pirep_comments as $key => $value) {
                $this->PostComment($pirep, $value, $poster);
            }
        }

        // If state is decided, update pirep with it and save
        if (isset($pirep_state)) {
            $pirep->state = $pirep_state;
            $pirep->save();
        }
    }

    public function PostComment($pirep, $comment, $poster = null)
    {
        if (is_null($poster)) {
            // Get A Random Admin
            $adm_users = DB::table('role_user')->where('role_id', function ($query) {
                return $query->select('id')->from('roles')->where('name', 'admin')->limit(1);
            })->pluck('user_id');

            $poster = $adm_users->random();
        }
        // Post The Message
        PirepComment::create(['pirep_id' => $pirep->id, 'user_id' => $poster, 'comment' => $comment]);
    }
}
