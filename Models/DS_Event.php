<?php

namespace Modules\DisposableSpecial\Models;

use App\Contracts\Model;
use App\Models\Flight;
use App\Models\Pirep;
use Carbon\Carbon;

class DS_Event extends Model
{
    public $table = 'disposable_events';

    protected $fillable = [
        'name',
        'type',
        'thumb_url',
        'image_url',
        'desc',
        'rules',
        'public',
        'active',
        'visible',
        'start_at',
        'end_at',
    ];

    // Validation
    public static $rules = [
        'name'          => 'required|max:250',
        'type'          => 'required|integer',
        'thumb_url'     => 'nullable',
        'image_url'     => 'nullable',
        'desc'          => 'nullable|string',
        'rules'         => 'nullable|string',
        'public'        => 'required|boolean',
        'active'        => 'required|boolean',
        'visible'       => 'required|boolean',
        'start_at'      => 'required|datetime',
        'end_at'        => 'required|datetime',
    ];

    // Carbon Coverted Dates
    public $dates = [
        'start_at',
        'end_at',
        'created_at',
        'updated_at',
    ];

    // Attributes
    public function getLiveAttribute()
    {
        return (Carbon::now()->between($this->start_at, $this->end_at, true)) ? true : false;
    }

    public function attendants()
    {
        return $this->users()->where('attend', true)->count();
    }

    public function completed()
    {
        return $this->users()->where('completed', true)->count();
    }

    // Relationship with flights (legs)
    public function legs()
    {
        return $this->hasMany(Flight::class, 'event_id', 'id');
    }

    // Relationship with pireps (legs flown)
    public function pireps()
    {
        return $this->hasMany(Pirep::class, 'event_id', 'id');
    }

    // Relationship with event meta
    public function meta()
    {
        return $this->hasMany(DS_EventMeta::class, 'event_id', 'id');
    }

    // Relationship to users
    public function users()
    {
        return $this->hasMany(DS_EventUser::class, 'event_id', 'id');
    }
}
