<?php

namespace Modules\DisposableSpecial\Services;

use App\Models\Acars;
use App\Models\Aircraft;
use App\Models\Fare;
use App\Models\Flight;
use App\Models\Pirep;
use App\Models\Rank;
use App\Models\SimBrief;
use App\Models\Subfleet;
use App\Models\Enums\AircraftState;
use App\Models\Enums\PirepState;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\DisposableSpecial\Models\DS_Tour;

class DS_CronServices
{
    // Fix Broken SimBrief Packs
    public function FixBrokenSimBrief()
    {
        $briefs = SimBrief::withCount(['pirep'])->whereNotNull('pirep_id')->whereNotNull('flight_id')->having('pirep_count', 0)->get();

        if (filled($briefs) && $briefs->count() > 0) {
            // Drop Pirep relationship
            foreach ($briefs as $brief) {
                $brief->pirep_id = null;
                $brief->save();
            }
            Log::info('Disposable Special | Fixed ' . $briefs->count() . ' SimBrief packs with no matching PIREPs');
        }
    }

    // Delete Expired SimBrief Packs
    public function DeleteExpiredSimBrief()
    {
        $expire_hours = setting('simbrief.expire_hours', 6);
        $expire_time = Carbon::now('UTC')->subHours($expire_hours);

        $deleted_ofps = SimBrief::whereNull('pirep_id')->where('created_at', '<=', $expire_time)->delete();
        if ($deleted_ofps > 0) {
            Log::info('Disposable Special | Deleted ' . $deleted_ofps . ' expired SimBrief OFP Packs');
        }
    }

    // Process Free Flights
    // Deactivate them and hide if not
    public function ProcessFreeFlights()
    {
        $ffs = Flight::where('flight_type', 'E')->where(function ($query) {
            $query->where('active', 1)->orWhere('visible', 1);
        })->get();

        if (filled($ffs) && $ffs->count() > 0) {
            foreach ($ffs as $ff) {
                $ff->active = 0;
                $ff->visible = 0;
                $ff->save();
            }
            Log::info('Disposable Special | ' . $ffs->count() . ' Free Flights processed and updated as inactive and invisible');
        }
    }

    // Release Stuck Aircraft ("in use" or "in air" without an active pirep)
    public function ReleaseStuckAircraft()
    {
        $active_pireps = Pirep::where('state', PirepState::IN_PROGRESS)->orWhere('state', PirepState::PAUSED)->pluck('aircraft_id')->toArray();
        $active_aircraft = Aircraft::where('state', '!=', AircraftState::PARKED)->whereNotIn('id', $active_pireps)->get();
        // Park them with a log entry for each
        foreach ($active_aircraft as $aircraft) {
            $aircraft->state = AircraftState::PARKED;
            $aircraft->save();
            Log::info('Disposable Special | ' . $aircraft->registration . ' state changed to PARKED');
        }
    }

    // Handle Tour Flights, Activate or Deactivate according to Tour Dates
    // Process only flights with no dates set, rest will be handled by phpVMS
    public function ProcessTours()
    {
        $today = Carbon::now();
        $tomorrow = Carbon::now()->addDays(1);
        // Activate
        $tours = DS_Tour::whereDate('start_date', $tomorrow)->pluck('tour_code')->toArray();

        if (empty($tours)) {
            return;
        }

        $flights = Flight::whereIn('route_code', $tours)->whereNull('start_date')->whereNull('end_date')->where('active', 0)->get();

        if (filled($flights) && $flights->count() > 0) {
            foreach ($flights as $flight) {
                $flight->active = 1;
                $flight->visible = 1;
                $flight->save();
            }
            Log::info('Disposable Special | Processed ' . count($tours) . ' Tours and activated ' . $flights->count() . ' flights');
        }
        // Deactivate
        $tours = DS_Tour::whereDate('end_date', '<', $today)->orWhereDate('start_date', '>', $tomorrow)->pluck('tour_code')->toArray();

        if (empty($tours)) {
            return;
        }

        $flights = Flight::whereIn('route_code', $tours)->whereNull('start_date')->whereNull('end_date')->where('active', 1)->get();

        if (filled($flights) && $flights->count() > 0) {
            foreach ($flights as $flight) {
                $flight->active = 0;
                $flight->visible = 0;
                $flight->save();
            }
            Log::info('Disposable Special | Processed ' . count($tours) . ' Tours and deactivated ' . $flights->count() . ' flights');
        }
    }

    // Check Acars Table for Log Errors
    // Sometimes vmsAcars sends the same log entries back to phpVMS
    // Causing a terrible slowdown during pirep processing
    // This method is designed to avoid/fix such rare cases
    public function CheckAcarsLogs()
    {
        $acars_logs = DB::table('acars')->selectRaw('pirep_id, count(*) as log_counts')->where('type', 2)
            ->groupBy('pirep_id')->orderBy('log_counts', 'DESC')->having('log_counts', '>', 500)->get();

        foreach ($acars_logs as $acars_log) {
            DB::table('acars')->where(['pirep_id' => $acars_log->pirep_id, 'type' => 2])->delete();
            Log::info('Disposable Special | Deleted ' . $acars_log->log_counts . ' log entries for PIREP ' . $acars_log->pirep_id . ' | acars');
        }
    }

    // Delete old Acars Position Reports
    // No route and/or flight log entries, only position reports to reduce db load
    public function DeleteOldAcars($days = 0)
    {
        if ($days > 0) {
            $acars = DB::table('acars')->where('type', 0)->where('created_at', '<', Carbon::now()->subDays($days))->delete();
            if ($acars > 0) {
                Log::info('Disposable Special | Deleted ' . $acars . ' position report records | acars');
            }
        }
    }

    // Delete old SimBrief OFP packs
    public function DeleteOldSimBrief($days = 0)
    {
        if ($days > 0) {
            $simbrief = DB::table('simbrief')->where('created_at', '<', Carbon::now()->subDays($days))->delete();
            if ($simbrief > 0) {
                Log::info('Disposable Special | Deleted ' . $simbrief . ' OFP packs | simbrief');
            }
        }
    }

    // Delete Not Flown Members
    // Protect members with roles (like special members and/or non flying va staff)
    public function DeleteNonFlownMembers($days = 0)
    {
        if ($days > 0) {
            // Get members with pireps and staff
            $users_with_pireps = DB::table('pireps')->select('user_id')->groupBy('user_id')->pluck('user_id')->toArray();
            $users_with_roles = DB::table('role_user')->select('user_id')->groupBy('user_id')->pluck('user_id')->toArray();
            $safe_users = array_unique(array_merge($users_with_pireps, $users_with_roles));
            // Delete the rest
            $deleted_users = DB::table('users')->where('created_at', '<', Carbon::now()->subDays($days))->whereNotIn('id', $safe_users)->delete();
            if ($deleted_users > 0) {
                Log::info('Disposable Special | Deleted ' . $deleted_users . ' non-flown members | users');
            }
        }
    }

    // Clean out Acars Table
    public function CleanAcarsRecords()
    {
        $records = Acars::withCount(['pirep'])->having('pirep_count', 0)->pluck('id')->toArray();

        $acars = DB::table('acars')->whereIn('id', $records)->delete();
        if ($acars > 0) {
            Log::info('Disposable Special | Deleted ' . $acars . ' redundant records with no matching PIREP | acars');
        }
    }

    // Clean out Redundant Relationships
    // If one of the foreing keys is missing record becomes redundant
    public function CleanRelationships()
    {
        $fares = Fare::pluck('id')->toArray();
        $flights = Flight::pluck('id')->toArray();
        $ranks = Rank::pluck('id')->toArray();
        $subfleets = Subfleet::pluck('id')->toArray();

        $ff_no_flight = DB::table('flight_fare')->whereNotIn('flight_id', $flights)->delete();
        if ($ff_no_flight > 0) {
            Log::info('Disposable Special | Deleted ' . $ff_no_flight . ' redundant records with no matching FLIGHT | flight_fare');
        }

        $ff_no_fare = DB::table('flight_fare')->whereNotIn('fare_id', $fares)->delete();
        if ($ff_no_fare > 0) {
            Log::info('Disposable Special | Deleted ' . $ff_no_fare . ' redundant records with no matching FARE | flight_fare');
        }

        $sf_no_subfleet = DB::table('subfleet_fare')->whereNotIn('subfleet_id', $subfleets)->delete();
        if ($sf_no_subfleet > 0) {
            Log::info('Disposable Special | Deleted ' . $sf_no_subfleet . ' redundant records with no matching SUBFLEET | subfleet_fare');
        }

        $sf_no_fare = DB::table('subfleet_fare')->whereNotIn('fare_id', $fares)->delete();
        if ($sf_no_fare > 0) {
            Log::info('Disposable Special | Deleted ' . $sf_no_fare . ' redundant records with no matching FARE | subfleet_fare');
        }

        $fs_no_flight = DB::table('flight_subfleet')->whereNotIn('flight_id', $flights)->delete();
        if ($fs_no_flight > 0) {
            Log::info('Disposable Special | Deleted ' . $fs_no_flight . ' redundant records with no matching FLIGHT | flight_subfleets');
        }

        $fs_no_subfleet = DB::table('flight_subfleet')->whereNotIn('subfleet_id', $subfleets)->delete();
        if ($fs_no_subfleet > 0) {
            Log::info('Disposable Special | Deleted ' . $fs_no_subfleet . ' redundant records with no matching SUBFLEET | flight_subfleets');
        }

        $sr_no_rank = DB::table('subfleet_rank')->whereNotIn('rank_id', $ranks)->delete();
        if ($sr_no_rank > 0) {
            Log::info('Disposable Special | Deleted ' . $sr_no_rank . ' redundant records with no matching RANK | subfleet_rank');
        }

        $sr_no_subfleet = DB::table('subfleet_rank')->whereNotIn('subfleet_id', $subfleets)->delete();
        if ($sr_no_subfleet > 0) {
            Log::info('Disposable Special | Deleted ' . $sr_no_subfleet . ' redundant records with no matching SUBFLEET | subfleet_rank');
        }
    }
}
