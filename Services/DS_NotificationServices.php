<?php

namespace Modules\DisposableSpecial\Services;

use Illuminate\Support\Facades\Log;

class DS_NotificationServices
{
    // Prepar Discord Message
    public function NewUserMessage($user)
    {
        $wh_url = DS_Setting('turksim.discord_divert_webhook');
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
                        ["name" => "__Name__", "value" => "[" . $user->name . "](" . route('admin.users.edit', [$user->id]) . ")", "inline" => true],
                        ["name" => "__E-Mail__", "value" => $user->email, "inline" => true],
                    ],
                ],
            ]
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $this->DS_DiscordNotification($wh_url, $json_data);
    }

    // Send generated Discord Message
    public function DS_DiscordNotification($webhook_url, $json_data)
    {
        $ch = curl_init($webhook_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        if ($response) {
            Log::debug('Disposable Special | Discord WebHook Msg Response: ' . $response);
        }
        curl_close($ch);
    }
}
