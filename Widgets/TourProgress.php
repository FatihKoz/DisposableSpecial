<?php

namespace Modules\DisposableSpecial\Widgets;

use App\Contracts\Widget;
use App\Models\Pirep;
use App\Models\Enums\PirepState;
use App\Models\Enums\PirepStatus;
use Modules\DisposableSpecial\Models\DS_Tour;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

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
          $leg_count = ($tour->legs_count > 0) ? $tour->legs_count : 100;
          $ratio = ceil((100 * $pirep->flown_legs) / $leg_count);
          $end_date = Carbon::parse($tour->end_date);
          $diff = $end_date->diffInDays($now);
          $progress[$tour->tour_code] = [
            'name' => $tour->tour_name,
            'code' => $tour->tour_code,
            'legs' => $tour->legs_count,
            'comp' => $pirep->flown_legs,
            'prog' => $ratio,
            'barc' => ($ratio < 100) ? 'bg-warning text-black' : 'bg-success text-black',
            'remd' => ($diff < $warn_days) ? '| Last '.$diff.' days until closure !' : '',
            'warn' => ($diff < $warn_days) ? 'progress-bar-striped' : '',
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
