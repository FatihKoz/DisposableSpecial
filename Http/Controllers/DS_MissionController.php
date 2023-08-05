<?php

namespace Modules\DisposableSpecial\Http\Controllers;

use App\Contracts\Controller;
use App\Models\Aircraft;
use App\Models\Airport;
use App\Models\Flight;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\DisposableSpecial\Models\DS_Mission;

class DS_MissionController extends Controller
{
    public function index()
    {
        $now = Carbon::now();
        $margin = DS_Setting('dspecial.missions_margin', 3); // should be added to settings
        $basereturn = DS_Setting('dspecial.rebase_parked_aircraft', 0);

        $my_missions = DS_Mission::with('aircraft.airline', 'flight.airline', 'dpt_airport', 'arr_airport')->where('user_id', Auth::id())->whereNull('pirep_id')->orderBy('mission_order')->get();
        $used_aircraft = DS_Mission::whereNull('pirep_id')->orderBy('aircraft_id')->pluck('aircraft_id')->toArray();

        $aircraft = Aircraft::with('subfleet', 'airline')->whereNotIn('id', $used_aircraft)->whereNotNull('landing_time')->where('landing_time', '<', Carbon::now()->subDays($margin))->get();

        // Holds the list of leftover aircraft, dep, arr airports and suitable flights
        // array key is the registration of the leftover aircraft
        // ac = Aircraft Model (of the leftover aircraft)
        // dep = Airport Model (of the current location)
        // arr = Airport Model (of the Hub)
        // flt = Flight Model (Random flight between dep-arr airports for the company)
        // end = Carbon Object (Validity of the mission by DispoSpecial settings)
        $sc_missions = [];
        $fr_missions = [];

        foreach ($aircraft as $ac) {

            $valid_until =  $ac->landing_time->addDays($basereturn);
            $hub_id = filled($ac->hub_id) ? $ac->hub_id : optional($ac->subfleet)->hub_id;

            $where = [
                'airline_id'     => $ac->airline->id,
                'dpt_airport_id' => $ac->airport_id,
                'arr_airport_id' => $hub_id,
            ];

            if ($hub_id && $valid_until > $now && $ac->airport_id != $hub_id) {

                // Find flights between airports and randomly pick one
                $flt = Flight::with('airline')->where($where)->inRandomOrder()->first();

                // Prepare mission array contents
                $contents = [
                    'ac'  => $ac,
                    'dep' => Airport::where('id', $ac->airport_id)->first(),
                    'arr' => Airport::where('id', $hub_id)->first(),
                    'flt' => $flt,
                    'end' => $valid_until,
                ];

                if ($flt) {
                    // Flight found, add contents to scheduled missions
                    $sc_missions[$ac->registration] = $contents;
                } else {
                    // Flight NOT found, add contents to freeflight missions
                    $fr_missions[$ac->registration] = $contents;
                }
            }
        }

        return view('DSpecial::missions.index', [
            'sc_missions' => $sc_missions,
            'fr_missions' => $fr_missions,
            'my_missions' => $my_missions,
        ]);
    }

    // Store Mission (add/update to missions table for user)
    public function store(Request $request)
    {

        if (!$request->aircraft_id || !$request->flight_id) {
            flash()->error('Aircraft and Flight info are required for Missions!');
            return back();
        }

        DS_Mission::updateOrCreate(
            [
                'id' => $request->id,
            ],
            [
                'user_id'        => Auth::id(),
                'aircraft_id'    => $request->aircraft_id,
                'flight_id'      => $request->flight_id,
                'dpt_airport_id' => $request->dpt_airport_id,
                'arr_airport_id' => $request->arr_airport_id,
                'mission_type'   => $request->mission_type,
                'mission_year'   => Carbon::now()->year,
                'mission_month'  => Carbon::now()->month,
                'mission_order'  => DS_Mission::where('user_id', Auth::id())->count() + 1,
                'mission_valid'  => $request->mission_valid,
            ]
        );

        flash()->success('Mission Saved');
        return back();
    }
}
