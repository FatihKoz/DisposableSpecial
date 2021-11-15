<?php

namespace Modules\DisposableSpecial\Http\Controllers;

use App\Contracts\Controller;

class DS_PageController extends Controller
{
    public function ops_manual()
    {
        return view('DSpecial::pages.ops_manual');
    }

    public function landing_rates()
    {
        return view('DSpecial::pages.landing_rates');
    }

    public function about_us()
    {
        return view('DSpecial::pages.about_us');
    }

    public function rules_regs()
    {
        return view('DSpecial::pages.rules_regs');
    }
}
