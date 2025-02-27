<?php

namespace Modules\DisposableSpecial\Widgets;

use App\Contracts\Widget;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Modules\DisposableSpecial\Models\DS_Mission;

class Missions extends Widget
{
    protected $config = ['user' => null];

    public function run()
    {
        $user_id = $this->config['user'] ?? Auth::id();

        $eager_load = ['flight.airline', 'dpt_airport', 'arr_airport', 'aircraft'];

        $my_missions = DS_Mission::with($eager_load)->whereNull('pirep_id')->where('user_id', $user_id)->where('mission_valid', '>', Carbon::now())->orderBy('mission_order', 'asc')->get();

        return view('DSpecial::widgets.missions', [
            'DBasic'     => check_module('DisposableBasic'),
            'missions'   => $my_missions,
            'is_visible' => ($my_missions->count() > 0) ? true : false,
        ]);
    }
}
