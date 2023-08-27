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
use App\Models\Enums\FlightType;
use App\Services\AirportService;
use App\Services\FinanceService;
use App\Services\UserService;
use App\Support\Money;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DS_FreeFlightController extends Controller
{
    public function index()
    {
        if (DS_Setting('dspecial.freeflights_main', false) === false) {
            flash()->error('Web based Free Flights are disabled... Please select a flight from schedule');
            return redirect('/flights');
        }

        if (DS_Setting('dspecial.freeflights_reqbalance', 0) > 0) {
            $ff_finance = true;
            $ff_balance = Money::createFromAmount(DS_Setting('dspecial.freeflights_reqbalance', 0));
            $ff_cost = Money::createFromAmount(DS_Setting('dspecial.freeflights_costperedit', 0));
        } else {
            $ff_finance = false;
        }

        $settings = [];
        $settings['ac_rank'] = setting('pireps.restrict_aircraft_to_rank', true);
        $settings['ac_rating'] = setting('pireps.restrict_aircraft_to_typerating', false);
        $settings['sb_block'] = setting('simbrief.block_aircraft', false);
        $settings['sb_callsign'] = setting('simbrief.callsign', false);
        $settings['pilot_company'] = setting('pilots.restrict_to_company', false);
        $settings['pilot_location'] = setting('pilots.only_flights_from_current', false);
        $settings['airline_fleet'] = DS_Setting('dspecial.freeflights_companyfleet', false);
        $units = ['fuel' => setting('units.fuel')];

        // Get User and Allowed Subfleets
        $eager_user = ['airline', 'last_pirep', 'rank', 'journal'];

        $user = User::with($eager_user)->find(Auth::id());

        // Check settings for financial settings (requirement, balance)
        if ($ff_finance && $user->journal->balance < $ff_balance) {
            flash()->error('Not enough balance to perform a free flight. ' . $ff_balance . ' is required to proceed! Please select a flight from schedule...');
            return redirect('/flights');
        }

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

        // Get Airlines
        $airlines = Airline::where($al_where)->orderby('name')->get();

        // Prepare Airline ICAO Codes array (for JavaScript / Select2)
        $icao_list = [];
        foreach ($airlines as $airline) {
            $icao_list[$airline->id] = $airline->icao;
            $fleet_list[$airline->id] = Subfleet::where('airline_id', $airline->id)->pluck('id')->toArray();
        }

        // Get Available Aircraft
        $ac_where = [];
        $ac_where['state'] = AircraftState::PARKED;
        $ac_where['status'] = AircraftStatus::ACTIVE;

        if ($user_loc && $settings['pilot_location']) {
            $ac_where['airport_id'] = $user_loc;
        }

        $withCount = ['simbriefs' => function ($query) {
            $query->whereNull('pirep_id');
        }];

        $aircraft = Aircraft::withCount($withCount)->with('airline')
            ->where($ac_where)
            ->when(($settings['ac_rank'] || $settings['ac_rating'] || $settings['pilot_company']), function ($query) use ($allowed_sf) {
                return $query->whereIn('subfleet_id', $allowed_sf);
            })
            ->when($settings['sb_block'], function ($query) {
                return $query->having('simbriefs_count', 0);
            })->orderby('icao')->orderby('registration')
            ->get();

        // Prepare Main Aircraft array (for JavaScript / Select2 Dropdown)
        if ($aircraft) {
            $select2data = [];
            $select2data[] = ['id' => 0, 'text' => __('DSpecial::common.selectac')];
            foreach ($aircraft as $ac) {
                $text = $ac->airline->icao . ' | ' . $ac->ident;

                if ($ac->fuel_onboard[$units['fuel']] > 0) {
                    $text = $text . ' | ' . __('DSpecial::common.fuelob') . ': ' . DS_ConvertWeight($ac->fuel_onboard, $units['fuel']);
                }

                $select2data[] = ['id' => $ac->id, 'text' => $text];
            }
        }

        // Prepare Airline > Aircraft arrays (for JavaScript / Select2 Dropdown)
        if ($settings['airline_fleet']) {
            foreach ($airlines as $airline) {
                $list_aircraft = $aircraft->whereIn('subfleet_id', $fleet_list[$airline->id]);
                $airline_fleet[$airline->icao][] = ['id' => 0, 'text' => __('DSpecial::common.selectac')];
                foreach ($list_aircraft as $ac) {
                    $text = $ac->airline->icao . ' | ' . $ac->ident;

                    if ($ac->fuel_onboard[$units['fuel']] > 0) {
                        $text = $text . ' | ' . __('DSpecial::common.fuelob') . ': ' . DS_ConvertWeight($ac->fuel_onboard, $units['fuel']);
                    }

                    $airline_fleet[$airline->icao][] = ['id' => $ac->id, 'text' => $text];
                }
            }
        }

        $fflight = Flight::firstOrCreate(
            [
                'route_code'  => 'PF',
                'user_id'     => $user->id,
            ],
            [
                'airline_id'     => $user->airline_id,
                'flight_number'  => $user->id,
                'flight_type'    => 'E',
                'route_code'     => 'PF',
                'user_id'        => $user->id,
                'notes'          => $user->ident . ' - ' . $user->name_private,
                'dpt_airport_id' => $user_loc ?? 'ZZZZ',
                'arr_airport_id' => $user->home_airport_id ?? 'ZZZZ',
                'level'          => null,
                'distance'       => null,
                'route'          => null,
                'days'           => null,
                'active'         => 0,
                'visible'        => 0
            ]
        );

        return view('DSpecial::freeflights.index', [
            'aircraft'     => $aircraft,
            'airlines'     => $airlines,
            'icao'         => isset($icao_list) ? json_encode($icao_list) : null,
            'ff_balance'   => isset($ff_balance) ? $ff_balance : null,
            'ff_cost'      => isset($ff_cost) ? $ff_cost : null,
            'fflight'      => $fflight,
            'fleet_full'   => isset($select2data) ? json_encode($select2data) : null,
            'fleet_comp'   => isset($airline_fleet) ? $airline_fleet : null,
            'flight_types' => FlightType::select(true),
            'settings'     => $settings,
            'units'        => ['fuel' => setting('units.fuel')],
            'user'         => $user,
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

        // Check settings for financial settings (cost, charge)
        if (DS_Setting('dspecial.freeflights_costperedit', 0) > 0) {
            $ff_finance = true;
            $ff_cost = Money::createFromAmount(DS_Setting('dspecial.freeflights_costperedit', 0));
        } else {
            $ff_finance = false;
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
        $freeflight->route_code = 'PF';
        $freeflight->dpt_airport_id = $orig->icao;
        $freeflight->arr_airport_id = $dest->icao;
        $freeflight->distance = $dist;
        $freeflight->flight_time = DS_CalculateBlockTime($dist);
        $freeflight->days = null;
        $freeflight->route = null;
        $freeflight->flight_type = !empty($request->ff_iatatype) ? trim($request->ff_iatatype) : 'E';
        $freeflight->notes = !empty($request->ff_owner) ? $request->ff_owner : null;
        $freeflight->user_id = $request->user_id;
        $freeflight->active = 0;
        $freeflight->visible = 0;

        // Check Flight Type and adjust load factor & variance
        if (in_array($freeflight->flight_type, ['I', 'K', 'P', 'T'])) {
            $freeflight->load_factor = 0;
            $freeflight->load_factor_variance = 0;
        } else {
            $freeflight->load_factor = null;
            $freeflight->load_factor_variance = null;
        }

        $freeflight->save();

        Bid::firstorCreate(
            [
                'user_id'   => $request->user_id,
                'flight_id' => $request->ff_id
            ],
            [
                'user_id'     => $request->user_id,
                'flight_id'   => $request->ff_id,
                'aircraft_id' => !empty($request->ff_aircraft) ? $request->ff_aircraft : null,
            ]
        );

        if ($ff_finance) {
            $user = User::with('airline', 'journal')->find(Auth::id());
            $memo = 'FreeFlight ' . $freeflight->dpt_airport_id . '-' . $freeflight->arr_airport_id . ' ' . Carbon::now()->format('ymdHi');
            $this->ChargeForFreeFlight($user, $ff_cost, $memo);
            flash()->success('Transaction Completed... Personal Flight Updated & Bid Inserted');
        } else {
            flash()->success('Personal Flight Updated & Bid Inserted');
        }

        // Check if SimBrief is enabled and redirect to planning form or to bids page
        if (!empty(setting('simbrief.api_key'))) {
            $sblink = '?flight_id=' . $request->ff_id;
            if ($request->ff_aircraft != '0') {
                $sblink .= '&aircraft_id=' . $request->ff_aircraft;
            }

            return redirect(route('frontend.simbrief.generate') . $sblink);
        } else {

            return redirect(route('frontend.flights.bids'));
        }
    }

    public function ChargeForFreeFlight($user, $amount, $memo)
    {
        $financeSvc = app(FinanceService::class);

        // Charge User
        $financeSvc->debitFromJournal(
            $user->journal,
            $amount,
            $user,
            $memo,
            'FreeFlight Fees',
            'freeflight',
            Carbon::now()->format('Y-m-d')
        );

        // Credit Airline
        $financeSvc->creditToJournal(
            $user->airline->journal,
            $amount,
            $user,
            $memo . ' UserID:' . $user->id,
            'FreeFlight Fees',
            'freeflight',
            Carbon::now()->format('Y-m-d')
        );

        // Note Transaction
        Log::debug('Disposable Special | UserID:' . $user->id . ' Name:' . $user->name_private . ' charged for FreeFlight ' . $memo);
    }
}
