<?php

namespace Modules\DisposableSpecial\Services;

use App\Models\Enums\AircraftStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Modules\DisposableSpecial\Models\DS_Maintenance;

class DS_MaintenanceServices
{
    // Process Maintenance Records and Release Aircraft Back To Service
    public function ProcessMaintenance()
    {
        $current_time = Carbon::now();
        $active_maint_ops = DS_Maintenance::with('aircraft')->whereNotNull('act_end')->where('act_end', '<', $current_time)->get();
        foreach ($active_maint_ops as $active) {
            $active->last_note = $active->act_note;
            $active->last_time = $active->act_end;
            $active->act_note = null;
            $active->act_start = null;
            $active->act_end = null;

            if ($active->aircraft->status === AircraftStatus::MAINTENANCE) {
                $active->aircraft->status = AircraftStatus::ACTIVE;
                $active->aircraft->save();
            }

            $active->save();
            Log::info('Disposable Special | '.$active->aircraft->registration.' released back to service after '.$active->last_note);
        }
    }
}
