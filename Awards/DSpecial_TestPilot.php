<?php

namespace Modules\DisposableSpecial\Awards;

use App\Contracts\Award;
use App\Models\Pirep;

class DSpecial_TestPilot extends Award
{
    public $name = 'Test Pilot';
    public $param_description = 'The date when ops started. Enter like 2020-10-26';

    public function check($start_date = null): bool
    {
        if (!$start_date) {
            $start_date = '2021-01-01';
        }

        $where = [
            'user_id' => $this->user->id,
            'state' => 2
        ];

        $check = Pirep::where($where)->where('submitted_at', '<', $start_date)->count();

        return ($check > 0) ? true : false;
    }
}
