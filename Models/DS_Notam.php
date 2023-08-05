<?php

namespace Modules\DisposableSpecial\Models;

use App\Contracts\Model;
use App\Models\Airline;
use App\Models\Airport;
use Carbon\Carbon;

class DS_Notam extends Model
{
    public $table = 'disposable_notams';

    protected $fillable = [
        'title',
        'body',
        'eff_start',
        'eff_stime',
        'eff_end',
        'eff_etime',
        'ref_airport',
        'ref_airline',
        'ref_notamid',
        'active',
    ];

    // Validation
    public static $rules = [
        'title'        => 'required|max:250',
        'body'         => 'required',
        'eff_start'    => 'required',
        'eff_stime'    => 'nullable|max:4',
        'eff_end'      => 'nullable',
        'eff_etime'    => 'nullable|max:4',
        'ref_airport'  => 'nullable',
        'ref_airline'  => 'nullable',
        'ref_notamid'  => 'nullable',
        'active'       => 'nullable',
    ];

    // Attributes
    public function getIdentAttribute(): string
    {
        $serial = str_pad($this->id, 4, '0', STR_PAD_LEFT);

        if (isset($this->ref_airline)) {
            $serial = 'C' . $serial;
        } elseif (isset($this->ref_airport)) {
            $serial = 'A' . $serial;
        }

        $serial .= '/' . $this->created_at->format('y');

        if (isset($this->ref_notamid)) {
            $serial .= ' NOTAMR ' . str_pad($this->ref_notamid, 4, '0', STR_PAD_LEFT);
        } else {
            $serial .= ' NOTAMN';
        }

        return $serial;
    }

    public function getEffectiveFromAttribute(): string
    {
        $from = 'NIL';
        if (isset($this->eff_start)) {
            $from = Carbon::parse($this->eff_start)->format('ymd');

            if (isset($this->eff_stime)) {
                $from .= $this->eff_stime;
            } else {
                $from .= '0000';
            }
        }
        return $from;
    }

    public function getEffectiveUntilAttribute(): string
    {
        $until = 'UFN';
        if (isset($this->eff_end)) {
            $until = Carbon::parse($this->eff_end)->format('ymd');

            if (isset($this->eff_etime)) {
                $until .= $this->eff_etime;
            } else {
                $until .= '2359';
            }
        }
        return $until;
    }

    // Relationship to airline
    public function airline()
    {
        return $this->hasOne(Airline::class, 'id', 'ref_airline');
    }

    // Relationship to airport
    public function airport()
    {
        return $this->hasOne(Airport::class, 'id', 'ref_airport');
    }
}
