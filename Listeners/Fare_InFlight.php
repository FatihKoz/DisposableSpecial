<?php

namespace Modules\DisposableSpecial\Listeners;

use App\Events\Fares;
use App\Models\Fare;
use App\Models\Enums\FareType;
use Illuminate\Support\Facades\Log;

class Fare_InFlight
{
    // Return InFlight Income Items (Duty Free, Bouffet Sales etc)
    public function handle(Fares $event)
    {
        $fares = [];
        $group = 'InFlight Sales';

        $df_method = DS_Setting('turksim.income_dfmethod', 'disabled'); // DISABLED, INT or ALL
        $bs_method = DS_Setting('turksim.income_bsmethod', 'disabled'); // DISABLED, INT, DOM or ALL

        // Return Empty Array (Admin Settings)
        if ($df_method === 'disabled' && $bs_method === 'disabled') {
            return $fares;
        }

        // Airlines NOT offering Bouffet Sales
        $airline_codes = array('THY', 'SVA', 'AHO');

        $pirep = $event->pirep;
        $orig = $pirep->dpt_airport;
        $dest = $pirep->arr_airport;
        $int = true;
        $pax = null;

        // Get Passenger Count of the Flight
        if ($pirep->fares->count() > 0) {
            $act_pax = 0;
            foreach ($pirep->fares as $fare) {
                if ($fare->fare->type === FareType::PASSENGER) {
                    $act_pax = $act_pax + $fare->count;
                }
            }

            if ($act_pax > 0) {
                $pax = $act_pax;
            }
        }

        // Return Empty Array (No Pax, No Sale)
        if (!is_numeric($pax)) {
            return $fares;
        }

        Log::debug('Disposable Special, InFlight Sales | Flight=' . $pirep->airline->code . $pirep->flight_number . ' Total Passengers=' . $pax . ' Pirep=' . $pirep->id);

        // See If This is a Domestic Flight or Not
        if ($orig && $dest && $orig->country === $dest->country) {
            Log::debug('Disposable Special, InFlight Sales | Flight is Domestic, Country=' . $orig->country);
            $int = false;
        } elseif ($orig && $dest && $orig->country != $dest->country) {
            Log::debug('Disposable Special, InFlight Sales | Flight is International, Countries=' . $orig->country . '-' . $dest->country);
        } else {
            Log::debug('Disposable Special, InFlight Sales | Flight considered International, Country Check Not Possible!');
        }

        // Duty Free Sales
        if ($df_method === 'int' && $int === true || $df_method === 'all') {

            $memo = 'InFlight Sales | Duty Free';
            // Price ARRAY for DutyFree items and Cost Factor
            $df_prices = explode(',', DS_Setting('turksim.income_dfprices', '5,10,15,20,25,30,35,40,45,50,55,60,65,70')); // Array of available items
            $df_cost = round((100 - intval(DS_Setting('turksim.income_dfprofit', '35'))) / 100, 2); // 0.65 Cost of each DutyFree item %35 profit on each item

            $buyers = rand(0, $pax);
            $income = 0;
            $cost = 0;

            for ($i = 0; $i <= $buyers; $i++) {
                // Pick a random item price from prices array
                $item_price = $df_prices[array_rand($df_prices)];
                $income = $income + $item_price;
                $cost = $cost + ($item_price * $df_cost);
            }

            if ($income > 0) {
                Log::debug('Disposable Special, DutyFree Sale Customers=' . $buyers . ' Profit=' . round($income - $cost) . ' ' . setting('units.currency'));
                // Send in the DutyFree Sales
                $fares[] = new Fare([
                    'name'  => $memo,
                    'type'  => FareType::PASSENGER,
                    'price' => $income,
                    'cost'  => $cost,
                    'notes' => $group,
                ]);
            }
        }

        // No Bouffet Sales for defined airlines (Flag Carriers or so)
        if (in_array($pirep->airline->icao, $airline_codes)) {
            Log::debug('Disposable Special, Bouffet Sale NOT possible for Airline=' . $pirep->airline->icao . ' Pirep=' . $pirep->id);
            return $fares;
        }

        // Bouffet Sales
        if ($bs_method === 'int' && $int === true || $bs_method === 'dom' && $int === false || $bs_method === 'all') {

            $memo = 'InFlight Sales | Cabin Bouffet';
            // Price ARRAY for Bouffet items and Cost Factor (like coffee,tea,snacks,sandwiches,soda,beer etc)
            $bs_prices = explode(',', DS_Setting('turksim.income_bsprices', '1.5,2,2.5,3,4,5,6,7,8,9,10,11,14,18,20')); // Array of available items
            $bs_cost = round((100 - intval(DS_Setting('turksim.income_bsprofit', '45'))) / 100, 2); // 0.55 Cost of each Bouffet item %45 profit on each item

            $buyers = rand(0, $pax);
            $income = 0;
            $cost = 0;

            for ($i = 0; $i <= $buyers; $i++) {
                // Pick a random item price from prices array
                $item_price = $bs_prices[array_rand($bs_prices)];
                $income = $income + $item_price;
                $cost = $cost + ($item_price * $bs_cost);
            }

            if ($income > 0) {
                Log::debug('Disposable Special, Bouffet Sale Customers=' . $buyers . ' Profit=' . round($income - $cost) . ' ' . setting('units.currency'));
                // Send in Bouffet Sales
                $fares[] = new Fare([
                    'name'  => $memo,
                    'type'  => FareType::PASSENGER,
                    'price' => $income,
                    'cost'  => $cost,
                    'notes' => $group,
                ]);
            }
        }

        // Return The Array To Pirep Finance Service
        return $fares;
    }
}
