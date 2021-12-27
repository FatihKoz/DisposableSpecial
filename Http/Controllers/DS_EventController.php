<?php

namespace Modules\DisposableSpecial\Http\Controllers;

use App\Contracts\Controller;
use App\Models\Airline;
use App\Models\Airport;
use App\Models\Rank;
use App\Models\User;
use App\Models\Enums\UserState;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\DisposableSpecial\Models\DS_Event;

class DS_EventController extends Controller
{
    // Frontend Events Index
    public function index()
    {
        $user_id = Auth::id();
        $now = Carbon::now();

        $with = ['users', 'flights'];
        $withCount = ['users' => function ($query) use ($user_id) {
            $query->where('user_id', $user_id);
        }, 'flights'];

        $events = DS_Event::withCount($withCount)->with($with)->where(function ($query) use ($now) {
            $query->where('start_date', '<', $now)->where('end_date', '>', $now)->orWhere('start_date', '<', $now)->whereNull('end_date');
        })->having('users_count', '>', 0)->orderby('start_date')->orderby('event_name')->get();

        return view('DSpecial::events.index', [
            'events' => $events
        ]);
    }

    // Frontend Event Details
    public function show($code)
    {
        if (!$code) {
            flash()->error('Event not specified !');
            return redirect(route('DSpecial.events'));
        }

        $user_id = Auth::id();

        $with = ['users', 'flights.dpt_airport', 'flights.arr_airport', 'flights.airline'];
        $withCount = ['users' => function ($query) use ($user_id) {
            $query->where('user_id', $user_id);
        }, 'flights'];

        $event = DS_Event::withCount($withCount)->with($with)->where('event_code', $code)->having('users_count', '>', 0)->first();

        if (!$event) {
            flash()->error('Event not found !');
            return redirect(route('DSpecial.events'));
        }

        // Map Center
        $user = User::with('current_airport')->find($user_id);

        if ($user && $user->current_airport && $event->flights->contains('dpt_airport_id', $user->current_airport->id)) {
            $user_mapCenter = $user->current_airport->lat . ',' . $user->current_airport->lon;
            $user_loc = $user->current_airport->id;
        } else {
            $event_mapCenter = setting('acars.center_coords');
        }

        foreach ($event->flights->where('route_leg', 1) as $fleg) {
            $event_mapCenter = $fleg->dpt_airport->lat . ',' . $fleg->dpt_airport->lon;
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
        foreach ($event->flights as $flight) {
            $airports_pack->push($flight->dpt_airport);
            $airports_pack->push($flight->arr_airport);
        }
        $airports = $airports_pack->unique('id');

        foreach ($airports as $airport) {
            $apop = '<a href="' . route('frontend.airports.show', [$airport->id]) . '" target="_blank">' . $airport->id . ' ' . str_replace("'", "", $airport->name) . '</a>';
            if (
                isset($user_loc) && $user_loc === $airport->id
            ) {
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

        // Map Flights Array
        $mapFlights = [];

        foreach ($event->flights as $mf) {
            $pop = '<a href="/flights/' . $mf->id . '" target="_blank"><b>Leg #';
            $pop .= $mf->route_leg . ': ' . $mf->airline->code . $mf->flight_number . ' ' . $mf->dpt_airport_id . '-' . $mf->arr_airport_id;
            $pop .= '</b></a>';

            $mapFlights[] = [
                'id'   => $mf->id,
                'geod' => '[[' . $mf->dpt_airport->lat . ',' . $mf->dpt_airport->lon . '],[' . $mf->arr_airport->lat . ',' . $mf->arr_airport->lon . ']]',
                'geoc' => (DS_IsEventLegFlown($event->id, $mf->id, optional($user)->id) === true) ? 'Flown' : 'NotFlown',
                'pop'  => $pop,
            ];
        }

        return view('DSpecial::events.show', [
            'event'       => $event,
            'mapIcons'    => $mapIcons,
            'mapCenter'   => isset($user_mapCenter) ? '[' . $user_mapCenter . ']' : '[' . $event_mapCenter . ']',
            'mapAirports' => $mapAirports,
            'mapFlights'  => $mapFlights,
            'test'        => null, // $airports_unique,
        ]);
    }

    // Events Admin Index
    public function admin(Request $request)
    {
        //Get Events List
        $allevents = DS_Event::get();

        if ($request->input('eventedit')) {
            $airlines = Airline::where('active', 1)->orderby('name')->get();
            $hubs = Airport::where('hub', 1)->orderBy('id')->get();
            $ranks = Rank::orderBy('hours')->get();
            $event = DS_Event::where('id', $request->input('eventedit'))->first();
            $users = User::where('state', UserState::ACTIVE)->orderBy('pilot_id')->get()->except($event->users->modelKeys());

            if (!isset($event)) {
                flash()->error('Event Not Found !');
                return redirect(route('DSpecial.event_admin'));
            }
        }

        return view('DSpecial::admin.events.index', [
            'allevents' => $allevents,
            'airlines'  => $airlines ?? null,
            'hubs'      => $hubs ?? null,
            'ranks'     => $ranks ?? null,
            'users'     => $users ?? null,
            'event'     => $event ?? null,
        ]);
    }

    // Users Dropdown
    protected function return_user_view($event)
    {
        $airlines = Airline::where('active', 1)->orderby('name')->get();
        $hubs = Airport::where('hub', 1)->orderBy('id')->get();
        $ranks = Rank::orderBy('hours')->get();
        $users = User::where('state', UserState::ACTIVE)->orderBy('pilot_id')->get()->except($event->users->modelKeys());

        return view('DSpecial::admin.events.users', [
            'event'    => $event,
            'airlines' => $airlines ?? null,
            'hubs'     => $hubs ?? null,
            'ranks'    => $ranks ?? null,
            'users'    => $users,
        ]);
    }

    // Users
    public function users($id, Request $request)
    {
        $event = DS_Event::where('id', $id)->first();

        if (empty($event)) {
            flash()->error('Event not found!');
            return redirect(route('DSpecial.event_admin'));
        }

        // add user to event
        if ($request->isMethod('post')) {
            if ($request->input('method') == 'users') {
                $user = User::where('id', $request->input('user_id'))->where('state', UserState::ACTIVE)->first();
                $event->users()->syncWithoutDetaching([$user->id]);
            } elseif ($request->input('method') == 'airlines') {
                $users = User::where('airline_id', $request->input('airline_id'))->where('state', UserState::ACTIVE)->get();
                $event->users()->syncWithoutDetaching($users);
            } elseif ($request->input('method') == 'hubs') {
                $users = User::where('home_airport_id', $request->input('hub_id'))->where('state', UserState::ACTIVE)->get();
                $event->users()->syncWithoutDetaching($users);
            } elseif ($request->input('method') == 'ranks') {
                $users = User::where('rank_id', $request->input('rank_id'))->where('state', UserState::ACTIVE)->get();
                $event->users()->syncWithoutDetaching($users);
            } elseif ($request->input('method') == 'random') {
                $users = User::where('state', UserState::ACTIVE)->inRandomOrder()->limit($request->input('random_id'))->get();
                $event->users()->syncWithoutDetaching($users);
            }
        }
        // remove user from event
        elseif ($request->isMethod('delete')) {
            $user = User::where('id', $request->input('user_id'))->first();
            $event->users()->detach([$user->id]);
        }

        return $this->return_user_view($event);
    }

    // Store Event
    public function store(Request $request)
    {
        if (!$request->event_name || !$request->event_code) {
            flash()->error('Name And Code Fields Are Required For Events!');
            return redirect(route('DSpecial.event_admin'));
        }

        if (!$request->start_date) {
            flash()->error('Start Date Is Required For Events!');
            return redirect(route('DSpecial.event_admin'));
        }

        DS_Event::updateOrCreate(
            [
                'id' => $request->id,
            ],
            [
                'event_name' => $request->event_name,
                'event_code' => $request->event_code,
                'event_desc' => $request->event_desc,
                'start_date' => $request->start_date,
                'end_date'   => $request->end_date,
                'public'     => $request->public,
            ]
        );

        flash()->success('Event Saved');
        return redirect(route('DSpecial.event_admin'));
    }
}
