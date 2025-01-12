<?php

namespace Modules\DisposableSpecial\Listeners;

use App\Events\Fares;
use App\Models\Enums\FareType;
use App\Models\Fare;

class Fare_InFlight
{
    // Return InFlight Income Items (Duty Free, Bouffet Sales etc)
    public function handle(Fares $event)
    {
        $fares = [];
        $group = 'InFlight Sales';

        $df_method = DS_Setting('turksim.income_dfmethod', 'disabled'); // DISABLED, INT or ALL
        $bs_method = DS_Setting('turksim.income_bsmethod', 'disabled'); // DISABLED, INT, DOM or ALL

        if ($df_method === 'disabled' && $bs_method === 'disabled') {
            return $fares;
        }
        // Airlines NOT offering Bouffet Sales
        $airline_codes = ['THY', 'SVA', 'AHO'];

        $pirep = $event->pirep;
        $pirep->loadMissing('airline', 'arr_airport', 'dpt_airport', 'fares.fare');

        $airline = $pirep->airline;
        $orig = $pirep->dpt_airport;
        $dest = $pirep->arr_airport;
        // $currency = setting('units.currency');
        $int = ($orig && $dest && $orig->country === $dest->country) ? false : true;
        $pax = null;

        if ($pirep->fares->count() > 0) {
            $act_pax = 0;
            foreach ($pirep->fares as $fare) {
                if ($fare->type === FareType::PASSENGER) {
                    $act_pax = $act_pax + $fare->count;
                }
            }
            if ($act_pax > 0) {
                $pax = $act_pax;
            }
        }

        if (!is_numeric($pax)) {
            return $fares;
        }

        if ($df_method === 'int' && $int === true || $df_method === 'all') {
            // Duty Free Sales
            $memo = 'InFlight Sales | Duty Free';
            $df_prices = explode(',', DS_Setting('turksim.income_dfprices', '5,10,15,20,25,30,35,40,45,50,55,60,65,70')); // Array of available items
            $df_cost = round((100 - intval(DS_Setting('turksim.income_dfprofit', '35'))) / 100, 2); // 0.65 Cost of each DutyFree item %35 profit on each item

            $buyers = rand(0, $pax);
            $income = 0;
            $cost = 0;

            for ($i = 0; $i <= $buyers; $i++) {
                $item_price = $df_prices[array_rand($df_prices)];
                $income = $income + $item_price;
                $cost = $cost + ($item_price * $df_cost);
            }

            if ($income > 0) {
                $fares[] = new Fare([
                    'name'  => $memo,
                    'type'  => FareType::PASSENGER,
                    'price' => $income,
                    'cost'  => $cost,
                    'notes' => $group,
                ]);
            }
        }

        if (in_array($airline->icao, $airline_codes)) {
            return $fares;
        }

        if ($bs_method === 'int' && $int === true || $bs_method === 'dom' && $int === false || $bs_method === 'all') {
            // Bouffet Sales
            $memo = 'InFlight Sales | Cabin Bouffet';
            $bs_prices = explode(',', DS_Setting('turksim.income_bsprices', '1.5,2,2.5,3,4,5,6,7,8,9,10,11,14,18,20')); // Array of available item prices (coffee,tea,snack etc)
            $bs_cost = round((100 - intval(DS_Setting('turksim.income_bsprofit', '45'))) / 100, 2); // 0.55 Cost of each Bouffet item %45 profit on each item

            $buyers = rand(0, $pax);
            $income = 0;
            $cost = 0;

            for ($i = 0; $i <= $buyers; $i++) {
                $item_price = $bs_prices[array_rand($bs_prices)];
                $income = $income + $item_price;
                $cost = $cost + ($item_price * $bs_cost);
            }

            if ($income > 0) {
                $fares[] = new Fare([
                    'name'  => $memo,
                    'type'  => FareType::PASSENGER,
                    'price' => $income,
                    'cost'  => $cost,
                    'notes' => $group,
                ]);
            }
        }

        return $fares;
    }
}
