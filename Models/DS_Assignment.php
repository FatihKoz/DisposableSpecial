<?php

namespace Modules\DisposableSpecial\Models;

use App\Contracts\Model;
use App\Models\Flight;
use App\Models\Pirep;
use App\Models\User;

class DS_Assignment extends Model
{
    public $table = 'disposable_assignments';

    protected $fillable = [
        'user_id',
        'assignment_year',
        'assignment_month',
        'assignment_order',
        'flight_id',
        'pirep_id',
        'pirep_date',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'pirep_date',
    ];

    // Validation rules
    public static $rules = [
        'user_id'          => 'required',
        'assignment_year'  => 'required',
        'assignment_month' => 'required',
        'assignment_order' => 'required',
        'flight_id'        => 'required',
        'pirep_id'         => 'nullable',
        'pirep_date'       => 'nullable',
    ];

    // Relationship to Flight
    public function flight()
    {
        return $this->hasOne(Flight::class, 'id', 'flight_id');
    }

    // Relationship to User
    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    // Relationship to Pirep
    public function pirep()
    {
        return $this->hasOne(Pirep::class, 'id', 'pirep_id');
    }

    // Completed Attribute
    public function getCompletedAttribute()
    {
        $pirep = Pirep::where([
            'user_id'   => $this->user_id,
            'flight_id' => $this->flight_id,
            'state'     => 2,
        ])->whereMonth('created_at', $this->assignment_month)->whereYear('submitted_at', $this->assignment_year)->first();

        return isset($pirep) ? true : false;
    }
}
