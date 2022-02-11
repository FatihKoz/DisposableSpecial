<?php

namespace Modules\DisposableSpecial\Http\Controllers;

use App\Contracts\Controller;
use App\Models\Aircraft;
use App\Models\Airport;
use App\Models\Bid;
use App\Models\Flight;
use App\Models\Pirep;
use App\Models\SimBrief;
use App\Models\Subfleet;
use App\Models\Enums\PirepState;
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
            return redirect()->route('DSpecial.admin');
        }

        $settings = DB::table('disposable_settings')->where('key', 'LIKE', 'turksim.%')->orWhere('key', 'LIKE', 'phpvms.%')->get();

        $diversions = Pirep::withCount('alt_airport')->where('state', 2)->where('notes', 'LIKE', '%DIVERTED%')
            ->whereNotNull('alt_airport_id')
            ->whereColumn('arr_airport_id', '!=', 'alt_airport_id')
            ->whereDate('submitted_at', '>=', Carbon::today()->subDays(7))
            ->having('alt_airport_count', 1)
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
            $fix_destination = !empty($dest) ? $dest : $divpirep->alt_airport_id;
            // Fix User Location
            $divuser = $divpirep->user;
            if ($divuser->curr_airport_id === $divpirep->arr_airport_id) {
                $divuser->curr_airport_id = $fix_destination;
                $divuser->save();
            }
            // Fix Aircraft Location
            $divaircraft = $divpirep->aircraft;
            if ($divaircraft->airport_id === $divpirep->arr_airport_id) {
                $divaircraft->airport_id = $fix_destination;
                $divaircraft->save();
            }
            // Fix Pirep
            if ($divpirep->arr_airport_id != $divpirep->alt_airport_id) {
                $divpirep->arr_airport_id = $fix_destination;
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
        } elseif ($action === 'fixpsb') {
            // Clean "active" looking but not properly handled SimBrief Packs
            $active_pireps = DB::table('pireps')->where('state', PirepState::IN_PROGRESS)->orWhere('state', PirepState::PAUSED)->pluck('id')->toArray();
            $sb_packs = SimBrief::whereNotNull('flight_id')->whereNotNull('pirep_id')->whereNotIn('pirep_id', $active_pireps)->get();
            if (filled($sb_packs)) {
                foreach ($sb_packs as $sb) {
                    $sb->flight_id = null;
                    $sb->save();
                }
                flash()->success('Problematic SimBrief packs fixed');
            } else {
                flash()->info('No problematic SimBrief packs found, nothing done');
            }
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
            // Return all aircraft to their bases
            $aircraft = Aircraft::with('subfleet')->where('landing_time', '<', Carbon::today()->subDays(7))->get();
            foreach ($aircraft as $ac) {
                if ($ac->hub_id && $ac->airport_id != $ac->hub_id) {
                    $ac->airport_id = $ac->hub_id;
                    $ac->save();
                } elseif (!$ac->hub_id && $ac->subfleet->hub_id && $ac->airport_id != $ac->subfleet->hub_id) {
                    $ac->airport_id = $ac->subfleet->hub_id;
                    $ac->save();
                }
            }
            flash()->success('Fleet members returned to their hubs.');
        } elseif ($action === 'afs') {
            // Assign Fleets to flights, by range and airline
            if ($request->input('range') == 'short') {
                $oper = 'short';
                $distance = [0, 2500];
                $multiplier = [0, 110];
            } elseif ($request->input('range') == 'medium') {
                $oper = 'medium';
                $distance = [2501, 4000];
                $multiplier = [101, 119];
            } elseif ($request->input('range') == 'long') {
                $oper = 'long';
                $distance = [4001, 10000];
                $multiplier = [120, 200];
            } else {
                flash()->error('Range not defined');

                return;
            }

            if (filled($request->input('airline')) && is_numeric($request->input('airline'))) {
                $sal = $request->input('airline');
            } else {
                flash()->error('Airline not defined');

                return;
            }

            if (isset($oper) && isset($sal)) {
                // Flight Where
                $where = [];
                $where['airline_id'] = $sal;
                $where[] = ['flight_type', '!=', 'E'];
                // SubFleet Where
                $where_sf = [];
                $where_sf['airline_id'] = $sal;

                $selected_flights = Flight::where($where)->whereBetween('distance', $distance)->pluck('id')->toArray();
                $selected_subfleets = Subfleet::where($where_sf)->whereBetween('ground_handling_multiplier', $multiplier)->pluck('id')->toArray();

                if (blank($selected_flights) || blank($selected_subfleets)) {
                    flash()->error('No flights and/or Subfleets found');

                    return;
                }

                $count_inserted = 0;
                $count_skipped = 0;
                foreach ($selected_flights as $fl) {
                    foreach ($selected_subfleets as $sf) {
                        if (DB::table('flight_subfleet')->where(['flight_id' => $fl, 'subfleet_id' => $sf])->count() == 0) {
                            DB::table('flight_subfleet')->insert(['flight_id' => $fl, 'subfleet_id' => $sf]);
                            $count_inserted++;
                        } else {
                            $count_skipped++;
                        }
                    }
                }

                if (is_countable($selected_flights) && is_countable($selected_subfleets)) {
                    flash()->success(count($selected_subfleets) . ' subfleets and ' . count($selected_flights) . ' flights processed. Inserted ' . $count_inserted . ', skipped ' . $count_skipped . ' records');
                }
            }
        }
    }
}
