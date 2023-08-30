<?php

namespace Modules\DisposableSpecial\Models;

use App\Contracts\Model;
use App\Models\User;
use App\Models\Airline;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kyslik\ColumnSortable\Sortable;

class DS_Marketitem extends Model
{
    use SoftDeletes, Sortable;

    public $table = 'disposable_marketitems';

    protected $fillable = [
        'name',
        'description',
        'price',
        'image_url',
        'group',
        'dealer_id',
        'active',
    ];

    public static $rules = [
        'name'        => 'required|max:250',
        'description' => 'nullable',
        'price'       => 'required|numeric',
        'image_url'   => 'nullable',
        'group'       => 'nullable',
        'dealer_id'   => 'required|numeric',
        'active'      => 'required|boolean',
    ];

    public $sortable = [
        'name',
        'group',
        'price',
    ];

    // Relationship with owners (Based on User model)
    public function owners(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'disposable_marketitem_owner');
    }

    // Relationship with dealer (mostly for financial records, based on Airline model)
    public function dealer(): BelongsTo
    {
        return $this->belongsTo(Airline::class, 'dealer_id');
    }
}
