<?php

namespace Modules\DisposableSpecial\Listeners;

use App\Events\PirepAccepted;
use App\Models\Airport;
use App\Services\AirportService;
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
                // Provide basic information to admins
                // Crash, Operational Diversion or Scenery Problem
                if (abs($pirep->landing_rate) > 1500) {
                    // Possible crash due to the high landing rate
                    $diversion_reason = 'Crashed Near ' . $diversion_apt;
                } elseif ($diverted) {
                    // Diverted but not crashed and airport is found with lookup
                    $diversion_reason = "Operational";
                } else {
                    // Diverted but airport was not found with lookup
                    $diversion_reason = "Scenery Problem";
                }

                // Send the message with reason BEFORE changing the pirep values
                $this->SendDiversionMessage($pirep, $diversion_apt, $diversion_reason);
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
                    $pirep->alt_airport_id = $pirep->arr_airport_id; // Save intended dest as alternate for fixing it back when needed
                    $pirep->arr_airport_id = $diverted->id; // Use diversion dest as the new arrival
                    $pirep->flight_id = null; // Remove the flight id to drop the relationship
                    $pirep->save();

                    Log::info('Disposable Special | Pirep ' . $pirep->id . ' Flight ' . $pirep->ident . ' DIVERTED to ' . $diversion_apt . ', assets MOVED to Diversion Airport');
                }

                // Airport NOT found (only edit Pirep values)
                else {
                    $pirep->notes = 'DIVERTED (' . $pirep->arr_airport_id . ' > ' . $diversion_apt . ') ' . $pirep->notes;
                    $pirep->flight_id = null;
                    $pirep->save();

                    Log::info('Disposable Special | Pirep ' . $pirep->id . ' Flight ' . $pirep->ident . ' DIVERTED to ' . $diversion_apt . ', NOT ABLE to move assets !');
                }
            }
        }
    }

    public function SendDiversionMessage($pirep, $diversion_airport, $diversion_reason)
    {
        $webhookurl = DS_Setting('turksim.discord_divert_webhook');
        $msgposter = !empty(DS_Setting('turksim.discord_divert_msgposter')) ? DS_Setting('turksim.discord_divert_msgposter') : config('app.name');
        $pirep_aircraft = !empty($pirep->aircraft) ? $pirep->aircraft->ident : "Not Reported";

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
                        ["name" => "__Orig__", "value" => $pirep->dpt_airport_id, "inline" => true],
                        ["name" => "__Dest__", "value" => $pirep->arr_airport_id, "inline" => true],
                        ["name" => "__Equipment__", "value" => $pirep_aircraft, "inline" => true],
                        ["name" => "__Diverted__", "value" => $diversion_airport, "inline" => true],
                        ["name" => "__Reason__", "value" => $diversion_reason, "inline" => true],
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
