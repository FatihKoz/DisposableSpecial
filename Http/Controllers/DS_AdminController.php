<?php

namespace Modules\DisposableSpecial\Http\Controllers;

use App\Contracts\Controller;
use App\Models\Aircraft;
use App\Models\Airport;
use App\Models\Bid;
use App\Models\Flight;
use App\Models\Pirep;
use App\Models\SimBrief;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request as Req;

class DS_AdminController extends Controller
{
    public function index(Request $request)
    {
        if (filled($request->input('action'))) {
            $this->AdminActions($request->input('action'), $request);
        }

        $settings = DB::table('disposable_settings')->where('key', 'LIKE', 'turksim.%')->orWhere('key', 'LIKE', 'phpvms.%')->get();

        $diversions = Pirep::where('state', 2)->where('notes', 'LIKE', '%DIVERTED%')
            ->whereNotNull('alt_airport_id')
            ->whereColumn('arr_airport_id', '!=', 'alt_airport_id')
            ->whereDate('submitted_at', '>=', Carbon::today()->subDays(7))
            ->orderby('submitted_at', 'desc')
            ->get();

        return view('DSpecial::admin.index', [
            'diversions' => $diversions,
            'settings'   => $settings,
        ]);
    }

    // Update Disposable Module settings
    public function update()
    {
        $formdata = Req::post();
        $section = null;
        foreach ($formdata as $id => $value) {
            if ($id === 'group') {
                $section = $value;
            }
            $setting = DB::table('disposable_settings')->where('id', $id)->first();
            if (!$setting) {
                continue;
            }
            Log::debug('Disposable Special, ' . $setting->group . ' setting for ' . $setting->name . ' changed to ' . $value);
            DB::table('disposable_settings')->where(['id' => $setting->id])->update(['value' => $value]);
        }

        flash()->success($section . ' settings saved');
        return redirect(route('DSpecial.admin'));
    }

    // Adjust fuel prices by percentage
    public function AdjustFuelPrice($percentage, $fuel = 1)
    {
        // Get proper field name per fuel type
        if ($fuel == 0) {
            $fuel_type = 'fuel_100ll_cost';
            $fuel_name = '100LL';
        } elseif ($fuel == 2) {
            $fuel_type = 'fuel_mogas_cost';
            $fuel_name = 'MOGAS';
        } else {
            $fuel_type = 'fuel_jeta_cost';
            $fuel_name = 'JET A-1';
        }

        $airports = Airport::where($fuel_type, '>', 0)->get();
        if ($airports->count() > 0) {
            if ($percentage != 100) {
                foreach ($airports as $airport) {
                    $airport->$fuel_type = round(($airport->$fuel_type * $percentage) / 100, 3);
                    $airport->save();
                }
                flash()->success('All ' . $fuel_name . ' prices updated (' . $airports->count() . ' airports affected)');
            } else {
                flash()->info('Nothing done! Prices remain same... Check your inputs');
            }
        } else {
            flash()->error('Nothing done! No Airports returned for selected fuel type');
        }
    }

    // Fix diversion
    public function FixDiversion($pirepid, $dest = null)
    {
        // Get Pirep
        $divpirep = Pirep::with(['aircraft', 'user'])->where('id', $pirepid)->first();

        if (!$divpirep) {
            flash()->error('Pirep Not Found !');
        } else {
            // Get Intended Destination
            $destination = !empty($dest) ? $dest : $divpirep->alt_airport_id;
            // Fix User Location
            $divuser = $divpirep->user;
            if ($divuser->curr_airport_id === $divpirep->arr_airport_id) {
                $divuser->curr_airport_id = $destination;
                $divuser->save();
            }
            // Fix Aircraft Location
            $divaircraft = $divpirep->aircraft;
            if ($divaircraft->airport_id === $divpirep->arr_airport_id) {
                $divaircraft->airport_id = $destination;
                $divaircraft->save();
            }
            // Fix Pirep
            if ($divpirep->arr_airport_id != $divpirep->alt_airport_id) {
                $divpirep->arr_airport_id = $destination;
                $divpirep->notes = null;
                $divpirep->save();
                flash()->success('Diversion Fixed');
            } else {
                flash()->info('Nothing Done... This is not a Diverted Pirep !');
            }
        }
    }

    public function AdminActions($action, Request $request)
    {
        if ($action === 'cleanbids') {
            // Clean Old Bids
            Bid::where('created_at', '<', Carbon::now()->subHours(24))->delete();
            flash()->success('Old bids deleted');
        } elseif ($action === 'cleansb') {
            // Clean Unused SimBrief Packs
            SimBrief::whereNull('pirep_id')->where('created_at', '<', Carbon::now()->subHours(3))->delete();
            flash()->success('Unused SimBrief packs deleted');
        } elseif ($action === 'cleansball') {
            // Clean ALL Unused SimBrief Packs
            SimBrief::whereNull('pirep_id')->delete();
            flash()->success('ALL Unused SimBrief packs deleted');
        } elseif ($action === 'dist') {
            // Calculate Distance for flights with no gc distance
            $flights = Flight::whereNull('distance')->orwhere('distance', 1)->get();
            foreach ($flights as $flight) {
                $flight->distance = DS_CalculateDistance($flight->dpt_airport_id, $flight->arr_airport_id);
                $flight->save();
            }
            flash()->success('Great Circle Distances Calculated.');
        } elseif ($action === 'distall') {
            // Calculate Distance for all flights
            $flights = Flight::get();
            foreach ($flights as $flight) {
                $flight->distance = DS_CalculateDistance($flight->dpt_airport_id, $flight->arr_airport_id);
                $flight->save();
            }
            flash()->success('All Great Circle Distances Re-Calculated.');
        } elseif ($action === 'fixdiversion') {
            // Fix Diversion
            $divp = $request->input('divp');
            $divd = $request->input('divd');
            if ($divp && $divd) {
                $this->FixDiversion($divp, $divd);
            } elseif ($divp) {
                $this->FixDiversion($divp);
            }
        } elseif ($action === 'ftime') {
            // Calculate Block Times for flights with no time defined
            $flights = Flight::whereNull('flight_time')->whereNotNull('distance')->orwhere('flight_time', 1)->whereNotNull('distance')->get();
            foreach ($flights as $flight) {
                $flight->flight_time = DS_CalculateBlockTime($flight->distance, 485, 39);
                $flight->save();
            }
            flash()->success('Flight Times Calculated.');
        } elseif ($action === 'ftimeall') {
            // Calculate Flight Time for all flights
            $flights = Flight::whereNotNull('distance')->get();
            foreach ($flights as $flight) {
                $flight->flight_time = DS_CalculateBlockTime($flight->distance, 485, 39);
                $flight->save();
            }
            flash()->success('All Flight Times Re-Calculated.');
        } elseif ($action === 'fuelprice') {
            // Update Fuel Prices
            $ft = $request->input('ft');
            $pct = $request->input('pct');
            if ($ft == '0' && $pct || $ft == '2' && $pct) {
                $this->AdjustFuelPrice($pct, $ft);
            } elseif ($pct) {
                $this->AdjustFuelPrice($pct);
            }
        } elseif ($action === 'returnbase') {
            // Return all aircrafts to their bases
            $aircrafts = Aircraft::where('landing_time', '<', Carbon::today()->subDays(7))->get();
            foreach ($aircrafts as $aircraft) {
                if ($aircraft->subfleet->hub_id && $aircraft->airport_id != $aircraft->subfleet->hub_id) {
                    $aircraft->airport_id = $aircraft->subfleet->hub_id;
                    $aircraft->save();
                }
            }
            flash()->success('Aircrafts Returned to their Hubs.');
        }
    }
}
