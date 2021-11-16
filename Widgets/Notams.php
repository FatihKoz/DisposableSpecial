<?php

namespace Modules\DisposableSpecial\Widgets;

use App\Contracts\Widget;
use Carbon\Carbon;
use Modules\DisposableSpecial\Models\DS_Notam;
use Illuminate\Support\Facades\Auth;

class Notams extends Widget
{
    protected $config = ['count' => null, 'user' => false, 'airport' => null, 'airline' => null];

    public function run()
    {
        $count = is_numeric($this->config['count']) ? $this->config['count'] : null;
        $remove_array = ['<p>', '</p>', '<br>', '<br/>', '<br />', '<hr>', '<hr/>', '<hr />'];

        $now = Carbon::now();
        $where = [];
        $where['active'] = 1;

        if ($this->config['user'] === true) {
            $user = Auth::user();
            $userloc = $user->curr_airport_id ?? $user->home_airport_id;

            if (!empty($userloc)) {
                $where['ref_airport'] = $userloc;
            }
        }

        if ($this->config['user'] === false && !empty($this->config['airport'])) {
            $where['ref_airport'] = $this->config['airport'];
        }

        if (is_numeric($this->config['airline'])) {
            $where['ref_airline'] = $this->config['airline'];
        }

        $notams = DS_Notam::with(['airline', 'airport'])->where($where)
            ->where(function ($query) use ($now) {
                return $query->where('eff_start', '<', $now)->where('eff_end', '>', $now)
                    ->orWhere('eff_start', '<', $now)->whereNull('eff_end');
            })->when(is_numeric($count), function ($query) use ($count) {
                return $query->take($count);
            })
            ->orderby('updated_at', 'desc')->get();

        return view('DSpecial::widgets.notams', [
            'config' => $this->config,
            'notams' => $notams,
            'remove' => $remove_array,
        ]);
    }
}
