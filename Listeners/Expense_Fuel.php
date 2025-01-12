<?php

namespace Modules\DisposableSpecial\Listeners;

use App\Events\Expenses;
use App\Models\Enums\ExpenseType;
use App\Models\Enums\FuelType;
use App\Models\Enums\PirepState;
use App\Models\Expense;
use App\Models\Pirep;

class Expense_Fuel
{
    // Return a list of additional expenses as an ARRAY
    public function handle(Expenses $event)
    {
        $expenses = [];
        $group = 'Fuel Services';

        $fs_service = DS_Setting('turksim.expense_fuel_srv', false);
        $fs_drain = DS_Setting('turksim.expense_fuel_drn', false);
        $fs_low = DS_Setting('turksim.expense_fuel_low', false);
        $fs_tax = DS_Setting('turksim.expense_fuel_tax', false);

        if (!$fs_service && !$fs_drain && !$fs_low && !$fs_tax) {
            return $expenses;
        }

        $fuel_margin = DS_Setting('turksim.expense_fuel_margin', 220); // Pounds, anything above this value will be considered
        $fuel_service_cost = DS_Setting('turksim.expense_fuel_srvcost', 0.01);
        $drain_service_cost = DS_Setting('turksim.expense_fuel_drncost', 0.05);
        $fuel_lowuplift_cost = DS_Setting('turksim.expense_fuel_lowcost', 250);
        $fuel_lowuplift_limit = DS_Setting('turksim.expense_fuel_lowlimit', 1769.95); // Pounds -> 1000 Liters
        $fuel_domestic_tax = DS_Setting('turksim.expense_fuel_domtax', 6); // Percentage of VAT or similar like %7
        $domestic = false;

        $pirep = $event->pirep;
        $pirep->loadMissing('aircraft.subfleet', 'arr_airport', 'dpt_airport');

        $aircraft = $pirep->aircraft;
        $subfleet = filled($aircraft) ? $aircraft->subfleet : null;
        $fuel_type = filled($subfleet) ? $subfleet->fuel_type : FuelType::JET_A;
        $orig = $pirep->dpt_airport;
        $dest = $pirep->arr_airport;

        // Domestic Check
        if ($orig && $dest && $orig->country === $dest->country) {
            $domestic = true;
            if ($orig->country === 'TR' && $dest->country === 'TR') {
                $fuel_domestic_tax = 18;
            }
        }

        // Get Fuel Price
        if ($orig && $fuel_type === FuelType::JET_A) {
            $fuel_cost = !empty($orig->fuel_jeta_cost) ? $orig->fuel_jeta_cost : setting('airports.default_jet_a_fuel_cost');
        } elseif ($orig && $fuel_type === FuelType::LOW_LEAD) {
            $fuel_cost = !empty($orig->fuel_100ll_cost) ? $orig->fuel_100ll_cost : setting('airports.default_100ll_fuel_cost');
        } elseif ($orig && $fuel_type === FuelType::MOGAS) {
            $fuel_cost = !empty($orig->fuel_mogas_cost) ? $orig->fuel_mogas_cost : setting('airports.default_mogas_fuel_cost');
        } elseif ($fuel_type === FuelType::LOW_LEAD) {
            $fuel_cost = setting('airports.default_100ll_fuel_cost');
        } elseif ($fuel_type === FuelType::MOGAS) {
            $fuel_cost = setting('airports.default_mogas_fuel_cost');
        } else {
            $fuel_cost = setting('airports.default_jet_a_fuel_cost');
        }

        // Get Proper Fuel Amount (By Checking PhpVms Settings and Remaining Fuel From Prev Flight)
        if (setting('pireps.advanced_fuel', false)) {
            $prev_flight = Pirep::where(['aircraft_id' => $pirep->aircraft_id, 'state' => PirepState::ACCEPTED])
                ->where('submitted_at', '<=', $pirep->submitted_at)
                ->orderby('submitted_at', 'desc')->skip(1)->first();

            if ($prev_flight) {
                $fuel_amount = $pirep->block_fuel->internal(2) - ($prev_flight->block_fuel->internal(2) - $prev_flight->fuel_used->internal(2));
                if ($fuel_amount < 0 && $fuel_type === FuelType::JET_A) {
                    $drain_amount = abs($fuel_amount);
                    $fuel_amount = 0;
                }
            } else {
                $fuel_amount = $pirep->block_fuel->internal(2);
            }
        } else {
            $fuel_amount = $pirep->fuel_used->internal(2);
        }

        // Apply Fuel Draining or De-Fuelling Cost (per drained amount)
        if ($fs_drain && isset($drain_amount) && $drain_amount > $fuel_margin) {
            $drain_cost = round($drain_amount * $drain_service_cost, 2);

            $expenses[] = new Expense([
                'type'              => ExpenseType::FLIGHT,
                'amount'            => $drain_cost,
                'transaction_group' => $group,
                'name'              => 'De-Fuelling Service',
                'multiplier'        => true,
                'charge_to_user'    => false,
            ]);
        }

        // Apply Fuel Service Cost (per uplifted amount)
        if ($fs_service && $fuel_amount > $fuel_margin) {
            $service_cost = round($fuel_amount * $fuel_service_cost, 2);

            $expenses[] = new Expense([
                'type'              => ExpenseType::FLIGHT,
                'amount'            => $service_cost,
                'transaction_group' => $group,
                'name'              => 'Fuel Service',
                'multiplier'        => true,
                'charge_to_user'    => false,
            ]);
        }

        // Apply Low Fuel Uplift Extra
        if ($fs_low && $fuel_type === FuelType::JET_A && $fuel_amount > $fuel_margin && $fuel_amount < $fuel_lowuplift_limit) {
            $extra_service_cost = $fuel_lowuplift_cost;

            $expenses[] = new Expense([
                'type'              => ExpenseType::FLIGHT,
                'amount'            => $extra_service_cost,
                'transaction_group' => $group,
                'name'              => 'Fuel Service (Low Uplift Charge)',
                'multiplier'        => false,
                'charge_to_user'    => false,
            ]);
        }

        // Apply Fuel Tax To Domestic Flights
        if ($fs_tax && $domestic && $fuel_amount > $fuel_margin) {
            $fuel_tax = round($fuel_domestic_tax / 100, 2);
            $tax_cost = round($fuel_amount * ($fuel_cost * $fuel_tax), 2);

            $expenses[] = new Expense([
                'type'              => ExpenseType::FLIGHT,
                'amount'            => $tax_cost,
                'transaction_group' => $group,
                'name'              => 'Fuel Tax (Domestic Flight)',
                'multiplier'        => false,
                'charge_to_user'    => false,
            ]);
        }

        return $expenses;
    }
}
