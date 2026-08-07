<?php

namespace Modules\DisposableSpecial\Models;

use App\Contracts\Model;
use App\Models\Flight;
use Carbon\Carbon;

class DS_EventMeta extends Model
{
    public $table = 'disposable_event_meta';

    protected $fillable = [
        'event_id',
        'name',
        'slug',
        'value',
    ];

    // Validation
    public static $rules = [
        'event_id'  => 'required|integer',
        'name'      => 'required|string',
        'slug'      => 'required|string',
        'value'     => 'required|string',
    ];

    // Carbon Coverted Dates
    public $dates = [
        'created_at',
        'updated_at',
    ];

    // Relationship with event
    public function event()
    {
        return $this->belongsTo(DS_Event::class, 'event_id');
    }
}
