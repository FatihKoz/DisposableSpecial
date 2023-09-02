<?php

namespace Modules\DisposableSpecial\Models;

use App\Contracts\Model;
use App\Models\Aircraft;
use App\Models\Airport;
use App\Models\Flight;
use App\Models\Pirep;
use App\Models\User;
use App\Models\Enums\PirepState;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DS_Mission extends Model
{
    public $table = 'disposable_missions';

    protected $fillable = [
        'user_id',
        'aircraft_id',
        'flight_id',
        'dpt_airport_id',
        'arr_airport_id',
        'mission_type',
        'mission_year',
        'mission_month',
        'mission_order',
        'mission_valid',
        'pirep_id',
        'pirep_date',
    ];

    public static $rules = [
        'user_id'        => 'nullable',
        'aircraft_id'    => 'nullable',
        'flight_id'      => 'nullable',
        'dpt_airport_id' => 'nullable',
        'arr_airport_id' => 'nullable',
        'mission_type'   => 'required',
        'mission_year'   => 'required',
        'mission_month'  => 'required',
        'mission_order'  => 'required',
        'mission_valid'  => 'nullable',
        'pirep_id'       => 'nullable',
        'pirep_date'     => 'nullable',
    ];

    protected $casts = [
        'created_at'    => 'datetime',
        'mission_valid' => 'datetime',
        'pirep_date'    => 'datetime',
        'updated_at'    => 'datetime',
    ];

    // Relationships
    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function aircraft(): HasOne
    {
        return $this->hasOne(Aircraft::class, 'id', 'aircraft_id');
    }

    public function flight(): HasOne
    {
        return $this->hasOne(Flight::class, 'id', 'flight_id');
    }

    public function dpt_airport(): HasOne
    {
        return $this->hasOne(Airport::class, 'id', 'dpt_airport_id');
    }

    public function arr_airport(): HasOne
    {
        return $this->hasOne(Airport::class, 'id', 'arr_airport_id');
    }

    public function pirep(): HasOne
    {
        return $this->hasOne(Pirep::class, 'id', 'pirep_id');
    }

    // Completed Attribute
    public function getCompletedAttribute()
    {
        $pirep = Pirep::where([
            'user_id'        => $this->user_id,
            'aircraft_id'    => $this->aircraft_id,
            'dpt_airport_id' => $this->dpt_airport_id,
            'arr_airport_id' => $this->arr_airport_id,
            'state'          => PirepState::ACCEPTED,
        ])->whereMonth('created_at', $this->mission_month)->whereYear('submitted_at', $this->mission_year)->first();

        return isset($pirep) ? true : false;
    }
}
