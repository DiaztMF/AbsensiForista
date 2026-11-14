<?php

namespace App\Notifications\Channels;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Notifications\Notification;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class WebPushChannel
{
    /**
     * Channel ini hanya dipakai untuk notifiable model User.
     *
     * @param  User  $notifiable
     */
    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toWebPush')) {
            return;
        }

        $subscriptions = $notifiable->pushSubscriptions()->get();
        if ($subscriptions->isEmpty()) {
            return;
        }

        $publicKey = config('services.webpush.public_key');
        $privateKey = config('services.webpush.private_key');

        if (! $publicKey || ! $privateKey) {
            return;
        }

        $webPush = new WebPush([
            'VAPID' => [
                'subject' => config('services.webpush.subject'),
                'publicKey' => $publicKey,
                'privateKey' => $privateKey,
            ],
        ]);

        $payload = json_encode($notification->toWebPush($notifiable), JSON_THROW_ON_ERROR);

        foreach ($subscriptions as $sub) {
            $webPush->queueNotification(
                Subscription::create([
                    'endpoint' => $sub->endpoint,
                    'publicKey' => $sub->public_key,
                    'authToken' => $sub->auth_token,
                    'contentEncoding' => $sub->content_encoding ?: 'aesgcm',
                ]),
                $payload
            );
        }

        foreach ($webPush->flush() as $report) {
            if ($report->isSubscriptionExpired()) {
                PushSubscription::where('endpoint', $report->getEndpoint())->delete();
            }
        }
    }
}
