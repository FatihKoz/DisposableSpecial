<?php

namespace Modules\DisposableSpecial\Listeners;

use App\Events\PirepAccepted;
use App\Models\Airport;
use App\Services\AirportService;
use Illuminate\Support\Facades\Log;
use Modules\DisposableSpecial\Services\DS_NotificationServices;

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
                    $diversion_reason = 'Crashed Near '.$diversion_apt;
                } elseif ($diverted) {
                    // Diverted but not crashed and airport is found with lookup
                    $diversion_reason = 'Operational';
                } else {
                    // Diverted but airport was not found with lookup
                    $diversion_reason = 'Scenery Problem';
                }

                // Send the message with reason BEFORE changing the pirep values
                $DiscordSvc = app(DS_NotificationServices::class);
                $DiscordSvc->SendDiversionMessage($pirep, $diversion_apt, $diversion_reason);
            }

            if (DS_Setting('turksim.pireps_handle_diversions', true)) {
                // Airport found
                if ($diverted) {
                    // Move Assets to Diversion Destination and edit Pirep values
                    $aircraft->airport_id = $diverted->id;
                    $aircraft->save();

                    $user->curr_airport_id = $diverted->id;
                    $user->save();

                    $pirep->notes = 'DIVERTED ('.$pirep->arr_airport_id.' > '.$diversion_apt.') '.$pirep->notes;
                    $pirep->alt_airport_id = $pirep->arr_airport_id; // Save intended dest as alternate for fixing it back when needed
                    $pirep->arr_airport_id = $diverted->id; // Use diversion dest as the new arrival
                    $pirep->flight_id = null; // Remove the flight id to drop the relationship
                    $pirep->route_leg = null; // Remove the route_leg to exclude this pirep from tour checks
                    $pirep->save();

                    Log::info('Disposable Special | Pirep '.$pirep->id.' Flight '.$pirep->ident.' DIVERTED to '.$diversion_apt.', assets MOVED to Diversion Airport');
                }

                // Airport NOT found (only edit Pirep values)
                else {
                    $pirep->notes = 'DIVERTED ('.$pirep->arr_airport_id.' > '.$diversion_apt.') '.$pirep->notes;
                    $pirep->flight_id = null; // Remove the flight id to drop the relationship
                    $pirep->route_leg = null; // Remove the route_leg to exclude this pirep from tour checks
                    $pirep->save();

                    Log::info('Disposable Special | Pirep '.$pirep->id.' Flight '.$pirep->ident.' DIVERTED to '.$diversion_apt.', NOT ABLE to move assets !');
                }
            }
        }
    }
}
