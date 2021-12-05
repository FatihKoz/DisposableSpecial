<?php

namespace Modules\DisposableSpecial\Http\Controllers;

use App\Contracts\Controller;
use App\Models\Aircraft;
use App\Models\Airline;
use App\Models\Bid;
use App\Models\Flight;
use App\Models\Subfleet;
use App\Models\User;
use App\Models\Enums\AircraftState;
use App\Models\Enums\AircraftStatus;
use App\Services\AirportService;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DS_FreeFlightController extends Controller
{
    public function index()
    {
        $settings = [];
        $settings['ac_rank'] = setting('pireps.restrict_aircraft_to_rank', true);
        $settings['ac_rating'] = setting('pireps.restrict_aircraft_to_typerating', false);
        $settings['sb_block'] = setting('simbrief.block_aircraft', false);
        $settings['sb_callsign'] = setting('simbrief.callsign', false);
        $settings['pilot_company'] = setting('pilots.restrict_to_company', false);
        $settings['pilot_location'] = setting('pilots.only_flights_from_current', false);

        $eager_user = ['airline', 'last_pirep', 'rank'];

        $user = User::with($eager_user)->find(Auth::id());
        $user_loc = optional($user)->curr_airport_id ?? optional($user)->home_airport_id;

        $al_where = [];
        $al_where['active'] = 1;

        $sf_where = [];
        $allowed_sf = [];

        if ($settings['ac_rank'] || $settings['ac_rating']) {
            $userSvc = app(UserService::class);
            $restricted_to = $userSvc->getAllowableSubfleets($user);
            $allowed_sf = $restricted_to->pluck('id')->toArray();
        }

        if ($settings['pilot_company']) {
            $al_where['id'] = $user->airline_id;
            $sf_where['airline_id'] = $user->airline_id;

            $airline_sf = Subfleet::where($sf_where)->pluck('id')->toArray();
            $allowed_sf = filled($allowed_sf) ? array_intersect($allowed_sf, $airline_sf) : $airline_sf;
        }

        // Get airlines
        $airlines = Airline::where($al_where)->orderby('name')->get();

        // Get available Aircraft
        $ac_where = [];
        $ac_where['state'] = AircraftState::PARKED;
        $ac_where['status'] = AircraftStatus::ACTIVE;

        if ($user_loc && $settings['pilot_location']) {
            $ac_where['airport_id'] = $user_loc;
        }

        $withCount = ['simbriefs' => function ($query) {
            $query->whereNull('pirep_id');
        }];
        $aircraft = Aircraft::withCount($withCount)
            ->where($ac_where)
            ->when(($settings['ac_rank'] || $settings['ac_rating'] || $settings['pilot_company']), function ($query) use ($allowed_sf) {
                return $query->whereIn('subfleet_id', $allowed_sf);
            })
            ->when($settings['sb_block'], function ($query) {
                return $query->having('simbriefs_count', 0);
            })->orderby('icao')->orderby('registration')
            ->get();

        $fflight = Flight::firstOrCreate([
            'flight_type' => 'E',
            'route_code'  => 'PF' . $user->id
        ], [
            'airline_id'     => $user->airline_id,
            'flight_number'  => $user->id,
            'flight_type'    => 'E',
            'route_code'     => 'PF' . $user->id,
            'dpt_airport_id' => $user_loc ?? 'ZZZZ',
            'arr_airport_id' => $user->home_airport_id ?? 'ZZZZ',
            'level'          => null,
            'distance'       => null,
            'active'         => 0,
            'visible'        => 0
        ]);

        return view('DSpecial::freeflights.index', [
            'fflight'   => $fflight,
            'aircraft'  => $aircraft,
            'airlines'  => $airlines,
            'settings'  => $settings,
            'user'      => $user,
            'units'     => ['fuel' => setting('units.fuel')],
        ]);
    }

    public function store(Request $request)
    {
        // Check mandatory form fields
        if (strlen(trim($request->ff_orig)) != 4 || strlen(trim($request->ff_dest)) != 4) {
            flash()->error('Check Airport Inputs !');

            return redirect(route('DSpecial.freeflight'));
        }

        if (strlen(trim($request->ff_number)) === 0) {
            flash()->error('Check Flight Number !');

            return redirect(route('DSpecial.freeflight'));
        }

        // Lookup for airports, add if necessary, return back if not found
        $airportSvc = app(AirportService::class);
        $orig = $airportSvc->lookupAirportIfNotFound(trim($request->ff_orig));
        $dest = $airportSvc->lookupAirportIfNotFound(trim($request->ff_dest));

        if (!$orig || !$dest) {
            flash()->error('Airport NOT found !!! Check ICAO codes and try again... Free Flight NOT Saved');

            return redirect(route('DSpecial.freeflight'));
        }

        // Save updated personal flight, create the bid and redirect to flight planning
        $dist = DS_CalculateDistance($orig->icao, $dest->icao);

        $freeflight = Flight::where('id', $request->ff_id)->first();

        $freeflight->airline_id = $request->ff_airlineid;
        $freeflight->flight_number = trim($request->ff_number);
        $freeflight->callsign = !empty($request->ff_callsign) ? trim($request->ff_callsign) : null;
        $freeflight->route_code = 'PF' . $request->user_id;
        $freeflight->dpt_airport_id = $orig->icao;
        $freeflight->arr_airport_id = $dest->icao;
        $freeflight->distance = $dist;
        $freeflight->flight_time = DS_CalculateBlockTime($dist);
        $freeflight->active = 0;
        $freeflight->visible = 0;
        $freeflight->save();

        Bid::firstorCreate(
            ['user_id' => $request->user_id, 'flight_id' => $request->ff_id],
            ['user_id' => $request->user_id, 'flight_id' => $request->ff_id]
        );

        flash()->success('Personal Flight Updated & Bid Inserted');
        $sblink = '?flight_id=' . $request->ff_id;
        if ($request->ff_aircraft != '0') {
            $sblink .= '&aircraft_id=' . $request->ff_aircraft;
        }

        return redirect(route('frontend.simbrief.generate') . $sblink);
    }
}
