<?php

namespace Modules\DisposableSpecial\Listeners;

use App\Contracts\Listener;
use App\Events\CronFiveMinute;
use App\Events\CronFifteenMinute;
use App\Events\CronThirtyMinute;
use App\Events\CronHourly;
use App\Events\CronNightly;
use App\Events\CronWeekly;
use App\Events\CronMonthly;
use App\Events\UserRegistered;
use Illuminate\Support\Facades\Log;
use Modules\DisposableSpecial\Http\Controllers\DS_AssignmentController;
use Modules\DisposableSpecial\Services\DS_CronServices;
use Modules\DisposableSpecial\Services\DS_MaintenanceServices;
use Modules\DisposableSpecial\Services\DS_NotificationServices;

class Gen_Cron extends Listener
{
    public static $callbacks = [
        CronFiveMinute::class => 'handle_05min',
        CronFifteenMinute::class => 'handle_15min',
        CronThirtyMinute::class => 'handle_30min',
        CronHourly::class  => 'handle_hourly',
        CronNightly::class => 'handle_nightly',
        CronWeekly::class => 'handle_weekly',
        CronMonthly::class => 'handle_monthly',
        UserRegistered::class => 'handle_newuser',
    ];

    // Cron 5 mins
    public function handle_05min()
    {
        $MaintSVC = app(DS_MaintenanceServices::class);
        $MaintSVC->ProcessMaintenance();
    }

    // Cron 15 mins
    public function handle_15min()
    {
        $CronSVC = app(DS_CronServices::class);
        $CronSVC->DeleteExpiredSimBrief();
        $CronSVC->FixBrokenSimBrief();
        if (DS_Setting('dspecial.database_cleanup', false) === true) {
            $CronSVC->CheckAcarsLogs();
        }
    }

    // Cron 30 mins
    public function handle_30min()
    {
        $CronSVC = app(DS_CronServices::class);
        $CronSVC->ProcessFreeFlights();
        $CronSVC->DeletePausedPireps(DS_Setting('dspecial.delete_paused_pireps', 0));
        // $this->DS_WriteToLog('30 mins test');
    }

    // Cron Hourly
    public function handle_hourly()
    {
        // $this->DS_WriteToLog('60 mins or Hourly test');
    }

    // Cron Nightly
    public function handle_nightly()
    {
        $CronSVC = app(DS_CronServices::class);
        $CronSVC->ProcessTours();
        $CronSVC->DeleteOldAcars(DS_Setting('dspecial.old_acars_posreps', 0));
        $CronSVC->DeleteOldSimBrief(DS_Setting('dspecial.old_simbrief_ofp', 0));
        $CronSVC->DeleteNonFlownMembers(DS_Setting('dspecial.delete_nonflown_members', 0));
        $CronSVC->RebaseParkedAircraft(DS_Setting('dspecial.rebase_parked_aircraft', 0));
    }

    // Cron Weekly
    public function handle_weekly()
    {
        if (DS_Setting('dspecial.database_cleanup', false) === true) {
            $CronSVC = app(DS_CronServices::class);
            $CronSVC->CleanAcarsRecords();
            $CronSVC->CleanRelationships();
        }
    }

    // Cron Monthly
    public function handle_monthly()
    {
        if (DS_Setting('turksim.assignments_auto', false)) {
            $FlightAssignments = app(DS_AssignmentController::class);
            $FlightAssignments->TriggerAssignment();
        }
    }

    // New User Registrations
    public function handle_newuser(UserRegistered $event)
    {
        if (DS_Setting('turksim.discord_registermsg', false)) {
            $NotificationSVC = app(DS_NotificationServices::class);
            $NotificationSVC->NewUserMessage($event->user);
        }
    }

    public function DS_WriteToLog($text = null)
    {
        Log::debug('Disposable Special | ' . $text);
    }
}
