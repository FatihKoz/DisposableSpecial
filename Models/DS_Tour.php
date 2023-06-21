<?php

namespace Modules\DisposableSpecial\Models;

use App\Contracts\Model;
use App\Models\Flight;
use App\Models\Airline;
use Carbon\Carbon;

class DS_Tour extends Model
{
    public $table = 'disposable_tours';

    protected $fillable = [
        'tour_name',
        'tour_code',
        'tour_desc',
        'tour_rules',
        'tour_airline',
        'start_date',
        'end_date',
        'active',
    ];

    // Validation
    public static $rules = [
        'tour_name'    => 'required|max:150',
        'tour_code'    => 'required|max:5',
        'tour_desc'    => 'nullable',
        'tour_rules'   => 'nullable',
        'tour_airline' => 'nullable',
        'start_date'   => 'required',
        'end_date'     => 'required',
        'active'       => 'nullable',
    ];

    // Carbon Coverted Dates
    public $casts = [
        'start_date' => 'datetime',
        'end_date'   => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Attributes
    public function getLiveAttribute()
    {
        return (Carbon::now()->between($this->start_date, $this->end_date, true)) ? true : false;
    }

    // Relationship with flights (legs)
    public function legs()
    {
        return $this->hasMany(Flight::class, 'route_code', 'tour_code');
    }

    // Relationship to airline
    public function airline()
    {
        return $this->belongsTo(Airline::class, 'tour_airline', 'id');
    }
}
