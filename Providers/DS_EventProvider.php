<?php

namespace Modules\DisposableSpecial\Providers;

use App\Events\Expenses;
use App\Events\Fares;
use App\Events\PirepAccepted;
use App\Events\PirepFiled;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\DisposableSpecial\Listeners\Expense_Airport;
use Modules\DisposableSpecial\Listeners\Expense_Enroute;
use Modules\DisposableSpecial\Listeners\Expense_Fuel;
use Modules\DisposableSpecial\Listeners\Expense_Maintenance;
use Modules\DisposableSpecial\Listeners\Fare_InFlight;
use Modules\DisposableSpecial\Listeners\Gen_Comments;
use Modules\DisposableSpecial\Listeners\Gen_Cron;
use Modules\DisposableSpecial\Listeners\Gen_Diversion;
use Modules\DisposableSpecial\Listeners\Gen_Maintenance;
use Modules\DisposableSpecial\Listeners\Gen_RandomFlights;

class DS_EventProvider extends ServiceProvider
{
    // Listen individual events
    protected $listen =
        [
            Expenses::class => [
                Expense_Airport::class,
                Expense_Enroute::class,
                Expense_Fuel::class,
                Expense_Maintenance::class,
            ],
            Fares::class => [
                Fare_InFlight::class,
            ],
            PirepFiled::class => [
                Gen_Comments::class,
            ],
            PirepAccepted::class => [
                Gen_Diversion::class,
                Gen_RandomFlights::class,
            ],
        ];

    // Subscribe multiple events
    protected $subscribe =
        [
            Gen_Maintenance::class,
            Gen_Cron::class,
        ];

    // Register module events
    public function boot()
    {
        parent::boot();
    }
}
