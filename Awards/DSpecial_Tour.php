<?php

namespace Modules\DisposableSpecial\Awards;

use App\Contracts\Award;
use App\Models\Pirep;
use Carbon\Carbon;
use Modules\DisposableSpecial\Models\DS_Tour;

class DSpecial_Tour extends Award
{
    public $name = 'Open Tours';
    public $param_description = 'The tour code which users needs to complete';

    public function check($tour_code = null): bool
    {
        if (!$tour_code) {
            $tour_code = 'FK978';
        }

        $user_id = $this->user->id;
        $pirep_count = 0;
        $tour = DS_Tour::withCount('legs')->with('legs')->where('tour_code', $tour_code)->first();

        if (filled($tour)) {
            foreach ($tour->legs as $fl) {
                $start_date = $fl->start_date ?? $tour->start_date;
                $end_date = $fl->end_date ?? $tour->end_date;

                $where = [
                    'user_id' => $user_id,
                    'flight_id' => $fl->id,
                    'state' => 2
                ];

                $orWhere = [
                    'route_code' => $fl->route_code,
                    'route_leg' => $fl->route_leg,
                    'dpt_airport_id' => $fl->dpt_airport_id,
                    'arr_airport_id' => $fl->arr_airport_id,
                    'state' => 2
                ];

                $whereDate = [
                    ['submitted_at', '>=', $start_date],
                    ['submitted_at', '<=', $end_date]
                ];

                $pirep_check = Pirep::where($where)->where($whereDate)
                    ->orWhere('user_id', $user_id)->where($orWhere)->where($whereDate)
                    ->count();

                if ($pirep_check) {
                    $pirep_count++;
                }
            }
        }

        return ($pirep_count > 0 && $tour->legs_count == $pirep_count) ? true : false;
    }
}
