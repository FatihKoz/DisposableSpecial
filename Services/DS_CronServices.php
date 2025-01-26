<?php

namespace Modules\DisposableSpecial\Services;

use App\Events\PirepCancelled;
use App\Models\Acars;
use App\Models\Aircraft;
use App\Models\Bid;
use App\Models\Enums\AcarsType;
use App\Models\Enums\AircraftState;
use App\Models\Enums\PirepState;
use App\Models\Enums\PirepStatus;
use App\Models\Fare;
use App\Models\Flight;
use App\Models\Pirep;
use App\Models\Rank;
use App\Models\Role;
use App\Models\SimBrief;
use App\Models\Subfleet;
use App\Models\User;
use App\Models\UserFieldValue;
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
            Log::info('Disposable Special | Fixed '.$briefs->count().' SimBrief packs with no matching PIREPs');
        }
    }

    // Delete Expired SimBrief Packs
    public function DeleteExpiredSimBrief()
    {
        $expire_hours = setting('simbrief.expire_hours', 6);
        $expire_time = Carbon::now('UTC')->subHours($expire_hours);

        $deleted_ofps = SimBrief::whereNull('pirep_id')->where('created_at', '<=', $expire_time)->delete();
        if ($deleted_ofps > 0) {
            Log::info('Disposable Special | Deleted '.$deleted_ofps.' expired SimBrief OFP Packs');
        }
    }

    // Process Free Flights
    // Deactivate them and hide if not
    public function ProcessFreeFlights()
    {
        $ffs = Flight::whereNotNull('user_id')->where('route_code', 'PF')->where(function ($query) {
            $query->where('active', 1)->orWhere('visible', 1);
        })->get();

        if (filled($ffs) && $ffs->count() > 0) {
            foreach ($ffs as $ff) {
                $ff->active = 0;
                $ff->visible = 0;
                $ff->save();
            }
            Log::info('Disposable Special | '.$ffs->count().' Free Flights processed and updated as inactive and invisible');
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
            Log::info('Disposable Special | '.$aircraft->registration.' state changed to PARKED');
        }
    }

    // Return aircraft to their bases
    public function RebaseParkedAircraft($days = 0)
    {
        if ($days > 0) {
            // Return aircraft to their bases if landed n days before cron runtime
            $today = Carbon::now();
            $margin = Carbon::now()->subDays($days);
            $aircraft = Aircraft::with('subfleet')->where('landing_time', '<', $margin)->get();

            foreach ($aircraft as $ac) {
                // Don't rebase the aircraft if it is recently updated (moved manually)
                if ($ac->updated_at->diffInDays($today) < 4) {
                    Log::info('Disposable Special | '.$ac->ident.' not returned to base, manual placement protection');
                    continue;
                }

                if ($ac->hub_id && $ac->airport_id != $ac->hub_id) {
                    $ac->airport_id = $ac->hub_id;
                    $ac->save();
                    Log::info('Disposable Special | '.$ac->ident.' returned to '.$ac->hub_id);
                } elseif (!$ac->hub_id && $ac->subfleet->hub_id && $ac->airport_id != $ac->subfleet->hub_id) {
                    $ac->airport_id = $ac->subfleet->hub_id;
                    $ac->save();
                    Log::info('Disposable Special | '.$ac->ident.' returned to '.$ac->subfleet->hub_id);
                }
            }
        }
    }

    // Set Module Ownership of Tour Flights
    public function OwnTourFlights()
    {
        $tours = DS_Tour::select('id', 'tour_code')->get();

        foreach ($tours as $tour) {
            $flights = Flight::where('route_code', $tour->tour_code)->update(['owner_id' => $tour->id, 'owner_type' => 'DS_Tour']);
            if (filled($flights) && $flights > 0) {
                Log::info('Disposable Special | '.$flights.' Tour legs processed and owned by '.$tour->tour_code);
            }
        }
    }

    // Handle Tour Flights, Activate or Deactivate according to Tour Dates
    // Process only flights with no dates set, rest will be handled by phpVMS
    public function ProcessTours()
    {
        $today = Carbon::now();
        $tomorrow = Carbon::now()->addDays(1);

        // Get Tour Codes
        $activate = DS_Tour::whereDate('start_date', $tomorrow)->pluck('tour_code')->toArray();
        $deactivate = DS_Tour::whereDate('end_date', '<', $today)->orWhereDate('start_date', '>', $tomorrow)->pluck('tour_code')->toArray();
        $keephidden = DS_Tour::whereDate('start_date', '<=', $today)->orWhereDate('end_date', '>=', $tomorrow)->pluck('tour_code')->toArray();

        if (filled($keephidden) && count($keephidden) > 0 && DS_Setting('dspecial.keep_tf_invisible', false) == true) {
            $flights = Flight::whereIn('route_code', $keephidden)->get();

            if (filled($flights) && $flights->count() > 0) {
                foreach ($flights as $flight) {
                    $flight->visible = 0;
                    $flight->save();
                }
                Log::info('Disposable Special | Processed '.count($keephidden).' Tours and hidden '.$flights->count().' flights');
            } else {
                Log::info('Disposable Special | No Tours Flights Found for Hiding');
            }
        }

        if (filled($activate) && count($activate) > 0) {
            $flights = Flight::whereIn('route_code', $activate)->whereNull('start_date')->whereNull('end_date')->get();

            if (filled($flights) && $flights->count() > 0) {
                foreach ($flights as $flight) {
                    $flight->active = 1;
                    $flight->visible = (DS_Setting('dspecial.keep_tf_invisible', false)) ? 0 : 1;
                    $flight->save();
                }
                Log::info('Disposable Special | Processed '.count($activate).' Tours and activated '.$flights->count().' flights');
            } else {
                Log::info('Disposable Special | No Tours Flights Found for Activation');
            }
        }

        if (filled($deactivate) && count($deactivate) > 0) {
            $flights = Flight::whereIn('route_code', $deactivate)->whereNull('start_date')->whereNull('end_date')->get();

            if (filled($flights) && $flights->count() > 0) {
                foreach ($flights as $flight) {
                    $flight->active = 0;
                    $flight->visible = 0;
                    $flight->save();
                }
                Log::info('Disposable Special | Processed '.count($deactivate).' Tours and deactivated '.$flights->count().' flights');
            } else {
                Log::info('Disposable Special | No Tours Flights Found for De-Activation');
            }
        }
    }

    // Check Acars Table for Log Errors
    // Sometimes vmsAcars sends the same log entries back to phpVMS
    // Causing a terrible slowdown during pirep processing
    // This method is designed to avoid/fix such rare cases
    public function CheckAcarsLogs()
    {
        $acars_logs = Acars::selectRaw('pirep_id, count(*) as log_counts')->where('type', AcarsType::LOG)
            ->groupBy('pirep_id')->orderBy('log_counts', 'DESC')->having('log_counts', '>', 500)->get();

        foreach ($acars_logs as $acars_log) {
            Acars::where(['pirep_id' => $acars_log->pirep_id, 'type' => AcarsType::LOG])->delete();
            Log::info('Disposable Special | Deleted '.$acars_log->log_counts.' log entries for PIREP '.$acars_log->pirep_id.' | acars');
        }
    }

    // Delete old Acars Position Reports
    // No route and/or flight log entries, only position reports to reduce db load
    public function DeleteOldAcars($days = 0)
    {
        if ($days > 0) {
            $acars = Acars::where('type', AcarsType::FLIGHT_PATH)->where('created_at', '<', Carbon::now()->subDays($days))->delete();
            if ($acars > 0) {
                Log::info('Disposable Special | Deleted '.$acars.' position report records | acars');
            }
        }
    }

    // Delete old SimBrief OFP packs
    public function DeleteOldSimBrief($days = 0)
    {
        if ($days > 0) {
            $simbrief = SimBrief::where('created_at', '<', Carbon::now()->subDays($days))->delete();
            if ($simbrief > 0) {
                Log::info('Disposable Special | Deleted '.$simbrief.' OFP packs | simbrief');
            }
        }
    }

    // Delete Not Flown Members
    // Protect members with roles (like special members and/or non flying va staff)
    public function DeleteNonFlownMembers($days = 0)
    {
        if ($days > 0) {
            // Get members with pireps and staff
            $users_with_pireps = Pirep::select('user_id')->groupBy('user_id')->pluck('user_id')->toArray();
            $users_with_roles = DB::table('role_user')->select('user_id')->groupBy('user_id')->pluck('user_id')->toArray();
            $safe_users = array_unique(array_merge($users_with_pireps, $users_with_roles));
            $picked_users = User::where('created_at', '<', Carbon::now()->subDays($days))->whereNotIn('id', $safe_users)->get();

            // Delete each user, remove their bids and user field entries
            if ($picked_users) {
                Log::info('Disposable Special | Deleting '.$picked_users->count().' non-flown members | users');
                foreach ($picked_users as $user) {
                    Log::info('Disposable Special | Deleted user, roles, bids, custom field values for id:'.$user->id.' > '.$user->name_private.' identified as non-flown user');
                    // Detach all roles from the user
                    $user->removeRoles($user->roles->toArray());
                    // Delete any custom profile fields
                    UserFieldValue::where('user_id', $user->id)->delete();
                    // Remove any bids
                    Bid::where('user_id', $user->id)->delete();
                    // Remove the user
                    $user->forceDelete();
                }
            } else {
                Log::info('Disposable Special | No non-flown members found | users');
            }
        }
    }

    // Delete Long Term Paused Pireps
    // Sometimes some people don't understand the usage of short term pause
    public function DeletePausedPireps($hours = 0)
    {
        if ($hours > 0) {
            $date = Carbon::now('UTC')->subHours($hours);

            $where = [];
            $where['state'] = PirepState::IN_PROGRESS;
            $where['status'] = PirepStatus::PAUSED;

            $pireps = Pirep::where('updated_at', '<', $date)->where($where)->get();

            foreach ($pireps as $pirep) {
                event(new PirepCancelled($pirep));
                Log::info('Disposable Special | Cancelled Pirep id='.$pirep->id.', state='.PirepState::label($pirep->state));
                $pirep->delete();
            }
        }
    }

    // Clean out Acars Table
    public function CleanAcarsRecords()
    {
        $records = Acars::withCount(['pirep'])->having('pirep_count', 0)->pluck('id')->toArray();

        $acars = Acars::whereIn('id', $records)->delete();

        if ($acars > 0) {
            Log::info('Disposable Special | Deleted '.$acars.' redundant records with no matching PIREP | acars');
        }
    }

    // Clean out Redundant Relationships
    // If one of the foreing keys is missing record becomes redundant
    public function CleanRelationships()
    {
        $fares = Fare::pluck('id')->toArray();
        $flights = Flight::pluck('id')->toArray();
        $ranks = Rank::pluck('id')->toArray();
        $roles = Role::pluck('id')->toArray();
        $subfleets = Subfleet::pluck('id')->toArray();
        $users = User::pluck('id')->toArray();

        $ff_no_flight = DB::table('flight_fare')->whereNotIn('flight_id', $flights)->delete();
        if ($ff_no_flight > 0) {
            Log::info('Disposable Special | Deleted '.$ff_no_flight.' redundant records with no matching FLIGHT | flight_fare');
        }

        $ff_no_fare = DB::table('flight_fare')->whereNotIn('fare_id', $fares)->delete();
        if ($ff_no_fare > 0) {
            Log::info('Disposable Special | Deleted '.$ff_no_fare.' redundant records with no matching FARE | flight_fare');
        }

        $sf_no_subfleet = DB::table('subfleet_fare')->whereNotIn('subfleet_id', $subfleets)->delete();
        if ($sf_no_subfleet > 0) {
            Log::info('Disposable Special | Deleted '.$sf_no_subfleet.' redundant records with no matching SUBFLEET | subfleet_fare');
        }

        $sf_no_fare = DB::table('subfleet_fare')->whereNotIn('fare_id', $fares)->delete();
        if ($sf_no_fare > 0) {
            Log::info('Disposable Special | Deleted '.$sf_no_fare.' redundant records with no matching FARE | subfleet_fare');
        }

        $fs_no_flight = DB::table('flight_subfleet')->whereNotIn('flight_id', $flights)->delete();
        if ($fs_no_flight > 0) {
            Log::info('Disposable Special | Deleted '.$fs_no_flight.' redundant records with no matching FLIGHT | flight_subfleets');
        }

        $fs_no_subfleet = DB::table('flight_subfleet')->whereNotIn('subfleet_id', $subfleets)->delete();
        if ($fs_no_subfleet > 0) {
            Log::info('Disposable Special | Deleted '.$fs_no_subfleet.' redundant records with no matching SUBFLEET | flight_subfleets');
        }

        $sr_no_rank = DB::table('subfleet_rank')->whereNotIn('rank_id', $ranks)->delete();
        if ($sr_no_rank > 0) {
            Log::info('Disposable Special | Deleted '.$sr_no_rank.' redundant records with no matching RANK | subfleet_rank');
        }

        $sr_no_subfleet = DB::table('subfleet_rank')->whereNotIn('subfleet_id', $subfleets)->delete();
        if ($sr_no_subfleet > 0) {
            Log::info('Disposable Special | Deleted '.$sr_no_subfleet.' redundant records with no matching SUBFLEET | subfleet_rank');
        }

        $user_field_values = DB::table('user_field_values')->whereNotIn('user_id', $users)->delete();
        if ($user_field_values > 0) {
            Log::info('Disposable Special | Deleted '.$user_field_values.' redundant records with no matching USER | user_field_values');
        }

        $journals = DB::table('journals')->where('morphed_type', 'LIKE', '%User')->whereNotIn('morphed_id', $users)->delete();
        if ($journals > 0) {
            Log::info('Disposable Special | Deleted '.$journals.' redundant records with no matching USER | journals');
        }

        $ur_users = DB::table('role_user')->whereNotIn('user_id', $users)->delete();
        if ($journals > 0) {
            Log::info('Disposable Special | Deleted '.$ur_users.' redundant records with no matching USER | role_user');
        }

        $ur_roles = DB::table('role_user')->whereNotIn('role_id', $roles)->delete();
        if ($journals > 0) {
            Log::info('Disposable Special | Deleted '.$ur_roles.' redundant records with no matching ROLE | role_user');
        }
    }
}
