<?php

namespace Modules\DisposableSpecial\Http\Controllers;

use App\Contracts\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\DisposableSpecial\Listeners\Expense_Maintenance;
use Modules\DisposableSpecial\Models\DS_Maintenance;

class DS_MaintenanceController extends Controller
{
    public function index()
    {
        $maintenance = DS_Maintenance::with('aircraft')->where('curr_state', '<', 95)->orderby('curr_state', 'asc')->paginate(20);

        return view('DSpecial::maintenance.index', [
            'DBasic'      => DS_CheckModule('DisposableBasic'),
            'maintenance' => $maintenance,
        ]);
    }

    public function index_admin()
    {
        $active_maint = DS_Maintenance::with('aircraft')->whereNotNull(['act_note', 'act_start', 'act_end'])->get();
        $maintenance = DS_Maintenance::with('aircraft')->whereNull(['act_note', 'act_start', 'act_end'])
            ->where('curr_state', '<', 99)
            ->orderby('curr_state', 'asc')->orderby('aircraft_id')
            ->paginate(10);

        return view('DSpecial::admin.maintenance', [
            'activemaint' => $active_maint,
            'maintenance' => $maintenance,
        ]);
    }

    public function finish_maint(Request $request)
    {
        if (empty($request->id) || empty($request->act_note)) {
            flash()->error('Maintenance NOT performed !');
            return redirect(route('DSpecial.maint_admin'));
        }

        $now = Carbon::now();
        $maint = DS_Maintenance::where('id', $request->id)->first();
        $aircraft = $maint->aircraft;

        if (empty($aircraft)) {
            flash()->error('Aircraft NOT Found... Maintenance NOT Performed !');
            return redirect(route('DSpecial.maint_admin'));
        }

        $maint->act_note = null;
        $maint->act_start = null;
        $maint->act_end = null;
        $maint->curr_state = 100;

        if ($request->act_note === 'C Check') {
            // Reset All
            $maint->cycle_c = 0;
            $maint->time_c = 0;
            $maint->last_c = $now;
            $maint->cycle_b = 0;
            $maint->time_b = 0;
            $maint->last_b = $now;
            $maint->cycle_a = 0;
            $maint->time_a = 0;
            $maint->last_a = $now;
        } else if ($request->act_note === 'B Check') {
            // Reset B and A
            $maint->cycle_b = 0;
            $maint->time_b = 0;
            $maint->last_b = $now;
            $maint->cycle_a = 0;
            $maint->time_a = 0;
            $maint->last_a = $now;
        } else if ($request->act_note === 'A Check') {
            // Reset A
            $maint->cycle_a = 0;
            $maint->time_a = 0;
            $maint->last_a = $now;
        }

        $aircraft->status = 'A';

        if ($request->ops === 'manual') {
            $maint_expense = app(Expense_Maintenance::class);
            $maint_expense->MaintenanceChecks($request->act_note, $aircraft, false, DS_Setting('turksim.maint_acstate_control', false));
            Log::debug('Disposable Maintenance, ' . $request->act_note . ' performed manually for ' . $aircraft->registration);
        } else {
            $maint->last_note = $request->act_note;
            $maint->last_time = $now;
        }

        $aircraft->save();
        $maint->save();

        flash()->success('Maintenance Performed');
        return redirect(route('DSpecial.maint_admin'));
    }
}
