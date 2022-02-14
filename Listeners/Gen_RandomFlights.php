<?php

namespace Modules\DisposableSpecial\Listeners;

use App\Events\PirepAccepted;
use App\Services\FinanceService;
use App\Support\Money;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Modules\DisposableBasic\Models\DB_RandomFlight;
use Modules\DisposableSpecial\Models\DS_Assignment;

class Gen_RandomFlights
{
    // Reward Pilot if a "Random Flight" or "Monthly Flight Assignment" is flown
    public function handle(PirepAccepted $event)
    {
        $reward_rf = DS_Setting('turksim.randomflights_reward', false);
        $reward_mfa = DS_Setting('turksim.assignments_reward', false);
        $DBasic = DS_CheckModule('DisposableBasic');

        if (!$reward_rf && !$reward_mfa) {
            return;
        }

        $pirep = $event->pirep;
        $pirep->loadMissing('user.rank', 'user.journal', 'flight.airline');

        $where = [];
        $where['user_id'] = $pirep->user_id;
        $where['flight_id'] = $pirep->flight_id;

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
            'Flight Reward (Pirep: ' . $pirep->id . ')',
            $group,
            'rewards',
            $today
        );
        // Debit Airline
        $financeSvc->debitFromJournal(
            $airline->journal,
            $amount,
            $user,
            'Flight Reward (' . $user->name_private . ' Pirep: ' . $pirep->id . ')',
            $group,
            'rewards',
            $today
        );

        // Ammend Log
        Log::debug('Disposable Special | ' . $user->name_private . ' rewarded ' . $amount . ' for ' . $group . ' completion');
    }
}
