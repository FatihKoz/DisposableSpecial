<?php

namespace Modules\DisposableSpecial\Widgets;

use App\Contracts\Widget;
use App\Models\Enums\PirepState;
use App\Models\Enums\PirepStatus;
use App\Models\Pirep;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Modules\DisposableSpecial\Models\DS_Tour;

class TourProgress extends Widget
{
    protected $config = ['user' => null, 'warn' => null, 'ftype' => null];

    public function run()
    {
        $user_id = $this->config['user'] ?? Auth::user()->id;
        $warn_days = is_numeric($this->config['warn']) ? $this->config['warn'] : 14;
        $now = Carbon::now();

        // Get Tours and prepare Tour Codes Array
        $tours = DS_Tour::withCount('legs')->where('active', 1)->whereDate('start_date', '<=', $now)->whereDate('end_date', '>=', $now)->get();
        $tour_codes = $tours->pluck('tour_code')->toArray();

        // Get Tour pireps
        $pirep_where = ['user_id' => $user_id, 'state' => PirepState::ACCEPTED, 'status'  => PirepStatus::ARRIVED];
        $pireps = Pirep::whereNotNull('route_leg')->where($pirep_where)
            ->whereIn('route_code', $tour_codes)
            ->selectRaw('route_code as tour_code, count(DISTINCT route_leg) as flown_legs')
            ->groupby('route_code')->get();

        // Prepare Tour Progress array
        $progress = [];
        foreach ($tours as $tour) {
            foreach ($pireps as $pirep) {
                if ($pirep->tour_code === $tour->tour_code) {
                    // Get User Pireps which are flown between tour start-end dates
                    $user_pireps = Pirep::where($pirep_where)->where('route_code', $tour->tour_code)
                        ->whereBetween('submitted_at', [$tour->start_date, $tour->end_date])
                        ->whereNotNull('route_leg')
                        ->orderBy('submitted_at')
                        ->pluck('route_leg')
                        ->toArray();

                    // Calculate the ratio for Progress Bar
                    $pirep_count = count(array_unique($user_pireps));
                    $leg_count = ($tour->legs_count > 0) ? $tour->legs_count : 100;
                    $ratio = ceil((100 * $pirep_count) / $leg_count);

                    // Check order of flown legs
                    $tour_order = range(1, $pirep_count);
                    $order_check = ($tour_order == $user_pireps) ? true : false;
                    if ($order_check === false) {
                        $bar_color = 'bg-danger text-black';
                    } else {
                        $bar_color = ($ratio < 100) ? 'bg-warning text-black' : 'bg-success text-black';
                    }

                    // Change the progress bar style and add reminder for closure
                    $diff = $tour->end_date->diffInDays($now);
                    if ($diff < $warn_days) {
                        $reminder_text = '| Last '.$diff.' days until closure !';
                        $warning_style = 'progress-bar-striped';
                    } else {
                        $reminder_text = null;
                        $warning_style = null;
                    }

                    // Prepare the final array
                    $progress[$tour->tour_code] = [
                        'name' => $tour->tour_name,
                        'code' => $tour->tour_code,
                        'legs' => $tour->legs_count,
                        'comp' => $pirep_count,
                        'prog' => $ratio,
                        'barc' => $bar_color,
                        'remd' => $reminder_text,
                        'warn' => $warning_style,
                    ];
                }
            }
        }

        // Prepare Tour Counts array
        $counts = [];
        $counts['user'] = $pireps->count();
        $counts['tour'] = $tours->count();

        return view('DSpecial::widgets.tour_progress', [
            'prog'   => $progress,
            'counts' => $counts,
        ]);
    }
}
