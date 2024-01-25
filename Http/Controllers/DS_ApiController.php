<?php

namespace Modules\DisposableSpecial\Http\Controllers;

use App\Contracts\Controller;
use App\Models\Enums\PirepState;
use App\Models\Pirep;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Modules\DisposableSpecial\Models\DS_Assignment;
use Modules\DisposableSpecial\Models\DS_Tour;
use Nwidart\Modules\Facades\Module;

class DS_ApiController extends Controller
{
    // Assignments
    public function assignments(Request $request)
    {
        if (!$this->AuthCheck($request->header('x-service-key'))) {
            return response(['error' => ['code' => '401', 'http_code' => 'Unauthorized', 'message' => 'Check Service Key!']], 401);
        };

        $curr_y = is_numeric($request->header('x-a-year')) ? $request->header('x-a-year') : Carbon::now()->format('Y');
        $curr_m = is_numeric($request->header('x-a-month')) ? $request->header('x-a-month') : Carbon::now()->format('m');

        $with = ['flight.airline', 'flight.arr_airport', 'flight.dpt_airport', 'pirep', 'user'];
        $where = [
            'assignment_year'  => $curr_y,
            'assignment_month' => $curr_m,
        ];

        $assignments = DS_Assignment::with($with)->where($where)->orderby('user_id')->orderby('assignment_order')->get();

        $list = [];

        foreach ($assignments as $a) {
            $list[] = [
                'id'           => $a->id,
                'pilot_id'     => $a->user_id,
                'pilot_ident'  => optional($a->user)->ident,
                'pilot_name'   => optional($a->user)->name_private,
                'year'         => $a->assignment_year,
                'month'        => $a->assignment_month,
                'order'        => $a->assignment_order,
                'flt_id'       => $a->flight_id,
                'flt_ident'    => optional($a->flight)->ident,
                'flt_number'   => optional($a->flight)->flight_number,
                'flt_al_icao'  => optional(optional($a->flight)->airline)->icao,
                'flt_al_iata'  => optional(optional($a->flight)->airline)->iata,
                'flt_al_name'  => optional(optional($a->flight)->airline)->name,
                'flt_dep_icao' => optional(optional($a->flight)->dpt_airport)->icao,
                'flt_dep_iata' => optional(optional($a->flight)->dpt_airport)->iata,
                'flt_dep_name' => optional(optional($a->flight)->dpt_airport)->name,
                'flt_arr_icao' => optional(optional($a->flight)->arr_airport)->icao,
                'flt_arr_iata' => optional(optional($a->flight)->arr_airport)->iata,
                'flt_arr_name' => optional(optional($a->flight)->arr_airport)->name,
                'prp_id'       => $a->pirep_id,
                'prp_date'     => filled($a->pirep_date) ? $a->pirep_date->format('d.M.Y H:i') : null,
                'completed'    => (filled($a->pirep_id) && filled($a->pirep_date)) ? true : false,
                'created_at'   => $a->created_at->format('d.M.Y H:i'),
                'updated_at'   => $a->updated_at->format('d.M.Y H:i'),
            ];
        }

        return response()->json($list);
    }

    // Tours
    public function tours(Request $request)
    {
        if (!$this->AuthCheck($request->header('x-service-key'))) {
            return response(['error' => ['code' => '401', 'http_code' => 'Unauthorized', 'message' => 'Check Service Key!']], 401);
        };

        $now = Carbon::now();
        $with = ['airline', 'legs'];

        $where = [
            'active' => 1,
            ['end_date', '>=', $now],
        ];

        $tours = DS_Tour::with($with)->where($where)->orderby('tour_code')->get();

        $active = collect();
        $planned = collect();

        foreach ($tours as $t) {
            $tour = [
                'code'         => $t->tour_code,
                'name'         => $t->tour_name,
                'desc'         => $t->tour_desc,
                'rules'        => $t->tour_rules,
                'type'         => ($t->tour_airline > 0) ? 'Airline Tour' : 'Generic Tour',
                'start'        => $t->start_date->format('d.M.Y H:i'),
                'end'          => $t->end_date->endOfDay()->format('d.M.Y H:i'),
                'airline_id'   => ($t->tour_airline > 0) ? $t->tour_airline : null,
                'airline_icao' => optional($t->airline)->icao,
                'airline_iata' => optional($t->airline)->iata,
                'airline_name' => optional($t->airline)->name,
                'airline_logo' => optional($t->airline)->logo,
                'state'        => ($t->start_date <= $now) ? 'Active' : 'Planned',
                'leg_count'    => $t->legs->count(),
                'plt_count'    => Pirep::where('route_code', $t->tour_code)->where('state', PirepState::ACCEPTED)->distinct('user_id')->count(),
                'legs'         => $t->legs()->with('dpt_airport', 'arr_airport')->select('id', 'flight_number', 'route_code', 'route_leg', 'dpt_airport_id', 'arr_airport_id', 'start_date', 'end_date')->orderby('route_leg')->get(),
            ];

            // Append either active or planned collections
            if ($t->start_date <= $now) {
                $active->push($tour);
            } else {
                $planned->push($tour);
            }
        }

        return response()->json([
            'active'  => $active,
            'planned' => $planned,
        ]);
    }

    // Module Check
    public function modules(Request $request)
    {
        $DBM = Module::find('DisposableBasic');
        $DBE = isset($DBM) ? $DBM->isEnabled() : false;

        $DSM = Module::find('DisposableSpecial');
        $DSE = isset($DSM) ? $DSM->isEnabled() : false;

        return response()->json([
            'App Name'           => config('app.name'),
            'App URL'            => config('app.url'),
            'Disposable Basic'   => 'Installed: ' . isset($DBM) . ' | Enabled: ' . $DBE,
            'Disposable Special' => 'Installed: ' . isset($DSM) . ' | Enabled: ' . $DSE,
        ]);
    }

    // Simple Auth Check
    public function AuthCheck($service_key = null)
    {
        return ($service_key === null || $service_key !== DS_Setting('dbasic.srvkey')) ? false : true;
    }
}
