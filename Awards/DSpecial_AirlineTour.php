<?php

namespace Modules\DisposableSpecial\Awards;

use App\Contracts\Award;
use App\Models\Pirep;
use App\Models\Enums\PirepState;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Modules\DisposableSpecial\Models\DS_Tour;

class DSpecial_AirlineTour extends Award
{
    public $name = 'Airline Tours';
    public $param_description = 'The airline specific tour code which users needs to complete';

    public function check($tour_code = null): bool
    {
        if (!$tour_code) {
            Log::error('Disposable Special | Tour Code Not Set');
            return false;
        }

        $tour = DS_Tour::withCount('legs')->where('tour_code', $tour_code)->where('active', 1)->first();

        if (filled($tour)) {
            if (Carbon::now()->between($tour->start_date->startOfDay(), $tour->end_date->endOfDay()) === false) {
                // Current date is not between tour start/end dates
                return false;
            }
            if (blank($tour->tour_airline)) {
                // Airline not defined, this may be an open tour
                Log::error('Disposable Special | Airline Tour Award class is not designed for Open Tour checks');
                return false;
            }
        } else {
            // Tour not found or not active
            return false;
        }

        // Check if the legs have any specific start/end dates
        // Result will be used for enabling per flight checks
        $start_dates_count = $tour->legs()->whereNotNull('start_date')->count();
        $end_dates_count = $tour->legs()->whereNotNull('end_date')->count();
        $deep_check = ($start_dates_count + $end_dates_count) > 0 ? true : false;

        $user_id = $this->user->id;

        $pirep_where = [
            'user_id'    => $user_id,
            'route_code' => $tour->tour_code,
            'airline_id' => $tour->tour_airline,
            'state'      => PirepState::ACCEPTED,
            ['submitted_at', '>=', $tour->start_date],
            ['submitted_at', '<=', $tour->end_date]
        ];

        $ordered_user_pireps = Pirep::where($pirep_where)->whereNotNull('route_leg')->orderBy('submitted_at', 'asc')->pluck('route_leg')->toArray();
        $ordered_tour_flights = $tour->legs()->whereNotNull('route_leg')->orderBy('route_leg', 'asc')->pluck('route_leg')->toArray();

        $pirep_order_check = array_intersect_assoc($ordered_tour_flights, $ordered_user_pireps);

        // If the intersection of arrays do not give what we want, return false
        // No need to proceed and do a flight based check
        if (count($ordered_tour_flights) != count($pirep_order_check)) {
            return false;
        } elseif (count($ordered_tour_flights) == count($pirep_order_check) && $deep_check === false) {
            return true;
        }

        // We passed all basic checks, now it is time for a per flight check
        // This takes time and slightly slow
        if ($deep_check === true) {
            Log::debug('Disposable Special | ' . $tour->tour_code . ' Tour has start/end dates defined for legs, deep checks enabled');
            $tour->loadMissing('legs');
            $pirep_count = 0;

            foreach ($tour->legs as $fl) {
                $start_date = $fl->start_date ?? $tour->start_date;
                $end_date = $fl->end_date ?? $tour->end_date;

                $where = [
                    'user_id'        => $user_id,
                    'airline_id'     => $tour->tour_airline,
                    'route_code'     => $fl->route_code,
                    'route_leg'      => $fl->route_leg,
                    'dpt_airport_id' => $fl->dpt_airport_id,
                    'arr_airport_id' => $fl->arr_airport_id,
                    'state' => PirepState::ACCEPTED,
                    ['submitted_at', '>=', $start_date],
                    ['submitted_at', '<=', $end_date]
                ];

                $pirep_check = Pirep::where($where)->count();

                if ($pirep_check > 0) {
                    $pirep_count++;
                }
            }

            return ($pirep_count > 0 && $tour->legs_count == $pirep_count) ? true : false;
        }
    }
}
