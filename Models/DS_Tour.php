<?php

namespace Modules\DisposableSpecial\Models;

use App\Contracts\Model;
use App\Models\Airline;
use App\Models\Flight;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DS_Tour extends Model
{
    public $table = 'disposable_tours';

    protected $fillable = [
        'tour_name',
        'tour_code',
        'tour_desc',
        'tour_rules',
        'tour_airline',
        'tour_token',
        'tour_fplremark',
        'start_date',
        'end_date',
        'active',
    ];

    public static $rules = [
        'tour_name'      => 'required|max:150',
        'tour_code'      => 'required|max:5',
        'tour_desc'      => 'nullable',
        'tour_rules'     => 'nullable',
        'tour_airline'   => 'nullable',
        'tour_token'     => 'nullable',
        'tour_fplremark' => 'nullable|max:100',
        'start_date'     => 'required',
        'end_date'       => 'required',
        'active'         => 'nullable',
    ];

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
    public function legs(): HasMany
    {
        return $this->hasMany(Flight::class, 'route_code', 'tour_code');
    }

    // Relationship to airline
    public function airline(): BelongsTo
    {
        return $this->belongsTo(Airline::class, 'tour_airline', 'id');
    }

    // Tour Token (Market Item)
    public function token(): BelongsTo
    {
        return $this->belongsTo(DS_Marketitem::class, 'tour_token', 'id');
    }
}
