<?php

namespace Modules\DisposableSpecial\Http\Controllers;

use App\Contracts\Controller;
use App\Models\Enums\AircraftStatus;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Modules\DisposableSpecial\Listeners\Expense_Maintenance;
use Modules\DisposableSpecial\Models\DS_Maintenance;

class DS_MaintenanceController extends Controller
{
    public function index()
    {
        $active_maint = $this->PrepareCollection('active');
        $maintenance = $this->PrepareCollection();

        return view('DSpecial::maintenance.index', [
            'DBasic'      => check_module('DisposableBasic'),
            'activemaint' => $active_maint,
            'maintenance' => $maintenance,
            'staff_check' => Auth::user()->ability('admin', 'admin-access'),
        ]);
    }

    public function index_admin()
    {
        $active_maint = $this->PrepareCollection('active');
        $maintenance = $this->PrepareCollection();

        return view('DSpecial::admin.maintenance', [
            'activemaint' => $active_maint,
            'maintenance' => $maintenance,
        ]);
    }

    public function PrepareCollection($type = null)
    {
        if ($type === 'active') {
            // Ongoing Maintenance
            return DS_Maintenance::with('aircraft')->whereNotNull(['act_note', 'act_start', 'act_end'])->get();
        } else {
            // Maintenance Required Aircraft
            return DS_Maintenance::with('aircraft')->whereNull(['act_note', 'act_start', 'act_end'])
                ->where(function ($query) {
                    $query->where('curr_state', '<', 80)
                    ->orWhere('rem_ta', '<', 600)
                    ->orWhere('rem_tb', '<', 600)
                    ->orWhere('rem_tc', '<', 600)
                    ->orWhere('rem_ca', '<', 3)
                    ->orWhere('rem_cb', '<', 3)
                    ->orWhere('rem_cc', '<', 3);
                })->orderby('curr_state', 'asc')->paginate(20);
        }
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

        // Record current values as backup
        $maint->last_state = $maint->curr_state.','.$maint->cycle_a.','.$maint->cycle_b.','.$maint->cycle_c.','.$maint->time_a.','.$maint->time_b.','.$maint->time_c;

        $maint->act_note = null;
        $maint->act_start = null;
        $maint->act_end = null;
        $maint->curr_state = 100;

        if ($request->act_note === 'C Check') {
            // Reset All
            $maint->cycle_c = 0;
            $maint->time_c = 0;
            $maint->last_c = $now;
            $maint->rem_cc = $maint->limits->cycle_c - $maint->cycle_c;
            $maint->rem_tc = $maint->limits->time_c - $maint->time_c;
            $maint->cycle_b = 0;
            $maint->time_b = 0;
            $maint->last_b = $now;
            $maint->rem_cb = $maint->limits->cycle_b - $maint->cycle_b;
            $maint->rem_tb = $maint->limits->time_b - $maint->time_b;
            $maint->cycle_a = 0;
            $maint->time_a = 0;
            $maint->last_a = $now;
            $maint->rem_ca = $maint->limits->cycle_a - $maint->cycle_a;
            $maint->rem_ta = $maint->limits->time_a - $maint->time_a;
        } elseif ($request->act_note === 'B Check') {
            // Reset B and A
            $maint->cycle_b = 0;
            $maint->time_b = 0;
            $maint->last_b = $now;
            $maint->rem_cb = $maint->limits->cycle_b - $maint->cycle_b;
            $maint->rem_tb = $maint->limits->time_b - $maint->time_b;
            $maint->cycle_a = 0;
            $maint->time_a = 0;
            $maint->last_a = $now;
            $maint->rem_ca = $maint->limits->cycle_a - $maint->cycle_a;
            $maint->rem_ta = $maint->limits->time_a - $maint->time_a;
        } elseif ($request->act_note === 'A Check') {
            // Reset A
            $maint->cycle_a = 0;
            $maint->time_a = 0;
            $maint->last_a = $now;
            $maint->rem_ca = $maint->limits->cycle_a - $maint->cycle_a;
            $maint->rem_ta = $maint->limits->time_a - $maint->time_a;
        }

        $aircraft->status = AircraftStatus::ACTIVE;

        if ($request->ops === 'manual') {
            $maint_expense = app(Expense_Maintenance::class);
            $maint_expense->MaintenanceChecks($request->act_note, $aircraft, false, DS_Setting('turksim.maint_acstate_control', false));
            Log::debug('Disposable Maintenance, '.$request->act_note.' performed manually for '.$aircraft->registration);
            flash()->success('Maintenance Started');
        } else {
            $maint->last_note = $request->act_note;
            $maint->last_time = $now;
            flash()->success('Maintenance Performed');
        }

        $aircraft->save();
        $maint->save();

        return back();
    }
}
