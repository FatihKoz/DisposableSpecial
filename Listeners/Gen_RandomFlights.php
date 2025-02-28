<?php

namespace Modules\DisposableSpecial\Listeners;

use App\Events\PirepAccepted;
use App\Services\FinanceService;
use App\Support\Money;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Modules\DisposableBasic\Models\DB_RandomFlight;
use Modules\DisposableSpecial\Models\DS_Assignment;
use Modules\DisposableSpecial\Models\DS_Mission;

class Gen_RandomFlights
{
    // Reward Pilot if a "Random Flight", "Mission Flight" or "Monthly Flight Assignment" is flown
    public function handle(PirepAccepted $event)
    {
        $reward_rf = DS_Setting('turksim.randomflights_reward', false);
        $reward_mfa = DS_Setting('turksim.assignments_reward', false);
        $reward_mis = DS_Setting('turksim.missions_reward', false);
        $DBasic = check_module('DisposableBasic');

        if (!$reward_rf && !$reward_mfa && !$reward_mis) {
            return;
        }

        $pirep = $event->pirep;
        $pirep->loadMissing('user.rank', 'user.journal', 'flight.airline');

        $where = [];
        $where['user_id'] = $pirep->user_id;
        $where['flight_id'] = $pirep->flight_id;

        // Random Flights
        if ($DBasic && $reward_rf) {
            $random_flight = DB_RandomFlight::where($where)->whereNull('pirep_id')->whereDate('assign_date', $pirep->submitted_at)->first();

            if ($random_flight) {
                $multiplier = DS_Setting('turksim.randomflights_multiplier', 10);
                $this->RewardUser($pirep, $multiplier, 'Random Flights');
                // Update Random Flight record to prevent further rewards
                $random_flight->pirep_id = $pirep->id;
                $random_flight->save();
            }
        }

        // Mission Flights
        if ($reward_mis) {
            // Add Aircraft to the search criteria
            $where['aircraft_id'] = $pirep->aircraft_id;
            // Create an OR clause for the search criteria
            $orWhere = [];
            $orWhere['user_id'] = $pirep->user_id;
            $orWhere['aircraft_id'] = $pirep->aircraft_id;
            $orWhere['dpt_airport_id'] = $pirep->dpt_airport_id;
            $orWhere['arr_airport_id'] = $pirep->arr_airport_id;

            $mission_flight = DS_Mission::where(function ($query) use ($where, $pirep) {
                $query->whereNull('pirep_id')->where($where)->where('mission_valid', '>', $pirep->submitted_at);
            })->orWhere(function ($query) use ($orWhere, $pirep) {
                $query->whereNull('pirep_id')->where($orWhere)->where('mission_valid', '>', $pirep->submitted_at);
            })->first();

            if ($mission_flight) {
                $multiplier = DS_Setting('turksim.missions_multiplier', 10);
                $this->RewardUser($pirep, $multiplier, 'Mission Flights');
                // Update Mission Flight record to prevent further rewards
                $mission_flight->pirep_id = $pirep->id;
                $mission_flight->save();
            }

            // Remove Aircraft from the search criteria
            unset($where['aircraft_id']);
        }

        // Monthly Flight Assignments
        $pirep_date = Carbon::parse($pirep->submitted_at);

        $where['assignment_year'] = $pirep_date->year;
        $where['assignment_month'] = $pirep_date->month;

        if ($reward_mfa) {
            $assignment = DS_Assignment::where($where)->whereNull('pirep_id')->first();

            if ($assignment) {
                $multiplier = DS_Setting('turksim.assignments_multiplier', 10);
                $this->RewardUser($pirep, $multiplier, 'Monthly Flight Assignments');
                // Update Assignment record to prevent further rewards
                $assignment->pirep_id = $pirep->id;
                $assignment->pirep_date = Carbon::now();
                $assignment->save();
            }
        }
    }

    public function RewardUser($pirep, $multiplier, $group)
    {
        $user = $pirep->user;
        $airline = $pirep->flight->airline;
        $today = Carbon::now()->format('Y-m-d');
        // Define Reward amount by base Rank payment
        $base_rate = ($pirep->source === 0) ? $user->rank->manual_base_pay_rate : $user->rank->acars_base_pay_rate;
        // Or by the Flight's Pilot Pay
        if (is_numeric(optional($pirep->flight)->pilot_pay)) {
            $base_rate = $pirep->flight->pilot_pay;
        }
        // Calculate the Reward amount with failsafe
        $amount = is_numeric($base_rate) ? round($base_rate * $multiplier, 2) : 5000;
        $amount = Money::createFromAmount($amount);

        $financeSvc = app(FinanceService::class);
        // Credit User
        $financeSvc->creditToJournal(
            $user->journal,
            $amount,
            $user,
            'Flight Reward (Pirep: '.$pirep->id.')',
            $group,
            'rewards',
            $today
        );
        // Debit Airline
        $financeSvc->debitFromJournal(
            $airline->journal,
            $amount,
            $user,
            'Flight Reward ('.$user->name_private.' Pirep: '.$pirep->id.')',
            $group,
            'rewards',
            $today
        );

        // Ammend Log
        Log::debug('Disposable Special | '.$user->name_private.' rewarded '.$amount.' for '.$group.' completion');
    }
}
