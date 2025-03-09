<?php

namespace Modules\DisposableSpecial\Models;

use App\Contracts\Model;
use App\Models\Flight;
use App\Models\User;
use Carbon\Carbon;

class DS_EventUser extends Model
{
    public $table = 'disposable_event_users';

    protected $fillable = [
        'event_id',
        'user_id',
        'attend',
        'completed',
    ];

    // Validation
    public static $rules = [
        'event_id'  => 'required|integer',
        'user_id'   => 'required|integer',
        'attend'    => 'nullable|boolean',
        'completed' => 'required|boolean',
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

    // Relationship with user
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
