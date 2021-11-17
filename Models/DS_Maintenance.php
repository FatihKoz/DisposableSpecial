<?php

namespace Modules\DisposableSpecial\Models;

use App\Contracts\Model;
use App\Models\Aircraft;
use Modules\DisposableBasic\Models\DB_Tech;
use stdClass;

class DS_Maintenance extends Model
{
    public $table = 'disposable_maintenance';

    protected $fillable = [
        'aircraft_id',
        'curr_state',
        'time_a',
        'time_b',
        'time_c',
        'cycle_a',
        'cycle_b',
        'cycle_c',
        'act_note',
        'act_start',
        'act_end',
        'last_a',
        'last_b',
        'last_c',
        'last_note',
        'last_time',
        'op_type',
    ];

    protected $dates = [
        'act_start',
        'act_end',
        'last_time',
        'created_at',
        'updated_at',
    ];

    protected $attributes = [
        'curr_state' => 100,
    ];

    // Validation
    public static $rules = [
        'aircraft_id' => 'required',
        'curr_state'  => 'nullable',
        'time_a'      => 'nullable',
        'time_b'      => 'nullable',
        'time_c'      => 'nullable',
        'cycle_a'     => 'nullable',
        'cycle_b'     => 'nullable',
        'cycle_c'     => 'nullable',
        'act_note'    => 'nullable',
        'act_start'   => 'nullable',
        'act_end'     => 'nullable',
        'last_a'      => 'nullable',
        'last_b'      => 'nullable',
        'last_c'      => 'nullable',
        'last_note'   => 'nullable',
        'last_time'   => 'nullable',
        'op_type'     => 'nullable',
    ];

    // Check Max Limit Attribute
    public function getLimitsAttribute()
    {
        $limits = new stdClass();
        $dtech = null;

        if (DS_CheckModule('DisposableBasic')) {
            $dtech = DB_Tech::where(['icao' => $this->aircraft->icao, 'active' => 1])->first();
        }

        // A Check
        $limits->cycle_a = !empty($dtech->cycle_a) ? $dtech->cycle_a : DS_Setting('turksim.maint_lim_ac', 250);
        $limits->time_a = 60 * (!empty($dtech->time_a) ? $dtech->time_a : DS_Setting('turksim.maint_lim_at', 500));
        $limits->duration_a = 60 * (!empty($dtech->hour_a) ? $dtech->hour_a : DS_Setting('turksim.maint_hours_a', 10));
        // B Check
        $limits->cycle_b = !empty($dtech->cycle_b) ? $dtech->cycle_b : DS_Setting('turksim.maint_lim_bc', 500);
        $limits->time_b = 60 * (!empty($dtech->time_b) ? $dtech->time_b : DS_Setting('turksim.maint_lim_bt', 1000));
        $limits->duration_b = 60 * (!empty($dtech->hour_b) ? $dtech->hour_b : DS_Setting('turksim.maint_hours_b', 48));
        // C Check
        $limits->cycle_c = !empty($dtech->cycle_c) ? $dtech->cycle_c : DS_Setting('turksim.maint_lim_cc', 2500);
        $limits->time_c = 60 * (!empty($dtech->time_c) ? $dtech->time_c : DS_Setting('turksim.maint_lim_ct', 5000));
        $limits->duration_c = 60 * (!empty($dtech->hour_c) ? $dtech->hour_c : DS_Setting('turksim.maint_hours_c', 120));
        // Pitch and Roll Limits
        $limits->pitch = !empty($dtech->max_pitch) ? $dtech->max_pitch : DS_Setting('turksim.maint_strtail_limit', 15);
        $limits->roll = !empty($dtech->max_roll) ? $dtech->max_roll : DS_Setting('turksim.maint_strwing_limit', 10);

        return $limits;
    }

    // Relationship to aircraft
    public function aircraft()
    {
        return $this->hasOne(Aircraft::class, 'id', 'aircraft_id');
    }
}
