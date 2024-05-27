<?php

namespace Modules\DisposableSpecial\Models;

use App\Contracts\Model;

class DS_Setting extends Model
{
    public $table = 'disposable_settings';

    protected $fillable = [
        'name',
        'key',
        'value',
        'default',
        'group',
        'field_type',
        'options',
        'desc',
        'order',
    ];

    public static $rules = [
        'name'       => 'required',
        'key'        => 'required',
        'value'      => 'nullable',
        'default'    => 'required',
        'group'      => 'nullable',
        'field_type' => 'nullable',
        'options'    => 'nullable',
        'desc'       => 'nullable',
        'order'      => 'nullable',
    ];
}
