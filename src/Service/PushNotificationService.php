<?php

namespace App\Service;

use App\Entity\PushSubscription;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class PushNotificationService
{
    private string $publicKey;
    private string $privateKey;

    public function __construct(string $publicKey, string $privateKey)
    {
        $this->publicKey = $publicKey;
        $this->privateKey = $privateKey;
    }

    public function sendNotification(PushSubscription $subscription, string $title, string $body): void
    {
        $webPush = new WebPush([
            'VAPID' => [
                'subject' => 'mailto:kundenservice@zukunftsorientierte-energie.de',
                'publicKey' => $this->publicKey,
                'privateKey' => $this->privateKey,
            ],
        ]);

        $payload = json_encode(['title' => $title, 'body' => $body]);
        $webPush->queueNotification(
            Subscription::create([
                'endpoint' => $subscription->getEndpoint(),
                'publicKey' => $subscription->getKeys()['p256dh'],
                'authToken' => $subscription->getKeys()['auth'],
            ]),
            $payload
        );

        foreach ($webPush->flush() as $report) {
            //            if (!$report->isSuccess()) {
            //                dd([
            //                    'success' => false,
            //                    'endpoint' => $subscription->getEndpoint(),
            //                    'reason' => $report->getReason(),
            //                    'response' => $report->getResponse()->getBody()->getContents(),
            //                ]);
            //            } else {
            //                dd([
            //                    'success' => true,
            //                    'endpoint' => $subscription->getEndpoint(),
            //                    'title' => $title,
            //                    'body' => $body,
            //                    'response' => $report->getResponse()->getBody()->getContents(),
            //                ]);
            //            }
        }
    }
}
