<?php

namespace Modules\DisposableSpecial\Awards;

use App\Contracts\Award;
use App\Models\Enums\PirepState;
use App\Models\Pirep;
use Illuminate\Support\Facades\Log;

class DSpecial_TestPilot extends Award
{
    public $name = 'Test Pilot';
    public $param_description = 'The date when ops started. Enter like 2020-10-26';

    public function check($start_date = null): bool
    {
        if (!$start_date) {
            Log::error('Disposable Special | Test Pilot Award Date Not Set');

            return false;
        }

        $where = [
            'user_id' => $this->user->id,
            'state'   => PirepState::ACCEPTED,
        ];

        $check = Pirep::where($where)->where('submitted_at', '<', $start_date)->count();

        return ($check > 0) ? true : false;
    }
}
