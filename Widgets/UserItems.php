<?php

namespace Modules\DisposableSpecial\Widgets;

use App\Contracts\Widget;
use App\Models\User;
use Modules\DisposableSpecial\Models\DS_Marketitem;
use Modules\DisposableSpecial\Models\DS_Marketowner;

class UserItems extends Widget
{
    protected $config = ['user' => null];

    public function run()
    {
        $user = is_numeric($this->config['user']) ? User::where('id', $this->config['user'])->first() : null;

        if ($user) {
            $useritems = DS_Marketowner::where('user_id', $user->id)->orderBy('marketitem_id')->pluck('marketitem_id')->toArray();
            $items = DS_Marketitem::where('active', 1)->whereIn('id', $useritems)->orderBy('name')->get();
        }

        return view('DSpecial::widgets.user_items', [
            'items' => isset($items) ? $items : null,
            'units' => DS_GetUnits(),
        ]);
    }
}
