<?php

namespace Modules\DisposableSpecial\Listeners;

use App\Events\Expenses;
use App\Models\Enums\ExpenseType;
use App\Models\Enums\FareType;
use App\Models\Enums\PirepState;
use App\Models\Expense;
use App\Models\Pirep;

class Expense_Airport
{
    // Return Airport Related Expenses
    public function handle(Expenses $event)
    {
        $expenses = [];
        $group = 'Airport Fees';

        // Landing Fee Settings
        $lf_method = DS_Setting('turksim.expense_lfmethod', 'disabled'); // DISABLED, LW or MTOW
        $lf_base = DS_Setting('turksim.expense_lfbase', 4.44); // Per Weight (Metric Tonne or Imperial Tonne)
        // Parking Fee Settings
        $pf_method = DS_Setting('turksim.expense_pfmethod', 'disabled'); // DISABLED, LW or MTOW
        $pf_base = DS_Setting('turksim.expense_pfbase', 0.55); // Per Weight, Applied after 2 hours per day, Multiplies after 24 hours
        $pf_maxd = DS_Setting('turksim.expense_pfmaxd', 14); // Maximum Days For Parking Fee
        // Terminal Services Fee Settings
        $tf_method = DS_Setting('turksim.expense_tfmethod', 'disabled'); // DISABLED, LOAD or CAP
        $tf_paxbase = DS_Setting('turksim.expense_tfpaxbase', 3.5); // Per Pax
        $tf_cgobase = DS_Setting('turksim.expense_tfcgobase', 0.0125); // Per Weight
        // Airport Authority Fee Settings
        $aa_method = DS_Setting('turksim.expense_aamethod', 'disabled'); // DISABLED, LOAD or CAP
        $aa_paxbase = DS_Setting('turksim.expense_aapaxbase', 0.64); // Per Pax Base Price
        $aa_cgobase = DS_Setting('turksim.expense_aacgobase', 1.2); // Per Weight Base Price
        // Ground Handling Fee Settings
        $gh_method = DS_Setting('turksim.expense_ghmethod', 'disabled'); // DISABLED, LOAD or CAP
        $gh_paxbase = DS_Setting('turksim.expense_ghpaxbase', 4.28); // Per Pax Base Price
        $gh_cgobase = DS_Setting('turksim.expense_ghcgobase', 6.31); // Per Weight Base Price
        // Catering Setting
        $ct_method = DS_Setting('turksim.expense_ctmethod', 'disabled'); // DISABLED, LOAD or CAP
        $ct_srvbase = DS_Setting('turksim.expense_ctsrvbase', 0.06); // Service Fee Base Price
        $ct_paxbase = DS_Setting('turksim.expense_ctpaxbase', 4.98); // Per Pax Base Price

        if ($lf_method === 'disabled' && $pf_method === 'disabled' && $tf_method === 'disabled' && $aa_method === 'disabled' && $gh_method === 'disabled' && $ct_method === 'disabled') {
            return $expenses;
        }

        $units = DS_GetUnits();
        $lc_carriers = ['PGT', 'OHY', 'KKK', 'SXS', 'HES']; // Low Cost Carriers Group
        $non_pax = ['F', 'A', 'H', 'I', 'K', 'M', 'P', 'T']; // Flight Types without Passenger Service
        // Airport Groups
        $ap_group_1 = ['LTBA', 'LTAC', 'LTAI', 'LTBJ', 'LTBS', 'LTFE', 'LTAF', 'LTCG', 'LTCE', 'LTAJ', 'LTAU', 'LTDA', 'LTFM', 'LTFJ'];

        // Basics
        $pirep = $event->pirep;
        $pirep->loadMissing('aircraft.subfleet.fares', 'airline', 'arr_airport', 'dpt_airport', 'fares');
        $aircraft = $pirep->aircraft;
        $orig = $pirep->dpt_airport;
        $dest = $pirep->arr_airport;
        // Max Defs
        $mtow = $aircraft->mtow->internal(0) > 0 ? $aircraft->mtow->internal(2) : null;
        $mlw = $aircraft->mlw->internal(0) > 0 ? $aircraft->mlw->internal(2) : null;
        $maxpax = null;
        $maxcgo = null;
        // Actual Defs
        $lw = null;
        $tow = null;
        $pax = null;
        $cgo = null;

        // High Season Dates
        $pirep_year = $pirep->submitted_at->format('Y');
        $season_s = $pirep_year.'-05-01';
        $season_e = $pirep_year.'-10-31';

        // Low Cost Airline Check
        $lowcost = (in_array($pirep->airline->icao, $lc_carriers) || $pirep->route_code === 'AJ') ? true : false;
        // High Priority Airport Check
        $hp_dest = (in_array($pirep->arr_airport_id, $ap_group_1)) ? true : false;
        $hp_orig = (in_array($pirep->dpt_airport_id, $ap_group_1)) ? true : false;
        // Need Max or Actual
        $needmax = ($lf_method === 'mtow' || $pf_method === 'mtow' || $aa_method === 'cap' || $tf_method === 'cap' || $gh_method === 'cap') ? true : false;
        $needact = ($lf_method === 'lw' || $pf_method === 'lw' || $aa_method === 'load' || $tf_method === 'load' || $gh_method === 'load') ? true : false;
        // Domestic Check
        $int = ($orig && $dest && $orig->country === $dest->country) ? false : true;

        if ($needmax && $aircraft && $aircraft->subfleet->fares->count() > 0) {
            $pax_cap = 0;
            $cgo_cap = 0;
            foreach ($aircraft->subfleet->fares as $fare) {
                if ($fare->type === FareType::PASSENGER) {
                    $pax_cap = $pax_cap + $fare->pivot->capacity;
                }
                if ($fare->type === FareType::CARGO) {
                    $cgo_cap = $cgo_cap + $fare->pivot->capacity;
                }
            }

            $maxpax = ($pax_cap > 0) ? $pax_cap : null;
            $maxcgo = ($cgo_cap > 0) ? $cgo_cap : null;
        }

        // Get Pirep Details (Actual Figures)
        if ($needact && $aircraft) {
            $act_lw = optional($pirep->fields->where('slug', 'landing-weight')->first())->value;
            $act_tow = optional($pirep->fields->where('slug', 'takeoff-weight')->first())->value;

            $lw = (is_numeric($act_lw) && $act_lw > 0) ? round($act_lw) : null;
            $tow = (is_numeric($act_tow) && $act_tow > 0) ? round($act_tow) : null;
        }

        if ($needact && $aircraft && $pirep->fares->count() > 0) {
            $act_pax = 0;
            $act_cgo = 0;
            foreach ($pirep->fares as $fare) {
                if ($fare->type === FareType::PASSENGER) {
                    $act_pax = $act_pax + $fare->count;
                }
                if ($fare->type === FareType::CARGO) {
                    $act_cgo = $act_cgo + $fare->count;
                }
            }

            $pax = ($act_pax >= 0) ? $act_pax : null;
            $cgo = ($act_cgo >= 0) ? $act_cgo : null;
        }

        // Landing Fee
        if ($lf_method != 'disabled') {
            $base_weight = ($lf_method === 'lw') ? $lw : $mtow;

            if (is_numeric($base_weight)) {
                // Get Total Landing Counts Per Year
                $landing_count = Pirep::where([
                    'airline_id'     => $pirep->airline_id,
                    'arr_airport_id' => $pirep->arr_airport_id,
                    'state'          => 2,
                ])->whereYear('submitted_at', $pirep->submitted_at)->count();

                // Check Destination Airport Group
                if ($hp_dest === true) {
                    $lf_base = round($lf_base * 1.606, 2);
                }

                if ($landing_count >= 0 && $landing_count <= 50) {
                    $base_fee = $lf_base;
                } elseif ($landing_count >= 51 && $landing_count <= 100) {
                    $base_fee = round($lf_base * 0.9568, 2);
                } elseif ($landing_count >= 101 && $landing_count <= 200) {
                    $base_fee = round($lf_base * 0.9032, 2);
                } elseif ($landing_count >= 201 && $landing_count <= 400) {
                    $base_fee = round($lf_base * 0.8583, 2);
                } else {
                    $base_fee = round($lf_base * 0.8151, 2);
                }

                $base_weight = round($base_weight / 2240, 2);
                $landing_fee = round($base_weight * $base_fee, 2);

                $expenses[] = new Expense([
                    'type'              => ExpenseType::FLIGHT,
                    'amount'            => $landing_fee,
                    'transaction_group' => $group,
                    'name'              => 'Landing Fee',
                    'multiplier'        => false,
                    'charge_to_user'    => false,
                ]);
            }
        }

        // Parking Fee
        if ($pf_method != 'disabled') {
            if ($pf_method === 'lw') {
                $base_weight = $lw;
            } else {
                $base_weight = $mtow;
            }
            // Get Previous Landing Details
            $prev_landing = Pirep::select('block_on_time')->where([
                'aircraft_id'    => $pirep->aircraft_id,
                'arr_airport_id' => $pirep->dpt_airport_id,
                'state'          => PirepState::ACCEPTED,
            ])->where('submitted_at', '<=', $pirep->submitted_at)->orderby('submitted_at', 'desc')->first();

            if (is_numeric($base_weight) && $prev_landing) {
                $max_note = null;
                $on_block = $prev_landing->block_on_time;
                $off_block = $pirep->block_off_time;

                $diff_hours = $off_block->diffInHours($on_block);
                $diff_days = $off_block->diffInDays($on_block);

                $base_weight = round($base_weight / 2240, 2);

                // Check High Season
                if ($off_block->between($season_s, $season_e)) {
                    $pf_base = round($pf_base * 2, 2);
                }
                // Check Origin Airport Group
                if ($hp_orig === true) {
                    $pf_base = round($pf_base * 2, 2);
                }
                // Apply Parking Fee Based on HOURS
                if ($diff_hours >= 2 && $diff_hours <= 24) {
                    $parking_note = $diff_hours.' Hours';
                    $parking_fee = round($base_weight * $pf_base, 2);
                }
                // Apply Parking Fee Based on DAYS
                elseif ($diff_hours > 24 && $diff_days > 0) {
                    $diff_days = $diff_days + 1;
                    // Apply Maximum
                    if ($diff_days > $pf_maxd) {
                        $diff_days = $pf_maxd;
                        // $max_note = ' | Max Rule Applied';
                    }
                    $parking_note = $diff_days.' Days';
                    // Apply Reduced Long Term Parking Base Fee For Hubs
                    if ($orig && $orig->hub == 1) {
                        $pf_base = round($pf_base * 0.50, 2);
                    }

                    $parking_fee = round(($base_weight * $pf_base) * $diff_days, 2);
                }

                if (isset($parking_fee) && $parking_fee > 0) {
                    // Log::debug('Disposable Special, Parking Fee details Time=' . $parking_note . ' Base Fee=' . $pf_base . ' Weight Factor=' . $base_weight . ' ' . $units['weight'] . $max_note);
                    $expenses[] = new Expense([
                        'type'              => ExpenseType::FLIGHT,
                        'amount'            => round($parking_fee + 84, 2),
                        'transaction_group' => $group,
                        'name'              => 'Parking Fee ('.$parking_note.')',
                        'multiplier'        => false,
                        'charge_to_user'    => false,
                    ]);
                }
            }
        }

        // Terminal Usage Fee
        if ($tf_method != 'disabled') {
            if ($tf_method === 'load') {
                $base_pax = $pax;
                $base_cgo = $cgo;
            } else {
                $base_pax = $maxpax;
                $base_cgo = $maxcgo;
            }

            if (is_numeric($base_pax) || is_numeric($base_cgo)) {
                // Domestic Discount
                if ($int === false) {
                    $tf_paxbase = $tf_paxbase * 0.50;
                    $tf_cgobase = $tf_cgobase * 0.50;
                }
                // Check High Season
                if ($pirep->created_at->between($season_s, $season_e)) {
                    $tf_paxbase = round($tf_paxbase * 2, 2);
                    $tf_cgobase = round($tf_cgobase * 2, 2);
                }
                // Check Origin Group
                if ($hp_orig === true) {
                    $tf_paxbase = round($tf_paxbase * 1.25, 2);
                    $tf_cgobase = round($tf_cgobase * 1.25, 2);
                }
                // Calculate Terminal Usage Fee
                $fee_pax = round($base_pax * $tf_paxbase, 2);
                $fee_cgo = round($base_cgo * $tf_cgobase, 2);
                $terminal_fee = round($fee_pax + $fee_cgo, 2);
                // $pax_note = ($fee_pax > 0) ? ' Pax=' . $base_pax . ' (' . $fee_pax . ' ' . $units['currency'] . ')': null;
                // $cgo_note = ($fee_cgo > 0) ? ' Cgo=' . $base_cgo . ' ' . $units['weight'] . ' (' . $fee_cgo . ' ' . $units['currency'] . ')': null;

                if ($terminal_fee > 0) {
                    // Log::debug('Disposable Special, Terminal Fee details' . $pax_note . $cgo_note);
                    $expenses[] = new Expense([
                        'type'              => ExpenseType::FLIGHT,
                        'amount'            => $terminal_fee,
                        'transaction_group' => $group,
                        'name'              => 'Terminal Services',
                        'multiplier'        => false,
                        'charge_to_user'    => false,
                    ]);
                }
            }
        }

        // Airport Authority Fee
        if ($aa_method != 'disabled') {
            if ($aa_method === 'load') {
                $base_pax = $pax;
                $base_cgo = $lw;
            } else {
                $base_pax = $maxpax;
                $base_cgo = $mtow;
            }
            // Check National Carrier Flights
            $dep_national = ($orig && $orig->country === strtoupper($pirep->airline->country)) ? true : false;
            $arr_national = ($dest && $dest->country === strtoupper($pirep->airline->country)) ? true : false;
            // Passenger or Cargo Selection
            if ($base_pax > 0) {
                $amount = $base_pax;
                $base_price = $aa_paxbase;
                $srv_type = 'pax';
            } else {
                $amount = $base_cgo;
                $base_price = $aa_cgobase;
                $srv_type = 'cgo';
            }
            if ($amount > 0) {
                $expenses[] = $this->AuthorityFee($amount, $base_price, $dep_national, $srv_type, 'Departure', $units);
                $expenses[] = $this->AuthorityFee($amount, $base_price, $arr_national, $srv_type, 'Arrival', $units);
            }
        }

        // Ground Handling Fee
        if ($gh_method != 'disabled') {
            if ($gh_method === 'load') {
                $base_pax = $pax;
                $base_cgo = $cgo;
            } else {
                $base_pax = $maxpax;
                $base_cgo = $maxcgo;
            }
            // Passenger or Cargo Selection
            if ($base_pax > 0) {
                $amount = $base_pax;
                $base_price = $gh_paxbase;
                $srv_type = 'pax';
            } else {
                $amount = $base_cgo;
                $base_price = $gh_cgobase;
                $srv_type = 'cgo';
            }
            // Low Cost Carrier
            if ($lowcost === true) {
                $base_price = round($base_price * 0.65, 3);
            }
            // Hub Check
            $dep_hub = ($orig && $orig->hub == 1) ? true : false;
            $arr_hub = ($dest && $dest->hub == 1) ? true : false;

            if ($amount > 0) {
                $expenses[] = $this->GroundHandlingFee($amount, $base_price, $dep_hub, $srv_type, 'Departure', $units);
                $expenses[] = $this->GroundHandlingFee($amount, $base_price, $arr_hub, $srv_type, 'Arrival', $units);
            }
        }

        // Catering
        if ($ct_method != 'disabled') {
            $base_pax = ($ct_method === 'load') ? $pax : $maxpax;
            $cat_note = 'Standard';
            $base_price = $ct_paxbase;
            // Low Cost Carriers
            if ($lowcost === true) {
                $cat_note = 'Low Cost';
                $base_price = round($base_price * 0.50, 3);
            }
            // No Pax Catering
            if (in_array($pirep->flight_type, $non_pax) || $base_pax === 0) {
                $base_pax = 4;
                $base_price = round($base_price * 2.50, 3);
                $cat_note = 'Crew Only';
                $int = true; // Force International Load
            }
            // Time Factor
            $flight_time = $pirep->flight_time;
            if ($flight_time >= 0 && $flight_time <= 30) {
                $time_factor = 0.50;
                $cat_time = 'Ultra Short Haul';
            } elseif ($flight_time > 30 && $flight_time <= 180) {
                $time_factor = 0.75;
                $cat_time = 'Short Haul';
            } elseif ($flight_time > 180 && $flight_time <= 360) {
                $time_factor = 1;
                $cat_time = 'Medium Haul';
            } elseif ($flight_time > 360 && $flight_time <= 720) {
                $time_factor = 1.25;
                $cat_time = 'Long Haul';
            } elseif ($flight_time > 720) {
                $time_factor = 1.5;
                $cat_time = 'Ultra Long Haul';
            }
            // Domestic Check
            $intdom = null;
            if ($int === false) {
                $intdom = 'Domestic ';
                $base_price = round($base_price * 0.75, 3);
            }
            // Catering Items Fee
            $catering_items = round($base_pax * $base_price * $time_factor, 2);
            // Catering Service Fee Tariff
            $catering_srv = round($ct_srvbase * 50);
            if ($base_pax > 0 && $base_pax <= 50) {
                $catering_srv = $catering_srv;
            } elseif ($base_pax > 50 && $base_pax <= 100) {
                $catering_srv = round($catering_srv * 1.66);
            } elseif ($base_pax > 100 && $base_pax <= 150) {
                $catering_srv = round($catering_srv * 3.33);
            } elseif ($base_pax > 150 && $base_pax <= 200) {
                $catering_srv = round($catering_srv * 5.33);
            } elseif ($base_pax > 200 && $base_pax <= 250) {
                $catering_srv = round($catering_srv * 7);
            } elseif ($base_pax > 250 && $base_pax <= 300) {
                $catering_srv = round($catering_srv * 7.66);
            } elseif ($base_pax > 300 && $base_pax <= 350) {
                $catering_srv = round($catering_srv * 9);
            } elseif ($base_pax > 350) {
                $catering_srv = round($catering_srv * 10.33);
            }
            // Catering Fee
            $catering_fee = $catering_srv + $catering_items;

            if ($catering_fee > 0) {
                $expenses[] = new Expense([
                    'type'              => ExpenseType::FLIGHT,
                    'amount'            => $catering_fee,
                    'transaction_group' => $group,
                    'name'              => $cat_note.' Catering ('.$intdom.$cat_time.')',
                    'multiplier'        => false,
                    'charge_to_user'    => false,
                ]);
            }
        }

        return $expenses;
    }

    // Authority Fee Calculation, Returns Array
    public function AuthorityFee($amount, $base_price, $is_national, $srv_type, $apt_type, $units)
    {
        // Based On Standard Authority Tariff 2021
        $nat = null;
        if ($srv_type === 'cgo') {
            $cgo = $amount;
            // Load Control, Ramp, Cargo, Flight Ops, Supervision
            $price = round($base_price * 25);
            $cgo = round($cgo / 1000, 2);
            if ($cgo > 0 && $cgo <= 25) {
                $price = $price;
            } elseif ($cgo > 25 && $cgo <= 50) {
                $price = round($price * 1.8);
            } elseif ($cgo > 50 && $cgo <= 75) {
                $price = round($price * 3.4333);
            } elseif ($cgo > 75 && $cgo <= 100) {
                $price = round($price * 4.0666);
            } elseif ($cgo > 100 && $cgo <= 150) {
                $price = round($price * 5.0333);
            } elseif ($cgo > 150 && $cgo <= 200) {
                $price = round($price * 5.7333);
            } elseif ($cgo > 200 && $cgo <= 300) {
                $price = round($price * 6.3666);
            } elseif ($cgo > 300) {
                $price = round($price * 8);
            }
        } else {
            $pax = $amount;
            // Passenger Services, Load Control, Ramp, Luggage & Cargo, Flight Ops, Supervision
            $price = round($base_price * 50);
            if ($pax > 0 && $pax <= 50) {
                $price = $price;
            } elseif ($pax > 50 && $pax <= 100) {
                $price = round($price * 2);
            } elseif ($pax > 100 && $pax <= 150) {
                $price = round($price * 3.8125);
            } elseif ($pax > 150 && $pax <= 200) {
                $price = round($price * 4.75);
            } elseif ($pax > 200 && $pax <= 250) {
                $price = round($price * 6.125);
            } elseif ($pax > 250 && $pax <= 300) {
                $price = round($price * 7.15625);
            } elseif ($pax > 300 && $pax <= 350) {
                $price = round($price * 8.09375);
            } elseif ($pax > 350) {
                $price = round($price * 9.21875);
            }
        }
        // Apply Arrival Discount
        if ($apt_type === 'Arrival') {
            $price = round($price * 0.82, 2);
        }
        // Appply Nationality Discount
        if ($is_national === true) {
            $price = round($price * 0.50, 2);
            $nat = 'National ';
        }

        return new Expense([
            'type'              => ExpenseType::FLIGHT,
            'amount'            => $price,
            'transaction_group' => 'Airport Fees',
            'name'              => $nat.'Airport Authority Fee ('.$apt_type.')',
            'multiplier'        => false,
            'charge_to_user'    => false,
        ]);
    }

    // Ground Handling Fee Calculation, Returns Array
    public function GroundHandlingFee($amount, $base_price, $is_hub, $srv_type, $apt_type, $units)
    {
        // Based On Widely Used Tariffs 2021
        if ($srv_type === 'cgo') {
            $cgo = $amount;
            // Load Control, Ramp, Cargo, Flight Ops, Supervision
            $price = round($base_price * 24);
            $cgo = round($cgo / 1000, 2);
            if ($cgo > 0 && $cgo <= 5) {
                $price = $price;
            } elseif ($cgo > 5 && $cgo <= 10) {
                $price = round($price * 1.98);
            } elseif ($cgo > 10 && $cgo <= 20) {
                $price = round($price * 3.53);
            } elseif ($cgo > 20 && $cgo <= 35) {
                $price = round($price * 4.28);
            } elseif ($cgo > 35 && $cgo <= 50) {
                $price = round($price * 5.35);
            } elseif ($cgo > 50 && $cgo <= 75) {
                $price = round($price * 5.83);
            } elseif ($cgo > 75 && $cgo <= 150) {
                $price = round($price * 6.42);
            } elseif ($cgo > 150) {
                $price = round($price * 7);
            }
        } else {
            $pax = $amount;
            // Passenger Services, Load Control, Ramp, Luggage & Cargo, Flight Ops, Supervision
            $price = round($base_price * 54);
            if ($pax > 0 && $pax <= 50) {
                $price = $price;
            } elseif ($pax > 50 && $pax <= 100) {
                $price = round($price * 2.12);
            } elseif ($pax > 100 && $pax <= 150) {
                $price = round($price * 3.45);
            } elseif ($pax > 150 && $pax <= 200) {
                $price = round($price * 4.26);
            } elseif ($pax > 200 && $pax <= 250) {
                $price = round($price * 6.37);
            } elseif ($pax > 250 && $pax <= 300) {
                $price = round($price * 7.13);
            } elseif ($pax > 300 && $pax <= 350) {
                $price = round($price * 8.37);
            } elseif ($pax > 350) {
                $price = round($price * 9.48);
            }
        }
        // Apply Hub Discount
        if ($is_hub === true) {
            $price = round($price * 0.54, 2);
        }
        // Apply Arrival Discount
        if ($apt_type === 'Arrival') {
            $price = round($price * 0.62, 2);
        }

        return new Expense([
            'type'              => ExpenseType::FLIGHT,
            'amount'            => $price,
            'transaction_group' => 'Airport Fees',
            'name'              => 'Ground Handling Fee ('.$apt_type.')',
            'multiplier'        => false,
            'charge_to_user'    => false,
        ]);
    }
}
