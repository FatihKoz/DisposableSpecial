<?php

namespace Modules\DisposableSpecial\Widgets;

use App\Contracts\Widget;
use App\Models\Pirep;
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
    $pireps = Pirep::whereNotNull('route_leg')->where(['user_id' => $user_id, 'state' => 2, 'status'  => 'ONB'])
      ->whereIn('route_code', $tour_codes)
      ->selectRaw('route_code as tour_code')->selectRaw('count(DISTINCT route_leg) as flown_legs')
      ->groupby('route_code')->get();

    // Prepare Tour Progress array
    $progress = [];
    foreach ($tours as $tour) {
      foreach ($pireps as $pirep) {
        if ($pirep->tour_code === $tour->tour_code) {
          $ratio = ceil((100 * $pirep->flown_legs) / $tour->legs_count);
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
