<?php

namespace Modules\DisposableSpecial\Services;

use Carbon\Carbon;
use Modules\DisposableSpecial\Models\DS_Maintenance;
use Illuminate\Support\Facades\Log;

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

            if ($active->aircraft->status === 'M') {
                $active->aircraft->status = 'A';
                $active->aircraft->save();
            }

            $active->save();
            Log::info('Disposable Special | ' . $active->aircraft->registration . ' released back to service after ' . $active->last_note);
        }
    }
}