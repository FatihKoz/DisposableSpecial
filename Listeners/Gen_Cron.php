<?php

namespace Modules\DisposableSpecial\Listeners;

use App\Contracts\Listener;
use App\Events\CronHourly;
use App\Events\CronMonthly;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Modules\DisposableSpecial\Http\Controllers\DS_AssignmentController;
use Modules\DisposableSpecial\Models\DS_Maintenance;

class Gen_Cron extends Listener
{
    // Callback to proper method
    public static $callbacks = [
        CronHourly::class  => 'handle_hourly',
        CronMonthly::class => 'handle_monthly',
    ];

    // Cron Hourly
    public function handle_hourly()
    {
        $this->ProcessMaintenance();
    }

    // Cron Monthly
    public function handle_monthly()
    {
        if (DS_Setting('turksim.assignments_auto', false)) {
            $this->AssignFlights();
        }
    }

    // Assign Monthly Flights
    public function AssignFlights()
    {
        $flightAssignments = app(DS_AssignmentController::class);
        $flightAssignments->TriggerAssignment();
    }

    // Process Maintenance Records and Release Aircraft Back To Service
    public function ProcessMaintenance()
    {
        $current_time = Carbon::now();
        $active_maint_ops = DS_Maintenance::with('aircraft')->whereNotNull('act_end')->get();
        foreach ($active_maint_ops as $active) {
            if ($active->act_end < $current_time) {
                $active->last_note = $active->act_note;
                $active->last_time = $active->act_end;
                $active->act_note = null;
                $active->act_start = null;
                $active->act_end = null;
                if ($active->aircraft->status === 'M') {
                    $active->aircraft->status = 'A';
                    $active->aircraft->save();
                }
                $active->save();
                Log::info('CRON, ' . $active->aircraft->registration . ' released back to service after ' . $active->last_note);
            }
        }
    }
}
