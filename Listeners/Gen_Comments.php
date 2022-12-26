<?php

namespace Modules\DisposableSpecial\Listeners;

use App\Events\PirepFiled;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class Gen_Comments
{
    public function handle(PirepFiled $event)
    {
        $comments = DS_Setting('turksim.auto_comment', false);

        if ($comments === false) {
            return;
        }

        $check_times = DS_Setting('turksim.auto_comment_times', true);
        $poster = DS_Setting('turksim.auto_comment_user', false);

        if ($poster === false) {
            $adm_users = DB::table('role_user')->where('role_id', function ($query) {
                return $query->select('id')->from('roles')->where('name', 'admin')->limit(1);
            })->pluck('user_id');
            $poster = $adm_users->random();
        }

        $pirep = $event->pirep;
        $aircraft = $pirep->aircraft;
        $simbrief = $pirep->simbrief;

        $pirep_comments = [];
        $now = Carbon::now()->toDateTimeString();
        $default_fields = ['pirep_id' => $pirep->id, 'user_id' => $poster, 'created_at' => $now, 'updated_at' => $now];

        $use_direct_db = true; // Set to false if you want to use php/memory & model loop for values

        if ($use_direct_db === true) {
            // $act_rw = DB::table('pirep_field_values')->where(['pirep_id' => $pirep->id, 'slug' => 'ramp-weight'])->value('value');
            $act_tow = DB::table('pirep_field_values')->where(['pirep_id' => $pirep->id, 'slug' => 'takeoff-weight'])->value('value');
            $act_ldw = DB::table('pirep_field_values')->where(['pirep_id' => $pirep->id, 'slug' => 'landing-weight'])->value('value');
            // $act_lcent = DB::table('pirep_field_values')->where(['pirep_id' => $pirep->id, 'slug' => 'arrival-centerline-deviation'])->value('value');
            // $act_ldist = DB::table('pirep_field_values')->where(['pirep_id' => $pirep->id, 'slug' => 'arrival-threshold-distance'])->value('value');
            $act_lfuel = DB::table('pirep_field_values')->where(['pirep_id' => $pirep->id, 'slug' => 'landing-fuel'])->value('value');
            // $act_lpitch = DB::table('pirep_field_values')->where(['pirep_id' => $pirep->id, 'slug' => 'landing-pitch'])->value('value');
            // $act_lroll = DB::table('pirep_field_values')->where(['pirep_id' => $pirep->id, 'slug' => 'landing-roll'])->value('value');
            // $act_lspeed = DB::table('pirep_field_values')->where(['pirep_id' => $pirep->id, 'slug' => 'landing-speed'])->value('value');
            // $act_tspeed = DB::table('pirep_field_values')->where(['pirep_id' => $pirep->id, 'slug' => 'takeoff-speed'])->value('value');
            // $act_aircraft = DB::table('pirep_field_values')->where(['pirep_id' => $pirep->id, 'slug' => 'aircraft'])->value('value');
            // $act_light_rules = DB::table('pirep_field_values')->where(['pirep_id' => $pirep->id, 'slug' => 'ignore-light-rules'])->value('value');
        } else {
            // $act_rw = optional($pirep->fields->where('slug', 'ramp-weight')->first())->value;
            $act_tow = optional($pirep->fields->where('slug', 'takeoff-weight')->first())->value;
            $act_ldw = optional($pirep->fields->where('slug', 'landing-weight')->first())->value;
            // $act_lcent = optional($pirep->fields->where('slug', 'arrival-centerline-deviation')->first())->value;
            // $act_ldist = optional($pirep->fields->where('slug', 'arrival-threshold-distance')->first())->value;
            $act_lfuel = optional($pirep->fields->where('slug', 'landing-fuel')->first())->value;
            // $act_lpitch = optional($pirep->fields->where('slug', 'landing-pitch')->first())->value;
            // $act_lroll = optional($pirep->fields->where('slug', 'landing-roll')->first())->value;
            // $act_lspeed = optional($pirep->fields->where('slug', 'landing-speed')->first())->value;
            // $act_tspeed = optional($pirep->fields->where('slug', 'takeoff-speed')->first())->value;
            // $act_aircraft = optional($pirep->fields->where('slug', 'aircraft')->first())->value;
            // $act_light_rules = optional($pirep->fields->where('slug', 'ignore-light-rules')->first())->value;
        }

        // SimBrief based checks
        if ($simbrief && $aircraft) {

            if ($simbrief->xml->params->units == 'kgs') {
                $block_fuel = $pirep->block_fuel->toUnit('kg', 2);
                $fuel_used = $pirep->fuel_used->toUnit('kg', 2);
                $landing_fuel = is_numeric($act_lfuel) ? round($act_lfuel / 2.20462262185, 2) : round($block_fuel - $fuel_used, 2);
                $act_tow = is_numeric($act_tow) ? round($act_tow / 2.20462262185) : null;
                $act_ldw = is_numeric($act_ldw) ? round($act_ldw / 2.20462262185) : null;
                $weight_margin = 1500;
            } else {
                $block_fuel = $pirep->block_fuel->internal(2);
                $fuel_used = $pirep->fuel_used->internal(2);
                $landing_fuel = is_numeric($act_lfuel) ? round($act_lfuel, 2) : round($block_fuel - $fuel_used, 2);
                $act_tow = is_numeric($act_tow) ? round($act_tow) : null;
                $act_ldw = is_numeric($act_ldw) ? round($act_ldw) : null;
                $weight_margin = 3300;
            }

            if ($check_times === true) {

                $dla_margin = DS_Setting('turksim.auto_comment_dlamargin', 20);

                if ($use_direct_db === true) {
                    $blocks_off = DB::table('pirep_field_values')->where(['pirep_id' => $pirep->id, 'slug' => 'blocks-off-time-real'])->value('value');
                    $blocks_on = DB::table('pirep_field_values')->where(['pirep_id' => $pirep->id, 'slug' => 'blocks-on-time-real'])->value('value');
                } else {
                    $blocks_off = optional($pirep->fields->where('slug', 'blocks-off-time-real')->first())->value;
                    $blocks_on = optional($pirep->fields->where('slug', 'blocks-on-time-real')->first())->value;
                }

                $sb_dep = Carbon::createFromTimestamp($pirep->simbrief->xml->times->sched_out);
                $sb_arr = Carbon::createFromTimestamp($pirep->simbrief->xml->times->sched_in);
                $act_dep = Carbon::parse($blocks_off);
                $act_arr = Carbon::parse($blocks_on);
                $diff_dep = $act_dep->diffInMinutes($sb_dep);
                $diff_arr = $act_arr->diffInMinutes($sb_arr);

                if ($diff_dep > $dla_margin) {
                    if ($sb_dep > $act_dep) {
                        $pirep_comments[] = array_merge($default_fields, ['comment' => 'Early Departure ' . $diff_dep . 'm']);
                    } else {
                        $pirep_comments[] = array_merge($default_fields, ['comment' => 'Late Departure ' . $diff_dep . 'm']);
                    }
                }

                if ($diff_arr > $dla_margin) {
                    if ($sb_arr > $act_arr) {
                        $pirep_comments[] = array_merge($default_fields, ['comment' => 'Early Arrival ' . $diff_arr . 'm']);
                    } else {
                        $pirep_comments[] = array_merge($default_fields, ['comment' => 'Late Arrival ' . $diff_arr . 'm']);
                    }
                }
            }

            if ($act_tow && $act_tow < ($simbrief->xml->weights->est_tow - $weight_margin)) {
                $pirep_comments[] = array_merge($default_fields, ['comment' => 'Aircraft Not Loaded Properly (Check Payload)']);
            }

            if ($act_tow && $act_tow > ($simbrief->xml->weights->est_tow + $weight_margin)) {
                $pirep_comments[] = array_merge($default_fields, ['comment' => 'Flight Plan Not Reflecting Current Load (Re-Calculation Advised)']);
            }

            if ($act_tow && $act_tow > $simbrief->xml->weights->max_tow_struct) {
                $pirep_comments[] = array_merge($default_fields, ['comment' => 'Overweight TakeOff (Structure)']);
            }

            if ($act_tow && $act_tow > $simbrief->xml->weights->max_tow) {
                $pirep_comments[] = array_merge($default_fields, ['comment' => 'Overweight TakeOff (Performance)']);
            }

            if ($act_ldw && $act_ldw > $simbrief->xml->weights->max_ldw) {
                $pirep_comments[] = array_merge($default_fields, ['comment' => 'Overweight Landing (Structure)']);
            }

            if ($aircraft->registration != $simbrief->xml->aircraft->reg) {
                $pirep_comments[] = array_merge($default_fields, ['comment' => 'Wrong Aircraft (Registration Mismatch)']);
            }

            if ($aircraft->icao != $simbrief->xml->aircraft->icaocode) {
                $pirep_comments[] = array_merge($default_fields, ['comment' => 'Wrong Aircraft (ICAO Type Mismatch)']);
            }

            if ($block_fuel > ($simbrief->xml->fuel->plan_ramp + $simbrief->xml->fuel->avg_fuel_flow)) {
                $pirep_comments[] = array_merge($default_fields, ['comment' => 'Excessive Block Fuel']);
            }

            if ($block_fuel < ($simbrief->xml->fuel->plan_ramp - $simbrief->xml->fuel->taxi)) {
                $pirep_comments[] = array_merge($default_fields, ['comment' => 'TakeOff Below OFP Minimum Block Fuel']);
            }

            if ($landing_fuel < ($simbrief->xml->fuel->reserve + $simbrief->xml->fuel->alternate_burn)) {
                $pirep_comments[] = array_merge($default_fields, ['comment' => 'Landing Below OFP Minimum Diversion Fuel']);
            }

            if ($landing_fuel < $simbrief->xml->fuel->reserve) {
                $pirep_comments[] = array_merge($default_fields, ['comment' => 'Landing Below OFP Minimum Reserve Fuel']);
            }
        }

        // Basic checks (with pirep figures)
        elseif (!$simbrief && $aircraft) {

            if ($pirep->block_fuel->internal(2) < ($pirep->fuel_used->internal(2) + round(($pirep->fuel_used->internal(2) / $pirep->flight_time) * 30, 2))) {
                $pirep_comments[] = array_merge($default_fields, ['comment' => 'TakeOff Below Minimum Required Block Fuel']);
            }

            if (($pirep->block_fuel->internal(2) - $pirep->fuel_used->internal(2)) < round(($pirep->fuel_used->internal(2) / $pirep->flight_time) * 30, 2)) {
                $pirep_comments[] = array_merge($default_fields, ['comment' => 'Landing Below Minimum Required Remaining Fuel']);
            }
        }

        if (is_countable($pirep_comments) && count($pirep_comments) > 0) {
            DB::table('pirep_comments')->insert($pirep_comments);
        }

        if (isset($pirep_state)) {
            $pirep->state = $pirep_state;
            $pirep->save();
        }
    }
}
