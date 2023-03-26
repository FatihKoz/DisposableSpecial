<?php

use App\Contracts\Migration;
use App\Models\Flight;

class UpdatePersonalFlights extends Migration
{
    public function up()
    {
        $pfs = Flight::where('route_code', 'LIKE', 'PF%')->where('flight_type', 'E')->get();

        foreach ($pfs as $pf) {
            $pf->user_id = ltrim($pf->route_code, 'PF');
            $pf->route_code = 'PF';
            $pf->save();
        }
    }
}
