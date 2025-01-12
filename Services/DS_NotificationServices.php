<?php

namespace Modules\DisposableSpecial\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Modules\DisposableSpecial\Models\Enums\DS_ItemCategory;

class DS_NotificationServices
{
    // New User Registered Message
    public function NewUserMessage($user)
    {
        $wh_url = DS_Setting('turksim.discord_divert_webhook');
        $msgposter = !empty(DS_Setting('turksim.discord_divert_msgposter')) ? DS_Setting('turksim.discord_divert_msgposter') : config('app.name');

        $json_data = json_encode([
            'content'  => 'New User Registered !',
            'username' => $msgposter,
            'tts'      => false,
            'embeds'   => [
                [
                    'type'      => 'rich',
                    'timestamp' => date('c', strtotime($user->created_at)),
                    'color'     => hexdec('FF0000'),
                    'fields'    => [
                        ['name' => '__Name__', 'value' => '['.$user->name.']('.route('admin.users.edit', [$user->id]).')', 'inline' => true],
                        ['name' => '__E-Mail__', 'value' => $user->email, 'inline' => true],
                    ],
                ],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $this->DS_DiscordNotification($wh_url, $json_data);
    }

    // Market Message
    public function MarketActionMessage($buyer, $item, $gifted = null)
    {
        $wh_url = DS_Setting('turksim.discord_divert_webhook');
        $msgposter = !empty(DS_Setting('turksim.discord_divert_msgposter')) ? DS_Setting('turksim.discord_divert_msgposter') : config('app.name');

        $user = isset($gifted) ? $gifted : $buyer;
        $user_avatar = !empty($user->avatar) ? $user->avatar->url : $user->gravatar(256);

        $amount = number_format($item->price, 0).' '.setting('units.currency');
        $category = DS_ItemCategory::label($item->category);

        $json_data = json_encode([
            'content'  => 'Market Item Bought !',
            'username' => $msgposter,
            'tts'      => false,
            'embeds'   => [
                [
                    'type'      => 'rich',
                    'timestamp' => Carbon::now()->format('c'),
                    'image'     => !empty($item->image_url) ? ['url' => public_asset($item->image_url)] : null,
                    'color'     => hexdec('FF0000'),
                    'thumbnail' => ['url' => $user_avatar],
                    'author'    => ['name' => $user->ident.' | '.$user->name_private, 'url' => route('frontend.profile.show', [$user->id])],
                    'fields'    => [
                        ['name' => '__Item__', 'value' => $item->name, 'inline' => true],
                        ['name' => '__Price__', 'value' => $amount, 'inline' => true],
                        ['name' => '__Category__', 'value' => $category, 'inline' => true],
                    ],
                ],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $this->DS_DiscordNotification($wh_url, $json_data);
    }

    // Diversion Message
    public function SendDiversionMessage($pirep, $diversion_airport, $diversion_reason)
    {
        $wh_url = DS_Setting('turksim.discord_divert_webhook');
        $msgposter = !empty(DS_Setting('turksim.discord_divert_msgposter')) ? DS_Setting('turksim.discord_divert_msgposter') : config('app.name');
        $pirep_aircraft = !empty($pirep->aircraft) ? $pirep->aircraft->ident : 'Not Reported';

        $json_data = json_encode([
            'content'  => 'Diversion Occured !',
            'username' => $msgposter,
            'tts'      => false,
            'embeds'   => [
                [
                    'title'     => '**Diverted Flight Details**',
                    'type'      => 'rich',
                    'timestamp' => date('c', strtotime($pirep->submitted_at)),
                    'color'     => hexdec('FF0000'),
                    'author'    => ['name' => 'Pilot In Command: '.$pirep->user->name_private, 'url' => route('frontend.profile.show', [$pirep->user_id])],
                    'fields'    => [
                        ['name' => '__Flight #__', 'value' => $pirep->ident, 'inline' => true],
                        ['name' => '__Orig__', 'value' => $pirep->dpt_airport_id, 'inline' => true],
                        ['name' => '__Dest__', 'value' => $pirep->arr_airport_id, 'inline' => true],
                        ['name' => '__Equipment__', 'value' => $pirep_aircraft, 'inline' => true],
                        ['name' => '__Diverted__', 'value' => $diversion_airport, 'inline' => true],
                        ['name' => '__Reason__', 'value' => $diversion_reason, 'inline' => true],
                    ],
                ],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $this->DS_DiscordNotification($wh_url, $json_data);
    }

    // Send generated Discord Message
    public function DS_DiscordNotification($webhook_url, $json_data)
    {
        $ch = curl_init($webhook_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-type: application/json']);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        if ($response) {
            Log::debug('Disposable Special | Discord WebHook Msg Response: '.$response);
        }
        curl_close($ch);
    }
}
