<?php

namespace Modules\DisposableSpecial\Http\Controllers;

use App\Contracts\Controller;
use App\Models\Flight;
use App\Models\User;
use App\Support\Money;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\DisposableSpecial\Models\DS_Assignment;

class DS_AssignmentController extends Controller
{
    public function index()
    {
        $user = User::withCount('roles')->with('rank', 'airline')->find(Auth::id());

        $now = Carbon::now();
        $current_year = $now->year;
        $current_month = $now->month;

        // Check Assignments
        if ($user->roles_count > 0) {
            $check_assignments = DS_Assignment::where(['assignment_year' => $current_year, 'assignment_month' => $current_month])->count();
            if ($check_assignments > 0) {
                $sys_check = true;
            }
        }

        $where = [];
        $where['user_id'] = $user->id;
        $where['assignment_year'] = $current_year;

        $show_months = DS_Setting('turksim.assignments_months', 4);

        if ($show_months < $current_month) {
            $where[] = ['assignment_month', '>', ($current_month - $show_months)];
        } else {
            $where[] = ['assignment_month', '>=', 1];
        }

        $with = ['flight.airline', 'flight.arr_airport', 'flight.dpt_airport', 'user.airline', 'user.rank'];
        $assignments = DS_Assignment::with($with)->where($where)->get();
        $groupped_assignments = $assignments->groupby('assignment_month')->sortKeysDesc();

        // Personal Stats
        $stats = [];
        $completed = 0;
        $reward_multiplier = DS_Setting('turksim.assignments_multiplier', 10);

        // Overall Stats (considers all year)
        $stat_assignments = DS_Assignment::with(['user.airline', 'user.rank'])->where(['user_id' => $user->id, 'assignment_year' => $current_year])->get();

        if ($groupped_assignments->count() > 0 && $stat_assignments->count() > 0) {

            foreach ($stat_assignments as $as) {
                if ($as->completed) {
                    $completed++;
                }
            }

            $earnings = round($completed * ($user->rank->acars_base_pay_rate * $reward_multiplier));

            $stats['Overall'] = [
                'total'     => $stat_assignments->count(),
                'completed' => $completed,
                'ratio'     => round((100 * $completed) / $stat_assignments->count(), 2),
                'earnings'  => Money::createFromAmount($earnings),
            ];

            // Monthly Stats (considers displayed months selection)
            foreach ($groupped_assignments as $group => $tas) {
                $comp = 0;
                foreach ($tas as $ts) {
                    if ($ts->completed) {
                        $comp++;
                    }
                }
                $earn = round($comp * ($user->rank->acars_base_pay_rate * $reward_multiplier));

                $stats[Carbon::create()->day(1)->month($group)->format('F')] = [
                    'total'     => count($tas),
                    'completed' => $comp,
                    'ratio'     => round((100 * $comp) / count($tas), 2),
                    'earnings'  => Money::createFromAmount($earn),
                ];
            }
        }

        return view('DSpecial::assignments.index', [
            'assignments' => $groupped_assignments,
            'stats'       => $stats,
            'sys_check'   => isset($sys_check) ? $sys_check : false,
        ]);
    }

    // Manual Trigger Route method
    public function assignments_manual(Request $request)
    {
        $curr_page = !empty($request->curr_page) ? $request->curr_page : '/dashboard';
        $reset = !empty($request->resetmonth) ? true : false;
        $user = null;

        $this->TriggerAssignment($user, $reset);

        return redirect(url($curr_page));
    }

    // Trigger Process (used by Cron and Admin Interface)
    public function TriggerAssignment($user = null, $reset = false)
    {
        $now = Carbon::now();
        $curr_y = $now->year;
        $curr_m = $now->month;

        if (!$user) {
            if ($reset === true) {
                DS_Assignment::where(['assignment_year' => $curr_y, 'assignment_month' => $curr_m])->delete();
                Log::info('Disposable Assignments, ALL Monthly Flight Assignments DELETED for ' . $curr_y . '/' . $curr_m);
            }
            // Assign Flights to all ACTIVE Users
            $active_users = User::where('state', 1)->orderby('id')->get();

            if ($active_users) {
                Log::info('Disposable Assignments, begin Monthly Flight Assignment process for ' . $curr_y . '/' . $curr_m);
                foreach ($active_users as $user) {
                    $this->GenerateAssignments($user);
                }
                Log::info('Disposable Assignments, Monthly Flight Assignment process completed for ' . $curr_y . '/' . $curr_m);
            }
        } elseif ($user) {
            // Handle a specific User's Assignments
            if ($reset === true) {
                DS_Assignment::where(['user_id' => $user->id, 'assignment_year' => $curr_y, 'assignment_month' => $curr_m])->delete();
                Log::info('Disposable Assignments, Monthly Flight Assignments of ' . $user->name_private . ' DELETED for ' . $curr_y . '/' . $curr_m);
            }
            $this->GenerateAssignments($user);
        }
    }

    // Generate Monthly Flight Assignments
    public function GenerateAssignments($user = null, $count = null)
    {
        $selected_flights = [];

        if (!$user) {
            Log::debug('Disposable Assignments, User Model not provided! Aborting assignment process');
            return;
        }

        if ($user->state !== 1) {
            Log::debug('Disposable Assignments, ' . $user->name_private . ' is not active! Aborting assignment process');
            return;
        }

        $assign_count = (is_numeric($count) && $count > 0) ? $count : DS_Setting('turksim.assignments_count', 4);

        $where_pirep = [];
        $where_pirep['user_id'] = $user->id;
        $where_pirep['state'] = 2;

        // Avoided Flights
        $avoid_flown = DS_Setting('turksim.assignments_flown', false);
        $avoid_tours = DS_Setting('turksim.assignments_tours', false);
        $avoid_array = [];

        if ($avoid_flown) {
            $avoid_array = DB::table('pireps')->where($where_pirep)->whereNotNull('flight_id')->groupby('flight_id')->pluck('flight_id')->toArray();
        }

        if ($avoid_tours) {
            $tour_codes = DB::table('disposable_tours')->groupby('tour_code')->pluck('tour_code')->toArray();
            $tour_flights = DB::table('flights')->whereIn('route_code', $tour_codes)->pluck('id')->toArray();
            $avoid_array = array_merge($avoid_array, $tour_flights);
            $avoid_array = array_unique($avoid_array, SORT_STRING);
        }

        // Average Flight Time
        $use_avgtime = DS_Setting('turksim.assignments_avgtime', false);
        $margin = DS_Setting('turksim.assignments_margin', 30);
        $min_ftime = null;
        $max_ftime = null;

        if ($use_avgtime) {
            // Min-Max flight time (detirmined by avg pirep flight time and margin)
            $avg_ftime = DB::table('pireps')->where($where_pirep)->whereNotNull('flight_time')->avg('flight_time');
            $min_ftime = is_numeric($avg_ftime) ? round($avg_ftime - $margin) : 60;
            $max_ftime = is_numeric($avg_ftime) ? round($avg_ftime + $margin) : 120;
        }

        // Suitable flights
        $force_airline = setting('pilots.restrict_to_company', false);
        $force_hubs = DS_Setting('turksim.assignments_usehubs', false);
        $force_rank = setting('pireps.restrict_aircraft_to_rank', false);
        $prefer_icao = DS_Setting('turksim.assignments_preficao', false);

        $where_flight = [];
        $where_flight['active'] = 1;

        if ($force_airline) {
            $where_flight['airline_id'] = $user->airline_id;
        }
        if ($force_hubs) {
            $where_flight['dpt_airport_id'] = $user->home_airport_id;
        } else {
            $where_flight['dpt_airport_id'] = filled($user->curr_airport_id) ? $user->curr_airport_id : $user->home_airport_id;
        }

        if ($prefer_icao) {
            // Preferred ICAO types (detirmined by pireps)
            $used_aircraft = DB::table('pireps')->where($where_pirep)->whereNotNull('aircraft_id')->groupby('aircraft_id')->pluck('aircraft_id')->toArray();
            $used_types = DB::table('aircraft')->whereIn('id', $used_aircraft)->groupby('icao')->pluck('icao')->toArray();
            $subfleets = DB::table('aircraft')->whereIn('icao', $used_types)->groupby('subfleet_id')->pluck('subfleet_id')->toArray();
        } else {
            // Allowed subfleets by rank or all
            $subfleets = ($force_rank) ? $user->rank->subfleets()->pluck('id')->toArray() : DB::table('subfleets')->pluck('id')->toArray();
        }

        $suitable_flights = [];
        if ($prefer_icao || $force_rank) {
            $suitable_flights = DB::table('flight_subfleet')->whereIn('subfleet_id', $subfleets)->groupby('flight_id')->pluck('flight_id')->toArray();

            if (is_countable($suitable_flights) && count($suitable_flights) === 0) {
                // No subfleets found assigned to flights, revert restrictions to false and move on
                Log::debug('Disposable Assignments, Subfleets not assigned to flights. Reverting to less-restricted mode');
                $prefer_icao = false;
                $force_rank = false;
            }
        }

        // Counters for loop changes
        $ext_ftime = false;
        $pass_time = 1;
        $pass_airport = 1;

        // Find flights
        $i = 1;
        $picked_flights = [];

        while ($i <= $assign_count) {
            $flights = Flight::where($where_flight)->whereNotIn('id', $picked_flights)
                ->when($use_avgtime, function ($query) use ($min_ftime, $max_ftime) {
                    return $query->whereBetween('flight_time', [$min_ftime, $max_ftime]);
                })
                ->when(($avoid_flown || $avoid_tours), function ($query) use ($avoid_array) {
                    return $query->whereNotIn('id', $avoid_array);
                })
                ->when(($prefer_icao || $force_rank), function ($query) use ($suitable_flights) {
                    return $query->whereIn('id', $suitable_flights);
                })
                ->get();

            // No flights found, extend the times to find some
            if (!$flights->count() && $use_avgtime && $pass_time <= 2) {
                $ext_ftime = true;
                $min_ftime = $min_ftime - ($margin * $pass_time);
                $max_ftime = $max_ftime + ($margin * $pass_time);
                $pass_time++;
                continue;
            }

            // No flights found from Hub, try from Current Airport
            if (!$flights->count() && $force_hubs && $pass_airport === 1) {
                $where_flight['dpt_airport_id'] = filled($user->curr_airport_id) ? $user->curr_airport_id : $user->home_airport_id;
                $pass_airport++;
                continue;
            }

            // No flights found from Current, try from Hub Airport
            if (!$flights->count() && !$force_hubs && $pass_airport === 1) {
                $where_flight['dpt_airport_id'] = $user->home_airport_id;
                $pass_airport++;
                continue;
            }

            // Still no flights, break the operation and return
            if (!$flights->count() && $pass_airport > 1) {
                Log::debug('Disposable Assignments, No suitable flights found for ' . $user->name_private . ' aborting process !');
                break;
            }

            // We have some flights, pick one
            $selected = $flights->random();
            $picked_flights[] = $selected->id;
            $selected_flights[$i] = $selected;

            // Change departure airport with picked flight's arrival and proceed
            $where_flight['dpt_airport_id'] = $selected->arr_airport_id;

            // Do not change the airline at non-hub airports (for no airline forced config)
            if (!$force_airline && $selected->arr_airport->hub != 1) {
                $where_flight['airline_id'] = $selected->airline_id;
            } elseif (!$force_airline) {
                unset($where_flight['airline_id']);
            }

            if ($ext_ftime && $use_avgtime) {
                // Return avg time back to defaults and reset the pass
                $min_ftime = $min_ftime + ($margin * $pass_time);
                $max_ftime = $max_ftime - ($margin * $pass_time);
                $ext_ftime = false;
                $pass_time = 1;
            }

            $i++;
        }

        $this->SaveAssignments($user, $selected_flights);
    }

    // Save Assignments
    public function SaveAssignments($user = null, $assignments = null)
    {
        if (is_null($assignments) || is_null($user)) {
            return;
        }

        $now = Carbon::now();
        $as_year = $now->year;
        $as_month = $now->month;

        $check = DS_Assignment::where(['user_id' => $user->id, 'assignment_year' => $as_year, 'assignment_month' => $as_month])->get();

        if ($check->count() > 0) {
            Log::debug('Disposable Assignments, User ' . $user->name_private . ' already have assignments for ' . $as_year . '/' . $as_month . ' skipping.');
            return;
        }

        foreach ($assignments as $order => $flight) {
            $ta = new DS_Assignment;

            $ta->user_id = $user->id;
            $ta->assignment_year = $as_year;
            $ta->assignment_month = $as_month;
            $ta->assignment_order = $order;
            $ta->flight_id = $flight->id;

            $ta->save();

            Log::debug('Disposable Assignments, ' . $as_year . '/' . $as_month . ' Flight (' . $order . ') ' . $flight->id . ' assigned to (' . $user->id . ') ' . $user->name_private);
        }
    }
}
