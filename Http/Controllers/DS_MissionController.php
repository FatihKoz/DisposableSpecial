<?php

namespace Modules\DisposableSpecial\Http\Controllers;

use App\Contracts\Controller;
use App\Models\Aircraft;
use App\Models\Airport;
use App\Models\Flight;
use App\Services\UserService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\DisposableSpecial\Models\DS_Maintenance;
use Modules\DisposableSpecial\Models\DS_Mission;

class DS_MissionController extends Controller
{
    public function index()
    {
        $now = Carbon::now();
        $user = Auth::user();
        $margin = DS_Setting('dspecial.missions_margin', 3); // should be added to settings
        $basereturn = DS_Setting('dspecial.rebase_parked_aircraft', 0);

        $my_missions = DS_Mission::with('aircraft.airline', 'flight.airline', 'dpt_airport', 'arr_airport')->where('user_id', $user->id)->whereNull('pirep_id')->orderBy('mission_order')->get();

        // Get the list of aircraft that the user is allowed to fly
        $userSvc = app(UserService::class);
        $restricted_to = $userSvc->getAllowableSubfleets($user);
        $allowed_sf = $restricted_to->pluck('id')->toArray();
        $allowed_aircraft = Aircraft::whereIn('subfleet_id', $allowed_sf)->pluck('id')->toArray();

        $used_aircraft = DS_Mission::whereNull('pirep_id')->orderBy('aircraft_id')->pluck('aircraft_id')->toArray();

        $aircraft = Aircraft::with('subfleet', 'airline')->where('landing_time', '<', Carbon::now()->subDays($margin))
            ->whereIn('id', $allowed_aircraft)
            ->whereNotIn('id', $used_aircraft)
            ->whereNotNull('landing_time')
            ->orderBy('landing_time')->get();

        $maintenance = DS_Maintenance::with('aircraft')->whereNull(['act_note', 'act_start', 'act_end'])
            ->whereIn('aircraft_id', $allowed_aircraft)
            ->where(function ($query) {
                $query->where('curr_state', '<', 77)
                    ->orWhere('rem_ta', '<', 300)
                    ->orWhere('rem_tb', '<', 300)
                    ->orWhere('rem_tc', '<', 300)
                    ->orWhere('rem_ca', '<', 2)
                    ->orWhere('rem_cb', '<', 2)
                    ->orWhere('rem_cc', '<', 2);
            })->orderby('aircraft_id')->get();

        // Holds the list of leftover aircraft, dep, arr airports and suitable flights
        // array key is the registration of the leftover aircraft
        // ac = Aircraft Model
        // dep = Airport Model (of the current location)
        // arr = Airport Model (of the Hub)
        // flt = Flight Model (Random flight between dep-arr airports for the owner company if available)
        // end = Carbon Object (Validity of the mission)
        $sc_missions = [];
        $mt_missions = [];

        // Create missions for leftover aircraft
        foreach ($aircraft as $ac) {
            $valid_until = $ac->landing_time->addDays($basereturn);
            $hub_id = filled($ac->hub_id) ? $ac->hub_id : optional($ac->subfleet)->hub_id;

            $where = [
                'airline_id'     => $ac->airline->id,
                'dpt_airport_id' => $ac->airport_id,
                'arr_airport_id' => $hub_id,
            ];

            if ($hub_id && $valid_until > $now && $ac->airport_id != $hub_id) {
                // Prepare mission array contents
                $contents = [
                    'ac'  => $ac,
                    'dep' => Airport::where('id', $ac->airport_id)->first(),
                    'arr' => Airport::where('id', $hub_id)->first(),
                    'flt' => Flight::with('airline')->where($where)->inRandomOrder()->first(),
                    'end' => $valid_until,
                ];

                $sc_missions[$ac->registration] = $contents;
            }
        }

        // Create missions for maintenace required aircraft
        foreach ($maintenance as $mt) {
            if (!$mt->aircraft) {
                continue;
            }

            $hub_id = filled(optional($mt->aircraft)->hub_id) ? $mt->aircraft->hub_id : optional(optional($mt->aircraft)->subfleet)->hub_id;

            $where = [
                'airline_id'     => $mt->aircraft->airline->id,
                'dpt_airport_id' => $mt->aircraft->airport_id,
                'arr_airport_id' => $hub_id,
            ];

            if ($hub_id && $mt->aircraft->airport_id != $hub_id) {
                // Prepare mission array contents
                $contents = [
                    'ac'  => $mt->aircraft,
                    'dep' => Airport::where('id', $mt->aircraft->airport_id)->first(),
                    'arr' => Airport::where('id', $hub_id)->first(),
                    'flt' => Flight::with('airline')->where($where)->inRandomOrder()->first(),
                    'end' => $now->copy()->addHours(48),
                ];

                $mt_missions[$mt->aircraft->registration] = $contents;
            }
        }

        return view('DSpecial::missions.index', [
            'sc_missions' => $sc_missions,
            'mt_missions' => $mt_missions,
            'my_missions' => $my_missions,
        ]);
    }

    // Store Mission (add/update to missions table for user)
    public function store(Request $request)
    {
        if ($request->remove_id && $request->action === 'remove') {
            DS_Mission::where('id', $request->remove_id)->delete();
            flash()->success('Mission Deleted');

            return back();
        }

        if (!$request->aircraft_id) {
            flash()->error('Aircraft required for Missions!');

            return back();
        }

        $user = Auth::user();

        DS_Mission::updateOrCreate(
            [
                'aircraft_id'    => $request->aircraft_id,
                'flight_id'      => $request->flight_id,
            ],
            [
                'user_id'        => $user->id,
                'aircraft_id'    => $request->aircraft_id,
                'flight_id'      => $request->flight_id,
                'dpt_airport_id' => $request->dpt_airport_id,
                'arr_airport_id' => $request->arr_airport_id,
                'mission_type'   => $request->mission_type,
                'mission_year'   => Carbon::now()->year,
                'mission_month'  => Carbon::now()->month,
                'mission_order'  => DS_Mission::where('user_id', $user->id)->max('mission_order') + 1,
                'mission_valid'  => $request->mission_valid,
            ]
        );

        flash()->success('Mission Saved/Updated Successfully');

        return back();
    }
}
