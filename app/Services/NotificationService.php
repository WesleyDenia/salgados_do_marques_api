<?php

namespace App\Services;

use App\Models\NotificationToken;
use Illuminate\Support\Facades\Http;
use App\Notifications\GlobalNotification;

class NotificationService
{
    public function registerToken($user, string $token, ?string $platform=null)
    {
        NotificationToken::updateOrCreate(
            ['token'=>$token],
            ['user_id'=>$user->id,'platform'=>$platform,'last_used_at'=>now()]
        );
    }

    public function sendGlobal(string $title, string $body)
    {
        $tokens = NotificationToken::pluck('token')->all();
        if (empty($tokens)) return;

        $serverKey = config('services.fcm.server_key');
        Http::withToken($serverKey)
            ->post('https://fcm.googleapis.com/fcm/send', [
                'registration_ids'=>$tokens,
                'notification'=>['title'=>$title,'body'=>$body],
            ]);

        foreach (\App\Models\User::all() as $user) {
            $user->notify(new GlobalNotification($title, $body));
        }
    }
}
