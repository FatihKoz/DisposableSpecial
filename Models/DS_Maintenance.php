<?php

namespace Modules\DisposableSpecial\Models;

use App\Contracts\Model;
use App\Models\Aircraft;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
        'last_state',
        'op_type',
        'rem_ta',
        'rem_tb',
        'rem_tc',
        'rem_ca',
        'rem_cb',
        'rem_cc',
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
        'last_state'  => 'nullable',
        'op_type'     => 'nullable',
        'rem_ta'      => 'nullable',
        'rem_tb'      => 'nullable',
        'rem_tc'      => 'nullable',
        'rem_ca'      => 'nullable',
        'rem_cb'      => 'nullable',
        'rem_cc'      => 'nullable',
    ];

    protected $casts = [
        'act_start'  => 'datetime',
        'act_end'    => 'datetime',
        'last_time'  => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $attributes = [
        'curr_state' => 100,
    ];

    protected $appends = [
        'remaining_times',
        'remaining_cycles',
        'limits',
    ];

    // Attributes
    // Remaining Times
    public function getRemainingTimesAttribute()
    {
        $times = collect();

        $times->a = $this->rem_ta;
        $times->b = $this->rem_tb;
        $times->c = $this->rem_tc;

        return $times;
    }

    // Remaining Cycles
    public function getRemainingCyclesAttribute()
    {
        $cycles = collect();

        $cycles->a = $this->rem_ca;
        $cycles->b = $this->rem_cb;
        $cycles->c = $this->rem_cc;

        return $cycles;
    }

    // Limits Attribute
    // Via Disposable Basic > Tech Model
    // Use module settings as failsafe
    public function getLimitsAttribute()
    {
        $limits = new stdClass();
        $dtech = null;

        if (check_module('DisposableBasic')) {
            $dtech = DB_Tech::where(['icao' => optional($this->aircraft)->icao, 'active' => 1])->first();
        }

        // A Check
        $limits->cycle_a = !empty($dtech->max_cycle_a) ? $dtech->max_cycle_a : DS_Setting('turksim.maint_lim_ac', 250);
        $limits->time_a = 60 * (!empty($dtech->max_time_a) ? $dtech->max_time_a : DS_Setting('turksim.maint_lim_at', 500));
        $limits->duration_a = 60 * (!empty($dtech->duration_a) ? $dtech->duration_a : DS_Setting('turksim.maint_hours_a', 10));
        // B Check
        $limits->cycle_b = !empty($dtech->max_cycle_b) ? $dtech->max_cycle_b : DS_Setting('turksim.maint_lim_bc', 500);
        $limits->time_b = 60 * (!empty($dtech->max_time_b) ? $dtech->max_time_b : DS_Setting('turksim.maint_lim_bt', 1000));
        $limits->duration_b = 60 * (!empty($dtech->duration_b) ? $dtech->duration_b : DS_Setting('turksim.maint_hours_b', 48));
        // C Check
        $limits->cycle_c = !empty($dtech->max_cycle_c) ? $dtech->max_cycle_c : DS_Setting('turksim.maint_lim_cc', 2500);
        $limits->time_c = 60 * (!empty($dtech->max_time_c) ? $dtech->max_time_c : DS_Setting('turksim.maint_lim_ct', 5000));
        $limits->duration_c = 60 * (!empty($dtech->duration_c) ? $dtech->duration_c : DS_Setting('turksim.maint_hours_c', 120));
        // Pitch and Roll Limits
        $limits->pitch = !empty($dtech->max_pitch) ? $dtech->max_pitch : DS_Setting('turksim.maint_strtail_limit', 15);
        $limits->roll = !empty($dtech->max_roll) ? $dtech->max_roll : DS_Setting('turksim.maint_strwing_limit', 10);

        return $limits;
    }

    // Relationship to aircraft
    public function aircraft(): HasOne
    {
        return $this->hasOne(Aircraft::class, 'id', 'aircraft_id');
    }
}
