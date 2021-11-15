<?php

namespace Modules\DisposableSpecial\Listeners;

use App\Events\Expenses;
use App\Models\Expense;
use App\Models\Enums\ExpenseType;
use Illuminate\Support\Facades\DB;
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

        // Return Empty Array (Admin Settings)
        if ($atc_method === 'disabled') {
            return $expenses;
        }

        $units = DS_GetUnits();
        $pirep = $event->pirep;
        $aircraft = $pirep->aircraft;
        $orig = $pirep->dpt_airport;
        $dest = $pirep->arr_airport;

        $tow = null;
        $mtow = null;
        $unit_rate = is_numeric($atc_base) ? $atc_base : null;
        $distance = is_numeric($pirep->distance) ? round($pirep->distance * 1.852, 2) : null;
        $distance_factor = is_numeric($distance) ? round($distance / 100, 2) : null;
        $time_factor = is_numeric($pirep->flight_time) ? round($pirep->flight_time / 100, 2) : null;

        // Domestic Check
        if ($orig && $dest && $orig->country === $dest->country) {
            $unit_rate = $unit_rate * 0.60;
            Log::debug('Disposable Special, Enroute Fees, Flight is Domestic, Country=' . $orig->country . ' Pirep=' . $pirep->id);
        } elseif ($orig && $dest && $orig->country != $dest->country) {
            Log::debug('Disposable Special, Enroute Fees, Flight is International, Countries=' . $orig->country . '-' . $dest->country . ' Pirep=' . $pirep->id);
        } else {
            Log::debug('Disposable Special, Enroute Fees, Flight considered International, Country Check Not Possible ! Pirep=' . $pirep->id);
        }

        // Calculate Proper Distance Factor (KM)
        if ($orig && $dest) {
            $gc_distance = DS_CalculateDistance($orig->id, $dest->id, 'km');
            $distance_factor = is_numeric($gc_distance) ? round($gc_distance / 100, 2) : $distance_factor;
        }

        // Get MTOW and TOW (KG)
        if ($aircraft && is_numeric($aircraft->mtow)) {
            $mtow = $aircraft->mtow;
            if ($units['weight'] === 'lbs') {
                $mtow = round($mtow / 2.20462262185, 2);
            }
        }

        if ($atc_method === 'tow') {
            $acars_tow = DB::table('pirep_field_values')->select('value')->where(['pirep_id' => $pirep->id, 'slug' => 'takeoff-weight'])->first();

            if ($acars_tow && is_numeric($acars_tow->value)) {
                $tow = round($acars_tow->value / 2.20462262185, 2);
            }
        }

        // Use TOW or MTOW, Distance or Time
        $base_weight = is_numeric($tow) ? $tow : $mtow;
        $distance_factor = is_numeric($distance_factor) ? $distance_factor : $time_factor;

        // Air Traffic Services Fee
        if ($atc_method != 'none' && is_numeric($base_weight) &&  is_numeric($distance_factor) && is_numeric($unit_rate)) {

            // Calculate ATC Cost
            $weight_factor = round(sqrt($base_weight / 50), 2);
            $atc_fee = round($distance_factor * $weight_factor * $unit_rate, 2);

            Log::debug('Disposable Special, ATC Services details Distance Factor=' . $distance_factor . ' Weight Factor=' . $weight_factor . ' for ' . $base_weight . ' ' . $units['weight']);
            // Send in ATC Fee
            $expenses[] = new Expense([
                'type' => ExpenseType::FLIGHT,
                'amount' => $atc_fee,
                'transaction_group' => $group,
                'name' => 'Air Traffic Services',
                'multiplier' => false,
                'charge_to_user' => false
            ]);
        }

        // Return The Array To Pirep Finance Service
        return $expenses;
    }
}
