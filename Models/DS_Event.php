<?php

namespace Modules\DisposableSpecial\Models;

use App\Contracts\Model;
use App\Models\Flight;
use App\Models\User;
use Carbon\Carbon;

class DS_Event extends Model
{
    public $table = 'disposable_events';

    protected $fillable = [
        'event_name',
        'event_code',
        'event_desc',
        'start_date',
        'end_date',
        'public',
    ];

    public static $rules = [
        'event_name'    => 'required|max:150',
        'event_code'    => 'required|max:5',
        'event_desc'    => 'nullable',
        'start_date'    => 'required',
        'end_date'      => 'nullable',
        'public'        => 'nullable',
    ];

    // Attributes
    public function getLiveAttribute()
    {
        return (Carbon::now()->between($this->start_date, $this->end_date, true)) ? true : false;
    }

    // Relationship with legs (flights)
    public function flights()
    {
        return $this->hasMany(Flight::class, 'route_code', 'event_code');
    }

    // Relationship with users (pilots)
    public function users()
    {
        return $this->belongsToMany(User::class, 'disposable_event_user', 'event_id', 'user_id');
    }
}
