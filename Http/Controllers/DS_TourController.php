<?php

namespace Modules\DisposableSpecial\Http\Controllers;

use App\Contracts\Controller;
use App\Models\Airline;
use App\Models\Award;
use App\Models\Enums\PirepState;
use App\Models\Flight;
use App\Models\Pirep;
use App\Models\Subfleet;
use App\Models\User;
use App\Models\UserAward;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\DisposableSpecial\Models\DS_Marketitem;
use Modules\DisposableSpecial\Models\DS_Marketowner;
use Modules\DisposableSpecial\Models\DS_Tour;
use Modules\DisposableSpecial\Models\Enums\DS_ItemCategory;

class DS_TourController extends Controller
{
    public function index(Request $request)
    {
        $sfid = !empty($request->input('sfid')) ? $request->input('sfid') : null;
        $tours = DS_Tour::withCount('legs')->with(['airline', 'token'])->where('active', 1)->orderby('start_date')->orderby('tour_name')->get();

        // Provide market bought tokens per user
        $user_id = Auth::id();
        $tour_tokens = DS_Marketitem::where('category', DS_ItemCategory::TOUR)->pluck('id')->toArray();
        $user_tokens = DS_Marketowner::where('user_id', $user_id)->whereIn('marketitem_id', $tour_tokens)->pluck('marketitem_id')->toArray();
        // Prepare tour subfleets for dropdown
        $tour_codes = $tours->where('end_date', '>=', Carbon::today())->sortBy('tour_code', SORT_NATURAL)->pluck('tour_code')->toArray();
        $tour_flights = Flight::withCount('subfleets')->whereIn('route_code', $tour_codes)->having('subfleets_count', '>', 0)->pluck('id')->toArray();
        $tour_subfleets = DB::table('flight_subfleet')->whereIn('flight_id', $tour_flights)->groupBy('subfleet_id')->pluck('subfleet_id')->toArray();
        $view_subfleets = Subfleet::with('airline')->whereIn('id', $tour_subfleets)->orderBy('name')->get();

        if ($sfid) {
            $fleet_flights = DB::table('flight_subfleet')->where('subfleet_id', $sfid)->whereIn('flight_id', $tour_flights)->pluck('flight_id')->toArray();
            $fleet_codes = Flight::whereIn('id', $fleet_flights)->groupBy('route_code')->pluck('route_code')->toArray();
            // Filter tours according to selected subfleet
            $tours = $tours->whereIn('tour_code', $fleet_codes);
        }

        return view('DSpecial::tours.index', [
            'carbon_now'     => Carbon::now(),
            'market_cat'     => DS_ItemCategory::TOUR,
            'tours'          => $tours,
            'tour_subfleets' => $view_subfleets,
            'tour_tokens'    => $tour_tokens,
            'user_tokens'    => $user_tokens,
            'units'          => DS_GetUnits(),
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
            'token',
        ])->where('tour_code', $code)->first();

        if (!$tour) {
            flash()->error('Tour not found !');

            return redirect(route('DSpecial.tours'));
        }

        // Logged in user
        $user = User::with(['bids', 'current_airport'])->find(Auth::id());

        // Check user tokens and redirect even before starting lots of stuff
        $user_token_check = DS_Marketowner::where(['user_id' => $user->id, 'marketitem_id' => $tour->tour_token])->count();

        if ($tour->tour_token > 0 && $user_token_check == 0) {
            flash()->error('You do not have the required token for '.$tour->tour_name.', please check the shop...');

            return redirect(route('DSpecial.market').'?cat='.DS_ItemCategory::TOUR);
        }

        // Tour Award Winners
        $tour_award = Award::where('ref_model_params', $code)->first();
        $tour_awards = UserAward::with('user')->where('award_id', optional($tour_award)->id)->orderBy('created_at', 'asc')->take(10)->get();

        // Tour Report
        $tour_report = [];
        $tour_pilots = Pirep::where(['route_code' => $code, 'state' => PirepState::ACCEPTED])->groupBy('user_id')->pluck('user_id')->toArray();
        $pilots = User::whereIn('id', $tour_pilots)->orderBy('pilot_id', 'asc')->get();

        if (filled($pilots)) {
            foreach ($pilots as $pilot) {
                $user_pireps = Pirep::where(['user_id' => $pilot->id, 'state' => PirepState::ACCEPTED, 'route_code' => $code])->whereNotNull('route_leg')->orderBy('submitted_at')->pluck('route_leg')->toArray();
                $tour_order = range(1, count($user_pireps));
                $tour_report[$pilot->id] = [];
                $tour_report[$pilot->id]['order'] = ($tour_order == $user_pireps) ? true : false;
                $tour_report[$pilot->id]['flown'] = implode(', ', $user_pireps);
                foreach ($tour->legs->sortBy('route_leg', SORT_NATURAL) as $tl) {
                    $tour_report[$pilot->id][$tl->route_leg] = DS_IsTourLegFlown($tour, $tl, $pilot->id);
                }
            }
        }

        // Map Center
        if ($user && $user->current_airport && filled($tour->legs()->where('dpt_airport_id', $user->current_airport->id))) {
            $user_mapCenter = $user->current_airport->lat.','.$user->current_airport->lon;
            $user_loc = $user->current_airport->id;
        } else {
            $tour_mapCenter = setting('acars.center_coords');
        }

        foreach ($tour->legs->where('route_leg', 1) as $fleg) {
            $tour_mapCenter = $fleg->dpt_airport->lat.','.$fleg->dpt_airport->lon;
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
            $apop = '<a href="'.route('frontend.airports.show', [$airport->id]).'" target="_blank">'.$airport->id.' '.str_replace("'", '', $airport->name).'</a>';
            if (isset($user_loc) && $user_loc === $airport->id) {
                $iconColor = 'YellowIcon';
            } else {
                $iconColor = 'BlueIcon';
            }
            $mapAirports[] = [
                'id'   => $airport->id,
                'loc'  => $airport->lat.', '.$airport->lon,
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
            $pop = '<a href="/flights/'.$mf->id.'" target="_blank">Leg #';
            $pop .= $mf->route_leg.': '.$mf->airline->code.$mf->flight_number.' '.$mf->dpt_airport_id.'-'.$mf->arr_airport_id;
            $pop .= '</a>';
            // Flights with popups and check results
            $mapFlights[] = [
                'id'   => $mf->id,
                'geod' => '[['.$mf->dpt_airport->lat.','.$mf->dpt_airport->lon.'],['.$mf->arr_airport->lat.','.$mf->arr_airport->lon.']]',
                'geoc' => $leg_checks[$mf->route_leg] ? 'Flown' : 'NotFlown', // (DS_IsTourLegFlown($tour, $mf, optional($user)->id)) ? 'Flown' : 'NotFlown',
                'pop'  => $pop,
            ];
        }

        $saved_flights = [];
        foreach ($user->bids as $bid) {
            $saved_flights[$bid->flight_id] = $bid->id;
        }

        return view('DSpecial::tours.show', [
            'carbon_now'    => Carbon::now(),
            'leg_checks'    => $leg_checks,
            'mapIcons'      => $mapIcons,
            'mapCenter'     => isset($user_mapCenter) ? '['.$user_mapCenter.']' : '['.$tour_mapCenter.']',
            'mapAirports'   => $mapAirports,
            'mapFlights'    => $mapFlights,
            'market_cat'    => DS_ItemCategory::TOUR,
            'pilots'        => $pilots,
            'saved'         => $saved_flights,
            'simbrief'      => !empty(setting('simbrief.api_key')),
            'simbrief_bids' => setting('simbrief.only_bids'),
            'tour'          => $tour,
            'tour_awards'   => $tour_awards,
            'tour_report'   => $tour_report,
            'user'          => isset($user) ? $user : null,
            'units'         => DS_GetUnits(),
        ]);
    }

    // Tour Admin Page
    public function index_admin(Request $request)
    {
        //Get Tours List
        $alltours = DS_Tour::get();
        $airlines = Airline::where('active', 1)->orderby('name')->get();
        $subfleets = Subfleet::with('airline')->orderby('name')->get();
        $tokens = DS_Marketitem::where('category', DS_ItemCategory::TOUR)->get();

        if ($request->input('act') && $request->input('tcode') && $request->input('sfid')) {
            $action = $request->input('act');
            $tour_codes = $request->input('tcode');
            $subfleet_ids = $request->input('sfid');
            $this->ManageTourSubfleets($action, explode(',', $tour_codes), explode(',', $subfleet_ids));

            if (filled($request->input('touredit'))) {
                return redirect(route('DSpecial.tour_admin').'?touredit='.$request->input('touredit'));
            } else {
                return redirect(route('DSpecial.tour_admin'));
            }
        }

        if ($request->input('touredit')) {
            $tour = DS_Tour::with('legs.subfleets')->where('id', $request->input('touredit'))->first();

            if (!isset($tour)) {
                flash()->error('Tour Not Found !');

                return redirect(route('DSpecial.tour_admin'));
            }

            $toursfs = collect();

            if ($tour->legs->count() > 0) {
                foreach ($tour->legs as $flt) {
                    foreach ($flt->subfleets as $sf) {
                        $toursfs->push($sf->id);
                    }
                }
            }
        }

        return view('DSpecial::admin.tours', [
            'alltours'  => $alltours,
            'airlines'  => $airlines,
            'subfleets' => $subfleets,
            'tokens'    => $tokens,
            'tour'      => isset($tour) ? $tour : null,
            'toursfs'   => isset($tour) ? $toursfs->unique()->toArray() : array(),
            'units'     => DS_GetUnits(),
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
                'tour_token'   => $request->tour_token,
                'start_date'   => $request->start_date,
                'end_date'     => $request->end_date,
                'active'       => $request->active,
            ]
        );

        flash()->success('Tour Saved');

        return redirect(route('DSpecial.tour_admin'));
    }

    // Removes Tour Details From a Pirep
    // By nulling both route_code and route_leg
    public function remove_from_pirep($pirep_id)
    {
        $tour_codes = DS_Tour::orderBy('created_at')->pluck('tour_code')->toArray();
        $pirep = Pirep::where('id', $pirep_id)->whereNotNull(['route_code', 'route_leg'])->whereIn('route_code', $tour_codes)->first();

        if ($pirep) {
            $pirep->route_code = null;
            $pirep->route_leg = null;
            $pirep->save();
            flash()->success('Tour details removed from pirep');
            Log::debug('Disposable Special | Tour details removed from Pirep ID:'.$pirep_id);
        } else {
            flash()->error('This is not a tour report');
        }

        return back()->withInput();
    }

    // Add-Remove SubFleets to Tour Legs
    public function ManageTourSubfleets($action, $tour_codes, $subfleet_ids)
    {
        if ($action === 'add' && $tour_codes && $subfleet_ids) {
            $flights = Flight::whereIn('route_code', $tour_codes)->pluck('id')->toArray();

            if (count($flights) === 0) {
                flash()->error('No flights found for '.implode(',', $tour_codes).' !');

                return;
            }

            foreach ($flights as $flight) {
                foreach ($subfleet_ids as $subfleet_id) {
                    $duplicate = DB::table('flight_subfleet')->where(['flight_id' => $flight, 'subfleet_id' => $subfleet_id])->count();

                    if ($duplicate == 0) {
                        DB::table('flight_subfleet')->insert(['flight_id' => $flight, 'subfleet_id' => $subfleet_id]);
                    } else {
                        $error = true;
                    }
                }
            }

            if (!isset($error)) {
                flash()->success('Selected Subfleets assigned successfully to '.implode(', ', $tour_codes).' flights !');
            } else {
                flash()->error('Some of the selected subfleets were already assigned to '.implode(', ', $tour_codes).' flights !');
            }
        }

        if ($action === 'remove' && $tour_codes && $subfleet_ids) {
            $flights = Flight::whereIn('route_code', $tour_codes)->pluck('id')->toArray();

            if (count($flights) === 0) {
                flash()->error('No flights found for '.implode(', ', $tour_codes).' !');

                return;
            }

            $sf_count = DB::table('flight_subfleet')->whereIn('subfleet_id', $subfleet_ids)->whereIn('flight_id', $flights)->count();

            if ($sf_count > 0) {
                DB::table('flight_subfleet')->whereIn('subfleet_id', $subfleet_ids)->whereIn('flight_id', $flights)->delete();
                flash()->success('Selected Subfleets removed successfully from '.implode(', ', $tour_codes).' flights !');
            } else {
                flash()->error('Selected Subfleets were not assigned, thus not removed from '.implode(', ', $tour_codes).' flights !');
            }
        }
    }

    // Handle Leg Actions
    public function leg_actions(Request $request)
    {
        if ($request->button_delete === 'delete_all') {
            $action = 'delete';
        } elseif ($request->button_clean === 'clean_all') {
            $action = 'clean';
        } elseif ($request->button_own === 'own_all') {
            $action = 'own';
        } elseif ($request->button_own === 'drop_all') {
            $action = 'drop';
        } elseif ($request->button_activate === 'activate_all') {
            $action = 'activate';
        } elseif ($request->button_activate === 'deactivate_all') {
            $action = 'deactivate';
        } else {
            $action = null;
            flash()->error('No action selected !');
        }

        $tour = DS_Tour::where('id', $request->tour_id)->first();

        if ($action === 'delete') {
            $this->DeleteLegs($action, $tour);
        }

        if ($action === 'clean') {
            $this->CleanNotes($action, $tour);
        }

        if ($action === 'activate' || $action === 'deactivate') {
            $this->ActivateLegs($action, $tour);
        }

        if ($action === 'own' || $action === 'drop') {
            $this->LegOwnership($action, $tour);
        }

        return redirect(route('DSpecial.tour_admin').'?touredit='.$tour->id);
    }

    // Own tour legs for keeping them safe from csv import deletions
    public function LegOwnership($action = null, $tour = null)
    {
        if (filled($tour) && $action === 'own') {
            $ownership = Flight::where('route_code', $tour->tour_code)->update(['owner_type' => 'DS_Tour', 'owner_id' => $tour->id]);
            Log::debug('Disposable Special | Leg ownership of '.$tour->tour_code.' added for '.$ownership.' legs');
            flash()->info('Tour leg ownership completed');
        } elseif (filled($tour) && $action === 'drop') {
            $ownership = Flight::where('route_code', $tour->tour_code)->update(['owner_type' => null, 'owner_id' => null]);
            Log::debug('Disposable Special | Leg ownership of '.$tour->tour_code.' removed for '.$ownership.' legs');
            flash()->info('Tour leg ownership removed');
        } else {
            flash()->error('Tour not specified !');
        }
    }

    // Delete Legs of a Tour
    public function DeleteLegs($action = null, $tour = null)
    {
        $selected_tour = DS_Tour::where('id', $tour->id)->first();

        if (filled($selected_tour) && $action === 'delete') {
            $deleted = $selected_tour->legs()->forceDelete();
            Log::debug('Disposable Special | '.$deleted.' legs of '.$selected_tour->tour_code.' deleted');
            flash()->info($selected_tour->tour_code.' legs deleted');
        } else {
            flash()->error('Tour not found !');
        }
    }

    // Clean Notes/Remarks of Tour Legs
    public function CleanNotes($action = null, $tour = null)
    {
        $selected_tour = DS_Tour::where('id', $tour->id)->first();

        if (filled($selected_tour) && $action === 'clean') {
            $cleaned = $selected_tour->legs()->update(['notes' => null]);
            Log::debug('Disposable Special | Notes/Remarks of '.$cleaned.' legs for '.$selected_tour->tour_code.' cleaned');
            flash()->info($selected_tour->tour_code.' leg notes/remarls cleaned');
        } else {
            flash()->error('Tour not found !');
        }
    }

    // Activate Legs of a Tour
    public function ActivateLegs($action = null, $tour = null)
    {
        $visibility = DS_Setting('dspecial.keep_tf_invisible', false) ? 0 : 1;
        $selected_tour = DS_Tour::where('id', $tour->id)->first();

        if (filled($selected_tour) && $action === 'activate') {
            $legs = $selected_tour->legs()->update(['active' => 1, 'visible' => $visibility]);
            Log::debug('Disposable Special | '.$legs.' legs of '.$selected_tour->tour_code.' activated');
            flash()->info($selected_tour->tour_code.' legs activated');
        } elseif (filled($selected_tour) && $action === 'deactivate') {
            $legs = $selected_tour->legs()->update(['active' => 0, 'visible' => 0]);
            Log::debug('Disposable Special | '.$legs.' legs of '.$selected_tour->tour_code.' deactivated');
            flash()->info('Tour legs deactivated');
        } else {
            flash()->error('Tour not found !');
        }
    }
}
