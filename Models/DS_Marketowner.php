<?php

namespace Modules\DisposableSpecial\Models;

use App\Contracts\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DS_Marketowner extends Model
{
    public $table = 'disposable_marketitem_owner';

    protected $fillable = [
        'marketitem_id',
        'user_id',
    ];

    public static $rules = [
        'marketitem_id' => 'required|numeric',
        'user_id'       => 'required|numeric',
    ];

    // Relationship with user
    public function owner(): HasOne
    {
        return $this->HasOne(User::class, 'id', 'user_id');
    }

    // Relationship with item
    public function item(): HasOne
    {
        return $this->HasOne(DS_Marketitem::class, 'id', 'marketitem_id');
    }
}
