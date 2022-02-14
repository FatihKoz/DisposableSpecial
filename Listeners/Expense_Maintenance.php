<?php

namespace Modules\DisposableSpecial\Listeners;

use App\Events\Expenses;
use App\Models\Expense;
use App\Models\JournalTransaction;
use App\Models\Pirep;
use App\Models\Enums\ExpenseType;
use App\Models\Enums\FuelType;
use App\Services\FinanceService;
use App\Support\Money;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Modules\DisposableBasic\Models\DB_Tech;
use Modules\DisposableSpecial\Models\DS_Maintenance;

class Expense_Maintenance
{
    // Return a list of additional expenses as an ARRAY
    public function handle(Expenses $event)
    {
        $expenses = [];
        $group = 'Maintenace Checks';

        $maint_hard = DS_Setting('turksim.maint_lndhard', false);
        $maint_soft = DS_Setting('turksim.maint_lndsoft', false);
        $maint_tail = DS_Setting('turksim.maint_strtail', false);
        $maint_wing = DS_Setting('turksim.maint_strwing', false);
        $maint_user = DS_Setting('turksim.maint_chguser', false);

        if (!$maint_hard && !$maint_soft && !$maint_tail && !$maint_wing) {
            return $expenses;
        }

        $maint_hardlimit = DS_Setting('turksim.maint_lndhard_limit', 500);
        $maint_softlimit = DS_Setting('turksim.maint_lndsoft_limit', 50);
        $maint_taillimit = DS_Setting('turksim.maint_strtail_limit', 15);
        $maint_winglimit = DS_Setting('turksim.maint_strwing_limit', 10);

        $pirep = $event->pirep;
        $pirep->loadMissing('aircraft.airline', 'aircraft.subfleet', 'user.journal');

        $landing_rate = optional($pirep->fields->where('slug', 'landing-rate')->first())->value;
        $landing_pitch = optional($pirep->fields->where('slug', 'landing-pitch')->first())->value;
        $landing_roll = optional($pirep->fields->where('slug', 'landing-roll')->first())->value;
        $takeoff_pitch = optional($pirep->fields->where('slug', 'takeoff-pitch')->first())->value;
        $takeoff_roll = optional($pirep->fields->where('slug', 'takeoff-roll')->first())->value;

        if (!is_numeric($landing_rate) || is_numeric($landing_rate) && $landing_rate >= 0) {
            $maint_hard = false;
            $maint_soft = false;
        }
        if (!is_numeric($landing_pitch) && !is_numeric($takeoff_pitch)) {
            $maint_tail = false;
        }
        if (!is_numeric($landing_roll) && !is_numeric($takeoff_roll)) {
            $maint_wing = false;
        }

        // Get ICAO Specific Roll And Pitch limits
        $aircraft = $pirep->aircraft;
        if (DS_CheckModule('DisposableBasic') && $aircraft && filled($aircraft->icao)) {
            $tech_limits = DB_Tech::where('icao', $aircraft->icao)->first();

            if ($tech_limits && is_numeric($tech_limits->max_pitch)) {
                $maint_taillimit = $tech_limits->max_pitch;
            }

            if ($tech_limits && is_numeric($tech_limits->max_roll)) {
                $maint_winglimit = $tech_limits->max_roll;
            }
        }

        // Hard Landing
        if ($maint_hard && abs($landing_rate) > $maint_hardlimit) {
            $service_cost = $this->MaintenanceChecks('Hard Landing Check', $aircraft, true);
            $service_cost = round($service_cost * (abs($landing_rate) / $maint_hardlimit), 2);
            // Log::debug('Disposable Special, Maintenance Check Expense (Hard Landing) applied Pirep=' . $pirep->id);
            $expenses[] = $this->MaintenanceExpense($group, $service_cost, 'Maintenance Check (Hard Landing)');

            if ($maint_user) {
                $this->ChargeUser($pirep, $service_cost, 'Maintenance Check (Hard Landing)');
            }
        }

        // Soft Landing
        if ($maint_soft && abs($landing_rate) < $maint_softlimit) {
            if ($aircraft->subfleet->fuel_type === FuelType::JET_A) {
                $service_cost = $this->MaintenanceChecks('Soft Landing Check', $aircraft, true);
                $service_cost = round($service_cost * ($maint_softlimit / abs($landing_rate)), 2);
                // Log::debug('Disposable Special, Maintenance Check Expense (Soft Landing) applied Pirep=' . $pirep->id);
                $expenses[] = $this->MaintenanceExpense($group, $service_cost, 'Maintenance Check (Soft Landing)');

                if ($maint_user) {
                    $this->ChargeUser($pirep, $service_cost, 'Maintenance Check (Soft Landing)');
                }
            }
        }

        // Engine/Wing Strike
        if ($maint_wing) {
            if (is_numeric($landing_roll) && abs($landing_roll) > $maint_winglimit) {
                $service_cost = $this->MaintenanceChecks('Engine/Wing Strike Check', $aircraft, true);
                $service_cost = round($service_cost * (abs($landing_roll) / $maint_winglimit), 2);
                // Log::debug('Disposable Special, Maintenance Check Expense (Landing Engine/Wing Strike) applied Pirep=' . $pirep->id);
                $expenses[] = $this->MaintenanceExpense($group, $service_cost, 'Maintenance Check (Landing Engine/Wing Strike)');

                if ($maint_user) {
                    $this->ChargeUser($pirep, $service_cost, 'Maintenance Check (Landing Engine/Wing Strike)');
                }
            }

            if (is_numeric($takeoff_roll) && abs($takeoff_roll) > $maint_winglimit) {
                $service_cost = $this->MaintenanceChecks('Engine/Wing Strike Check', $aircraft, true);
                $service_cost = round($service_cost * (abs($takeoff_roll) / $maint_winglimit), 2);
                // Log::debug('Disposable Special, Maintenance Check Expense (TakeOff Engine/Wing Strike) applied Pirep=' . $pirep->id);
                $expenses[] = $this->MaintenanceExpense($group, $service_cost, 'Maintenance Check (TakeOff Engine/Wing Strike)');

                if ($maint_user) {
                    $this->ChargeUser($pirep, $service_cost, 'Maintenance Check (TakeOff Engine/Wing Strike)');
                }
            }
        }

        // Tail Strike
        if ($maint_tail) {
            if (is_numeric($landing_pitch) && abs($landing_pitch) > $maint_taillimit) {
                $service_cost = $this->MaintenanceChecks('Tail Strike Check', $aircraft, true);
                $service_cost = round($service_cost * (abs($landing_pitch) / $maint_taillimit), 2);
                // Log::debug('Disposable Special, Maintenance Check Expense (Landing Tail Strike) applied Pirep=' . $pirep->id);
                $expenses[] = $this->MaintenanceExpense($group, $service_cost, 'Maintenance Check (Landing Tail Strike)');

                if ($maint_user) {
                    $this->ChargeUser($pirep, $service_cost, 'Maintenance Check (Landing Tail Strike)');
                }
            }

            if (is_numeric($takeoff_pitch) && abs($takeoff_pitch) > $maint_taillimit) {
                $service_cost = $this->MaintenanceChecks('Tail Strike Check', $aircraft, true);
                $service_cost = round($service_cost * (abs($takeoff_pitch) / $maint_taillimit), 2);
                // Log::debug('Disposable Special, Maintenance Check Expense (TakeOff Tail Strike) applied Pirep=' . $pirep->id);
                $expenses[] = $this->MaintenanceExpense($group, $service_cost, 'Maintenance Check (TakeOff Tail Strike)');

                if ($maint_user) {
                    $this->ChargeUser($pirep, $service_cost, 'Maintenance Check (TakeOff Tail Strike)');
                }
            }
        }

        return $expenses;
    }

    // Generic Expense Array Generation Method
    public function MaintenanceExpense($group, $amount, $memo, $multiplier = false, $charge_user = false)
    {
        return new Expense([
            'type' => ExpenseType::FLIGHT,
            'amount' => $amount,
            'transaction_group' => $group,
            'name' => $memo,
            'multiplier' => $multiplier,
            'charge_to_user' => $charge_user
        ]);
    }

    // Main Method to calculate maintenance costs, change aircraft state and charge company
    public function MaintenanceChecks($check, $aircraft, $flight_only = false, $change_status = null)
    {
        $units = DS_GetUnits();
        $unit_rate = DS_Setting('turksim.maint_unitrate', 0.3775);

        if (is_null($change_status)) {
            $change_status = DS_Setting('turksim.maint_acstate_control', false);
        }

        // Multipliers
        if ($check === 'A Check') {
            $multiplier = 1;
        } elseif ($check === 'B Check') {
            $multiplier = 3;
        } elseif ($check === 'C Check') {
            $multiplier = 7;
        } elseif ($check === 'Line Check') {
            $multiplier = 0.25;
        } elseif ($check === 'Tail Strike Check') {
            $multiplier = 0.0479;
            $flight_only = true;
        } elseif ($check === 'Engine/Wing Strike Check') {
            $multiplier = 0.0586;
            $flight_only = true;
        } elseif ($check === 'Hard Landing Check') {
            $multiplier = 0.0335;
            $flight_only = true;
        } elseif ($check === 'Soft Landing Check') {
            $multiplier = 0.0137;
            $flight_only = true;
        } else {
            $multiplier = 0.20;
            $flight_only = true;
        }

        $mtow = $aircraft->mtow;

        if (!is_numeric($mtow)) {
            // Try to get at last TOW
            $last_pirep = Pirep::where(['aircraft_id' => $aircraft->id, 'state' => 2])->orderby('submitted_at', 'desc')->first();
            $last_tow = optional($last_pirep->fields->where('slug', 'takeoff-weight')->first())->value;
            $mtow = is_numeric($last_tow) ? round($last_tow, 2) : null;

            if (is_numeric($mtow) && $units['weight'] === 'kg') {
                $mtow = round($mtow / 2.20462262185, 2);
            }
        }

        if (!is_numeric($mtow)) {
            if ($units['weight'] === 'kg') {
                $mtow = 79015; // Fixed failsafe B738 MTOW metric
            } else {
                $mtow = 174200; // Fixed failsafe B738 MTOW imperial
            }
        }

        $maintenance_cost = round(($unit_rate * $mtow) * $multiplier, 2);
        // Log::debug('Disposable Maintenance, Calculation Details T=' . $check . ' W=' . $mtow . ' ' . $units['weight'] . ' C=' . $maintenance_cost . ' ' . $units['currency']);

        // Change aircraft status, Write actual maintenance operation details
        if ($change_status) {
            $ds_maint = DS_Maintenance::with('aircraft.airline')->where('aircraft_id', $aircraft->id)->first();

            if ($ds_maint) {
                // Durations
                if ($check === 'A Check') {
                    $duration = $ds_maint->limits->duration_a;
                } elseif ($check === 'B Check') {
                    $duration = $ds_maint->limits->duration_b;
                } elseif ($check === 'C Check') {
                    $duration = $ds_maint->limits->duration_c;
                } else {
                    $duration = round(60 * DS_Setting('turksim.maint_hours_gen', 1));
                }

                // Set Aircraft Status
                $ds_maint->aircraft->status = 'M';
                $ds_maint->aircraft->save();
                // Write Current Operation
                $ds_maint->act_note = $check;
                $ds_maint->act_start = Carbon::now();
                $ds_maint->act_end = Carbon::now()->addMinutes($duration);
                $ds_maint->save();

                Log::info('Disposable Special | ' . $ds_maint->aircraft->registration . ' grounded until ' . Carbon::now()->addMinutes($duration));
            }
        }

        // Return Cost Only
        if ($flight_only === true) {
            return $maintenance_cost;
        }

        $airline = $aircraft->airline;

        $amount = Money::createFromAmount($maintenance_cost);
        $financeSvc = app(FinanceService::class);

        $financeSvc->debitFromJournal(
            $airline->journal,
            $amount,
            $aircraft,
            $check . ' for Reg=' . $aircraft->registration,
            'Maintenance Fees',
            'maintenance',
            Carbon::now()->format('Y-m-d')
        );
    }

    // Charger User Method
    public function ChargeUser($pirep, $amount, $memo)
    {
        // Check if it is charged before and return if true
        $check_where = [];
        $check_where['ref_model_id'] = $pirep->user->id;
        $check_where[] = ['ref_model', 'LIKE', '%User'];
        $check_where[] = ['memo', 'LIKE', '%' . $pirep->id];

        $check = JournalTransaction::where($check_where)->count();

        if ($check > 0) {
            Log::debug('Disposable Special | User ' . $pirep->user->name_private . ' ALREADY charged for ' . $memo . ' Pirep=' . $pirep->id . ' SKIPPING');
            return;
        }

        $amount = Money::createFromAmount($amount);
        $financeSvc = app(FinanceService::class);

        // Charge User
        $financeSvc->debitFromJournal(
            $pirep->user->journal,
            $amount,
            $pirep->user,
            $memo . ' Pirep=' . $pirep->id,
            'Maintenance Fees',
            'maintenance',
            Carbon::now()->format('Y-m-d')
        );

        // Credit Airline
        $financeSvc->creditToJournal(
            $pirep->aircraft->airline->journal,
            $amount,
            $pirep->user,
            $memo . ' User=' . $pirep->user->name_private . ' Pirep=' . $pirep->id,
            'Maintenance Fees',
            'maintenance',
            Carbon::now()->format('Y-m-d')
        );
        // Note Transaction
        Log::debug('Disposable Special | User ' . $pirep->user->name_private . ' charged for ' . $memo . ' Pirep=' . $pirep->id);
    }
}
