<?php

namespace Modules\DisposableSpecial\Widgets;

use App\Contracts\Widget;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Modules\DisposableSpecial\Models\DS_Assignment;

class Assignments extends Widget
{
    protected $config = ['user' => null, 'hide' => true];

    public function run()
    {
        $user_id = $this->config['user'] ?? Auth::id();

        $now = Carbon::now();
        $curr_y = $now->year;
        $curr_m = $now->month;

        $where = [];
        $where['user_id'] = $user_id;
        $where['assignment_year'] = $curr_y;
        $where['assignment_month'] = $curr_m;

        $eager_load = ['flight.airline', 'flight.dpt_airport', 'flight.arr_airport'];

        $monthly = DS_Assignment::with($eager_load)->where($where)->orderBy('assignment_order', 'asc')->get();

        $counts = [];
        $comp = 0;

        foreach ($monthly as $ma) {
            if ($ma->completed) {
                $comp++;
            }
        }

        $counts['completed'] = $comp;
        $counts['total'] = $monthly->count();

        if ($this->config['hide'] === false) {
            $assignments = $monthly;
        } else {
            $assignments = $monthly->where('completed', false);
        }

        return view('DSpecial::widgets.assignments', [
            'assignments' => $assignments,
            'counts'      => $counts,
            'is_visible'  => ($assignments->count() > 0) ? true : false,
            'hide'        => $this->config['hide'],
        ]);
    }
}
