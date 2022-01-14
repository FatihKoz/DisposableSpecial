<?php

namespace Modules\DisposableSpecial\Http\Controllers;

use App\Contracts\Controller;
use App\Models\Airline;
use App\Models\Flight;
use App\Models\Subfleet;
use App\Models\User;
use Carbon\Carbon;
use Modules\DisposableSpecial\Models\DS_Tour;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class DS_TourController extends Controller
{
    public function index()
    {
        $tours = DS_Tour::withCount('legs')->with('airline')->where('active', 1)->orderby('start_date')->orderby('tour_name')->get();

        return view('DSpecial::tours.index', [
            'tours'      => $tours, 
            'carbon_now' => Carbon::now(),
        ]);
    }

    public function show($code)
    {
        if (!$code) {
            flash()->error('Tour not specified !');
            return redirect(route('DSpecial.tours'));
        }

        $tour = DS_Tour::withCount('legs')->with([
            'legs' => function ($query) {
                $query->withCount('subfleets');
            },
            'legs.dpt_airport',
            'legs.arr_airport',
            'legs.airline',
            'airline',
        ])->where('tour_code', $code)->first();

        if (!$tour) {
            flash()->error('Tour not found !');
            return redirect(route('DSpecial.tours'));
        }

        // Logged in user
        $user = User::with('current_airport')->find(Auth::id());

        // Map Center
        if ($user && $user->current_airport && filled($tour->legs()->where('dpt_airport_id', $user->current_airport->id))) {
            $user_mapCenter = $user->current_airport->lat . ',' . $user->current_airport->lon;
            $user_loc = $user->current_airport->id;
        } else {
            $tour_mapCenter = setting('acars.center_coords');
        }

        foreach ($tour->legs->where('route_leg', 1) as $fleg) {
            $tour_mapCenter = $fleg->dpt_airport->lat . ',' . $fleg->dpt_airport->lon;
        }

        // Map Icons Array
        $mapIcons = [];

        $vmsUrl = public_asset('/assets/img/acars/marker.png');
        $RedUrl = 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png';
        $GreenUrl = 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png';
        $BlueUrl = 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png';
        $YellowUrl = 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-yellow.png';

        $shadowUrl = 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png';
        $iconSize = [12, 20];
        $shadowSize = [20, 20];

        $mapIcons['vmsIcon'] = json_encode(['iconUrl' => $vmsUrl, 'shadowUrl' => $shadowUrl, 'iconSize' => $iconSize, 'shadowSize' => $shadowSize]);
        $mapIcons['RedIcon'] = json_encode(['iconUrl' => $RedUrl, 'shadowUrl' => $shadowUrl, 'iconSize' => $iconSize, 'shadowSize' => $shadowSize]);
        $mapIcons['GreenIcon'] = json_encode(['iconUrl' => $GreenUrl, 'shadowUrl' => $shadowUrl, 'iconSize' => $iconSize, 'shadowSize' => $shadowSize]);
        $mapIcons['BlueIcon'] = json_encode(['iconUrl' => $BlueUrl, 'shadowUrl' => $shadowUrl, 'iconSize' => $iconSize, 'shadowSize' => $shadowSize]);
        $mapIcons['YellowIcon'] = json_encode(['iconUrl' => $YellowUrl, 'shadowUrl' => $shadowUrl, 'iconSize' => $iconSize, 'shadowSize' => $shadowSize]);

        // Map Airport Array
        $mapAirports = [];

        $airports_pack = collect();
        foreach ($tour->legs as $leg) {
            $airports_pack->push($leg->dpt_airport);
            $airports_pack->push($leg->arr_airport);
        }
        $airports = $airports_pack->unique('id');

        foreach ($airports as $airport) {
            $apop = '<a href="' . route('frontend.airports.show', [$airport->id]) . '" target="_blank">' . $airport->id . ' ' . str_replace("'", "", $airport->name) . '</a>';
            if (isset($user_loc) && $user_loc === $airport->id) {
                $iconColor = "YellowIcon";
            } else {
                $iconColor = "BlueIcon";
            }
            $mapAirports[] = [
                'id'   => $airport->id,
                'loc'  => $airport->lat . ', ' . $airport->lon,
                'pop'  => $apop,
                'icon' => $iconColor,
            ];
        }

        // Map Flights with Leg Checks (via pireps)
        $mapFlights = [];
        $leg_checks = [];

        foreach ($tour->legs as $mf) {
            // Leg Checks
            $leg_checks[$mf->route_leg] = DS_IsTourLegFlown($tour, $mf, optional($user)->id);
            // Popups
            $pop = '<a href="/flights/' . $mf->id . '" target="_blank">Leg #';
            $pop .= $mf->route_leg . ': ' . $mf->airline->code . $mf->flight_number . ' ' . $mf->dpt_airport_id . '-' . $mf->arr_airport_id;
            $pop .= '</a>';
            // Flights with popups and check results
            $mapFlights[] = [
                'id'   => $mf->id,
                'geod' => '[[' . $mf->dpt_airport->lat . ',' . $mf->dpt_airport->lon . '],[' . $mf->arr_airport->lat . ',' . $mf->arr_airport->lon . ']]',
                'geoc' => $leg_checks[$mf->route_leg] ? 'Flown' : 'NotFlown', // (DS_IsTourLegFlown($tour, $mf, optional($user)->id)) ? 'Flown' : 'NotFlown',
                'pop'  => $pop,
            ];
        }

        return view('DSpecial::tours.show', [
            'tour'        => $tour,
            'user'        => isset($user) ? $user : null,
            'carbon_now'  => Carbon::now(),
            'leg_checks'  => $leg_checks,
            'mapIcons'    => $mapIcons,
            'mapCenter'   => isset($user_mapCenter) ? '['.$user_mapCenter.']' : '['.$tour_mapCenter.']',
            'mapAirports' => $mapAirports,
            'mapFlights'  => $mapFlights,
        ]);
    }

    // Tour Admin Page
    public function index_admin(Request $request)
    {
        //Get Tours List
        $alltours = DS_Tour::get();
        $airlines = Airline::where('active', 1)->orderby('name')->get();
        $subfleets = Subfleet::with('airline')->orderby('name')->get();

        if ($request->input('act') && $request->input('tcode') && $request->input('sfid')) {
            $action = $request->input('act');
            $tour_code = $request->input('tcode');
            $subfleet_id = $request->input('sfid');
            $this->ManageTourSubfleets($action, $tour_code, $subfleet_id);
            return redirect(route('DSpecial.tour_admin'));
        }

        if ($request->input('touredit')) {
            $tour = DS_Tour::where('id', $request->input('touredit'))->first();

            if (!isset($tour)) {
                flash()->error('Tour Not Found !');
                return redirect(route('DSpecial.tour_admin'));
            }
        }

        return view('DSpecial::admin.tours', [
            'alltours'  => $alltours,
            'airlines'  => $airlines,
            'subfleets' => $subfleets,
            'tour'      => isset($tour) ? $tour : null,
        ]);
    }

    // Store Tour
    public function store(Request $request)
    {

        if (!$request->tour_name || !$request->tour_code) {
            flash()->error('Name And Code Fields Are Required For Tours!');
            return redirect(route('DSpecial.tour_admin'));
        }

        if (!$request->start_date || !$request->end_date) {
            flash()->error('Dates Are Required For Tours!');
            return redirect(route('DSpecial.tour_admin'));
        }

        DS_Tour::updateOrCreate(
            [
                'id' => $request->id,
            ],
            [
                'tour_name'    => $request->tour_name,
                'tour_code'    => $request->tour_code,
                'tour_desc'    => $request->tour_desc,
                'tour_rules'   => $request->tour_rules,
                'tour_airline' => $request->tour_airline,
                'start_date'   => $request->start_date,
                'end_date'     => $request->end_date,
                'active'       => $request->active,
            ]
        );

        flash()->success('Tour Saved');
        return redirect(route('DSpecial.tour_admin'));
    }

    // Add-Remove SubFleets to Tour Legs
    public function ManageTourSubfleets($action, $tour_code, $subfleet_id)
    {
        if ($action === 'add' && $tour_code && $subfleet_id) {
            $flights = Flight::where('route_code', $tour_code)->pluck('id')->toArray();

            if (count($flights) === 0) {
                flash()->error('No flights found for ' . $tour_code . ' !');
                return;
            }

            foreach ($flights as $flight) {
                $duplicate = DB::table('flight_subfleet')->where(['flight_id' => $flight, 'subfleet_id' => $subfleet_id])->count();

                if ($duplicate == 0) {
                    DB::table('flight_subfleet')->insert(['flight_id' => $flight, 'subfleet_id' => $subfleet_id]);
                }
            }

            if (isset($duplicate) && $duplicate == 0) {
                flash()->success('Subfleet Assigned to ' . $tour_code . ' Tour Flights');
            } else {
                flash()->error('Subfleet Already Assigned to ' . $tour_code . ' Tour Flights !');
            }
        }

        if ($action === 'remove' && $tour_code && $subfleet_id) {
            $flights = Flight::where('route_code', $tour_code)->pluck('id')->toArray();

            if (count($flights) === 0) {
                flash()->error('No flights found for ' . $tour_code . ' !');
                return;
            }

            $sf_count = DB::table('flight_subfleet')->where('subfleet_id', $subfleet_id)->whereIn('flight_id', $flights)->count();

            if ($sf_count > 0) {
                DB::table('flight_subfleet')->where('subfleet_id', $subfleet_id)->whereIn('flight_id', $flights)->delete();
                flash()->success('Subfleet removed from ' . $tour_code . ' flights');
            } else {
                flash()->error('Subfleet not assigned to ' . $tour_code . ' !');
            }
        }
    }
}
