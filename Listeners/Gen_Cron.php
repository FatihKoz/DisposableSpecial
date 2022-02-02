<?php

namespace Modules\DisposableSpecial\Listeners;

use App\Contracts\Listener;
use App\Events\CronHourly;
use App\Events\CronMonthly;
use App\Events\UserRegistered;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Modules\DisposableSpecial\Http\Controllers\DS_AssignmentController;
use Modules\DisposableSpecial\Models\DS_Maintenance;

class Gen_Cron extends Listener
{
    // Callback to proper method
    public static $callbacks = [
        CronHourly::class  => 'handle_hourly',
        CronMonthly::class => 'handle_monthly',
        UserRegistered::class => 'handle_newuser',
    ];

    // New User Registrations
    public function handle_newuser(UserRegistered $event)
    {
        if (DS_Setting('turksim.discord_registermsg', false)) {
            $this->SendDiscordMessage($event->user);
        }
    }

    // Cron Hourly
    public function handle_hourly()
    {
        $this->ProcessMaintenance();
    }

    // Cron Monthly
    public function handle_monthly()
    {
        if (DS_Setting('turksim.assignments_auto', false)) {
            $this->AssignFlights();
        }
    }

    // Assign Monthly Flights
    public function AssignFlights()
    {
        $flightAssignments = app(DS_AssignmentController::class);
        $flightAssignments->TriggerAssignment();
    }

    // Process Maintenance Records and Release Aircraft Back To Service
    public function ProcessMaintenance()
    {
        $current_time = Carbon::now();
        $active_maint_ops = DS_Maintenance::with('aircraft')->whereNotNull('act_end')->get();
        foreach ($active_maint_ops as $active) {
            if ($active->act_end < $current_time) {
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
                Log::info('CRON, ' . $active->aircraft->registration . ' released back to service after ' . $active->last_note);
            }
        }
    }

    public function SendDiscordMessage($user)
    {
        $webhookurl = DS_Setting('turksim.discord_divert_webhook');
        $msgposter = !empty(DS_Setting('turksim.discord_divert_msgposter')) ? DS_Setting('turksim.discord_divert_msgposter') : config('app.name');

        $json_data = json_encode([
            "content" => "New User Registered !",
            "username" => $msgposter,
            "tts" => false,
            "embeds" =>
            [
                [
                    "type" => "rich",
                    "timestamp" => date("c", strtotime($user->created_at)),
                    "color" => hexdec("FF0000"),
                    "fields" =>
                    [
                        ["name" => "__Name__", "value" => "[".$user->name."](".route('admin.users.edit', [$user->id]).")", "inline" => true],
                        ["name" => "__E-Mail__", "value" => $user->email, "inline" => true],
                    ],
                ],
            ]
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $ch = curl_init($webhookurl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        if ($response) {
            Log::debug('Discord WebHook | New User Msg Response: ' . $response);
        }
        curl_close($ch);
    }
}
