<?php

namespace Modules\DisposableSpecial\Listeners;

use App\Events\PirepAccepted;
use App\Models\Airport;
use App\Services\AirportService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Gen_Diversion
{
    // Listen PirepAccepted event to change Aircraft and Pilot location if diversion happens
    public function handle(PirepAccepted $event)
    {
        $pirep = $event->pirep;
        $diversion_apt = optional($pirep->fields->where('slug', 'diversion-airport')->first())->value;

        if ($diversion_apt) {
            $pirep->loadMissing('aircraft', 'user');
            $user = $pirep->user;
            $aircraft = $pirep->aircraft;
            $diverted = Airport::select('id')->where('id', $diversion_apt)->first();

            if (!$diverted) {
                // Airport Not Found In DB, Try To Lookup and Insert First
                $airportSvc = app(AirportService::class);
                $diverted = $airportSvc->lookupAirportIfNotFound($diversion_apt) ?? null;
            }

            if (DS_Setting('turksim.discord_divertmsg')) {
                $this->SendDiversionMessage($pirep, $diversion_apt, $diverted);
            }

            if (DS_Setting('turksim.pireps_handle_diversions', true)) {
                // Airport found
                if ($diverted) {
                    // Move Assets to Diversion Destination and edit Pirep values
                    $aircraft->airport_id = $diverted->id;
                    $aircraft->save();

                    $user->curr_airport_id = $diverted->id;
                    $user->save();

                    $pirep->notes = 'DIVERTED (' . $pirep->arr_airport_id . ' > ' . $diversion_apt . ') ' . $pirep->notes;
                    $pirep->arr_airport_id = $diverted->id;
                    $pirep->flight_id = null;
                    $pirep->save();

                    Log::info('TurkSim Module: Pirep=' . $pirep->id . ' Flight=' . $pirep->ident . ' DIVERTED to ' . $diversion_apt . ', assets MOVED to Diversion Airport');
                }

                // Airport NOT found (only edit Pirep values)
                else {
                    $pirep->notes = 'DIVERTED (' . $pirep->arr_airport_id . ' > ' . $diversion_apt . ') ' . $pirep->notes;
                    $pirep->flight_id = null;
                    $pirep->save();

                    Log::info('TurkSim Module: Pirep=' . $pirep->id . ' Flight=' . $pirep->ident . ' DIVERTED to ' . $diversion_apt . ', NOT ABLE to move assets !');
                }
            }
        }
    }

    public function SendDiversionMessage($pirep, $div_dest, $diverted = null)
    {
        $webhookurl = DS_Setting('turksim.discord_divert_webhook');
        $msgposter = !empty(DS_Setting('turksim.discord_divert_msgposter')) ? DS_Setting('turksim.discord_divert_msgposter') : config('app.name');
        $pirep_aircraft = !empty($pirep->aircraft) ? $pirep->aircraft->ident : "Not Reported";
        // Real Diversion, Crash or Error of Acars
        if ($diverted && $diverted->id == $pirep->dpt_airport_id) {
            $div_reason = "Acars";
        } elseif ($diverted && $diverted->id != $pirep->dpt_airport_id && abs($pirep->landing_rate) > 1500) {
            $div_reason = "Crash";
        } elseif ($diverted) {
            $div_reason = "Operational";
        } else {
            // User Diverted but airport was not found with lookup
            $div_reason = "Scenery";
        }
        $json_data = json_encode([
            "content" => "Diversion Occured !",
            "username" => $msgposter,
            "tts" => false,
            "embeds" =>
            [
                [
                    "title" => "**Diverted Flight Details**",
                    "type" => "rich",
                    "timestamp" => date("c", strtotime($pirep->submitted_at)),
                    "color" => hexdec("FF0000"),
                    "author" => ["name" => "Pilot In Command: " . $pirep->user->name_private, "url" => route('frontend.profile.show', [$pirep->user_id])],
                    "fields" =>
                    [
                        ["name" => "__Flight #__", "value" => $pirep->ident, "inline" => true],
                        ["name" => "__Origin__", "value" => $pirep->dpt_airport_id, "inline" => true],
                        ["name" => "__Destination__", "value" => $pirep->alt_airport_id, "inline" => true],
                        ["name" => "__Equipment__", "value" => $pirep_aircraft, "inline" => true],
                        ["name" => "__Diverted__", "value" => $div_dest, "inline" => true],
                        ["name" => "__Reason__", "value" => $div_reason, "inline" => true],
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
            Log::debug('Discord WebHook | Diversion Msg Response: ' . $response);
        }
        curl_close($ch);
    }
}
