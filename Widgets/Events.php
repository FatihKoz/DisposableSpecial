<?php

namespace Modules\DisposableSpecial\Widgets;

use App\Contracts\Widget;
use App\Models\Pirep;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Modules\DisposableSpecial\Models\DS_Event;

class Events extends Widget
{
    protected $config = ['user' => null, 'warn' => null,];

    public function run()
    {
        $user_id = $this->config['user'] ?? Auth::id();
        $warn_days = is_numeric($this->config['warn']) ? $this->config['warn'] : 14;
        $now = Carbon::now();

        $with = ['users', 'flights'];
        $withCount = ['users' => function ($query) use ($user_id) {
            $query->where('user_id', $user_id);
        }, 'flights'];

        // Get Events
        $events = DS_Event::withCount($withCount)->with($with)->where(function ($query) use ($now) {
            $query->where('start_date', '<', $now)->where('end_date', '>', $now)->orWhere('start_date', '<', $now)->whereNull('end_date');
        })->where(function ($query) {
            $query->where('public', 1)->orHavingRaw('users_count > 0');
        })->get();
        $events_code = $events->pluck('event_code')->toArray();

        // Get Event pireps
        $pireps = Pirep::whereNotNull('route_leg')->where(['user_id' => $user_id, 'state' => 2, 'status'  => 'ONB'])
            ->whereIn('route_code', $events_code)
            ->selectRaw('route_code as event_code')->selectRaw('count(DISTINCT route_leg) as flown_legs')
            ->groupby('route_code')->get();

        // Prepare Tour Progress array
        $progress = [];
        foreach ($events as $event) {
            foreach ($pireps as $pirep) {
                if ($pirep->event_code === $event->event_code) {
                    $ratio = ceil((100 * $pirep->flown_legs) / $event->flights_count);
                    $end_date = Carbon::parse($event->end_date ?? Carbon::now()->addYears(5));
                    $diff = $end_date->diffInDays($now);
                    $progress[$event->event_code] = [
                        'name' => $event->event_name,
                        'code' => $event->event_code,
                        'legs' => $event->flights_count,
                        'comp' => $pirep->flown_legs,
                        'prog' => $ratio,
                        'barc' => ($ratio < 100) ? 'bg-warning text-black' : 'bg-success text-black',
                        'remd' => ($diff < $warn_days) ? '| Last '.$diff.' days until closure !' : '',
                        'warn' => ($diff < $warn_days) ? 'progress-bar-striped' : '',
                    ];
                }
            }
            if (!array_key_exists($event->event_code, $progress)) {
                $end_date = Carbon::parse($event->end_date ?? Carbon::now()->addYears(5));
                $diff = $end_date->diffInDays($now);
                $progress[$event->event_code] = [
                    'name' => $event->event_name,
                    'code' => $event->event_code,
                    'legs' => $event->flights_count,
                    'comp' => 0,
                    'prog' => 50,
                    'barc' => 'bg-info text-black',
                    'remd' => ($diff < $warn_days) ? '| Last '.$diff.' days until closure !' : '',
                    'warn' => ($diff < $warn_days) ? 'progress-bar-striped' : '',
                ];
            }
        }

        $counts = [];
        $counts['user'] = $pireps->count();
        $counts['event'] = $events->count();
        // we need to return the $events to a view


        return view('DSpecial::widgets.events', [
            'prog'          => $progress,
            'counts'        => $counts,
            'is_visible'    => ($events->count() > 0) ? true : false,
        ]);
    }
}
