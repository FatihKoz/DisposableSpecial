<?php

namespace Modules\DisposableSpecial\Http\Controllers;

use App\Contracts\Controller;
use App\Models\Airline;
use App\Models\Airport;
use App\Models\Enums\UserState;
use App\Models\Rank;
use App\Models\User;
use Illuminate\Http\Request;
use Laracasts\Flash\Flash;
use Modules\DisposableSpecial\Models\DS_Event;

/**
 * Class DS_EventController
 * @package Modules\DisposableSpecial\Http\Controllers
 */
class DS_EventController extends Controller
{
    // Event Admin Page
    public function index_admin(Request $request)
    {
        //Get Events List
        $allevents = DS_Event::get();

        if ($request->input('eventedit')) {
            $airlines = Airline::where('active', 1)->orderby('name')->get();
            $hubs = Airport::where('hub',1)->orderBy('id')->get();
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

    protected function return_user_view($event)
    {
        $airlines = Airline::where('active', 1)->orderby('name')->get();
        $hubs = Airport::where('hub',1)->orderBy('id')->get();
        $ranks = Rank::orderBy('hours')->get();
        $users = User::where('state', UserState::ACTIVE)->orderBy('pilot_id')->get()->except($event->users->modelKeys());

        return view('DSpecial::admin.events.users', [
            'event' => $event,
            'airlines'  => $airlines ?? null,
            'hubs'      => $hubs ?? null,
            'ranks'     => $ranks ?? null,
            'users' => $users,
        ]);
    }

    public function users($id, Request $request)
    {
        $event = DS_Event::where('id', $id)->first();
        if (empty($event)) {
            Flash::error('Event not found!');
            return redirect(route('DSpecial.event_admin'));
        }

        // add user to event
        if ($request->isMethod('post')) {
            if($request->input('method') == 'users') {
                $user = User::where('id', $request->input('user_id'))->where('state', UserState::ACTIVE)->first();
                $event->users()->syncWithoutDetaching([$user->id]);
            } elseif($request->input('method') == 'airlines') {
                $users = User::where('airline_id', $request->input('airline_id'))->where('state', UserState::ACTIVE)->get();
                $event->users()->syncWithoutDetaching($users);
            } elseif($request->input('method') == 'hubs') {
                $users = User::where('home_airport_id', $request->input('hub_id'))->where('state', UserState::ACTIVE)->get();
                $event->users()->syncWithoutDetaching($users);
            } elseif($request->input('method') == 'ranks') {
                $users = User::where('rank_id', $request->input('rank_id'))->where('state', UserState::ACTIVE)->get();
                $event->users()->syncWithoutDetaching($users);
            } elseif($request->input('method') == 'random') {
                $users = User::where('state', UserState::ACTIVE)->inRandomOrder()->limit($request->input('random_id'))->get();
                $event->users()->syncWithoutDetaching($users);
            }
        }
        // remove subfleet from type rating
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
                'event_name'    => $request->event_name,
                'event_code'    => $request->event_code,
                'event_desc'    => $request->event_desc,
                'start_date'    => $request->start_date,
                'end_date'      => $request->end_date,
                'public'        => $request->public,
            ]
        );

        flash()->success('Event Saved');
        return redirect(route('DSpecial.event_admin'));
    }
}
