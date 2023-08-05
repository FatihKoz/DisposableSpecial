<?php

namespace Modules\DisposableSpecial\Listeners;

use App\Events\Expenses;
use App\Models\Expense;
use App\Models\Enums\ExpenseType;
use Illuminate\Support\Facades\Log;

class Expense_Enroute
{
    // Return a list of additional expenses as an ARRAY
    public function handle(Expenses $event)
    {
        $expenses = [];
        $group = 'Enroute Fees';

        $atc_method = DS_Setting('turksim.expense_atcmethod', 'disabled'); // DISABLED, TOW or MTOW
        $atc_base = DS_Setting('turksim.expense_atcbase', 2.586); // Base Unit Rate

        if ($atc_method === 'disabled') {
            return $expenses;
        }

        $units = DS_GetUnits();
        $pirep = $event->pirep;
        $pirep->loadMissing('aircraft', 'arr_airport', 'dpt_airport');
        $aircraft = $pirep->aircraft;
        $orig = $pirep->dpt_airport;
        $dest = $pirep->arr_airport;

        $tow = null;
        $mtow = null;
        $unit_rate = is_numeric($atc_base) ? $atc_base : null;
        $distance = is_numeric($pirep->distance->internal()) ? $pirep->distance->toUnit('km', 2) : null;
        $distance_factor = is_numeric($distance) ? round($distance / 100, 2) : null;
        $time_factor = is_numeric($pirep->flight_time) ? round($pirep->flight_time / 100, 2) : null;

        if ($orig && $dest && $orig->country === $dest->country) {
            $unit_rate = $unit_rate * 0.60;
            // Log::debug('Disposable Special, Enroute Fees, Flight is Domestic, Country=' . $orig->country . ' Pirep=' . $pirep->id);
        }

        if ($orig && $dest) {
            $gc_distance = DS_CalculateDistance($orig->id, $dest->id, 'km');
            $distance_factor = is_numeric($gc_distance) ? round($gc_distance / 100, 2) : $distance_factor;
        }

        if ($aircraft && is_numeric($aircraft->mtow)) {
            $mtow = ($units['weight'] === 'lbs') ? round($aircraft->mtow / 2.20462262185, 2) : $aircraft->mtow;
        }

        if ($atc_method === 'tow') {
            $acars_tow = optional($pirep->fields->where('slug', 'takeoff-weight')->first())->value;
            $tow = is_numeric($acars_tow) ? round($acars_tow / 2.20462262185, 2) : null;
        }

        $base_weight = is_numeric($tow) ? $tow : $mtow;
        $distance_factor = is_numeric($distance_factor) ? $distance_factor : $time_factor;

        // Air Traffic Services Fee
        if ($atc_method != 'none' && is_numeric($base_weight) &&  is_numeric($distance_factor) && is_numeric($unit_rate)) {
            $weight_factor = round(sqrt($base_weight / 50), 2);
            $atc_fee = round($distance_factor * $weight_factor * $unit_rate, 2);
            // Log::debug('Disposable Special, ATC Services details Distance Factor=' . $distance_factor . ' Weight Factor=' . $weight_factor . ' for ' . $base_weight . ' ' . $units['weight']);
            $expenses[] = new Expense([
                'type'              => ExpenseType::FLIGHT,
                'amount'            => $atc_fee,
                'transaction_group' => $group,
                'name'              => 'Air Traffic Services',
                'multiplier'        => false,
                'charge_to_user'    => false
            ]);
        }

        return $expenses;
    }
}
