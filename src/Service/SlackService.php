<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Notifier\ChatterInterface;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SlackService
{
    protected const SLACK_URL = 'https://slack.com/api/';
    public const SLACK_REMINDER_TIME = [
        'call' => '10 minutes',
        'view' => '1 hour',
        'work' => '1 hour',
        'else' => '1 hour',
    ];
    public const SLACK_REMINDER_TIME_TEST = [
        'call' => '15 seconds',
        'view' => '15 seconds',
        'work' => '15 seconds',
        'else' => '15 seconds',
    ];
    protected const SLACK_ACTION = [
        'user-list' => 'users.list',
        'channel-list' => 'conversations.list',
        'user-remove' => 'admin.users.remove',
        'chat-post' => 'chat.postMessage',
        'reminder' => 'chat.scheduleMessage',
    ];

    public function __construct(
        private ChatterInterface $chatter,
        private HttpClientInterface $client,
        private string $appSlackBearer,
        // todo config
        // private ?string $appSlackTeamId = 'T05CNRXFNF3'
    ) {
    }

    public function getUserList()
    {
        $httpClient = HttpClient::create();
        $response = $httpClient->request('POST', self::SLACK_URL.self::SLACK_ACTION['user-list'], [
            'headers' => [
                'Authorization' => 'Bearer '.$this->appSlackBearer,
            ],
        ]);

        return $response->toArray();
    }

    public function getEmojiList()
    {
        $httpClient = HttpClient::create();
        $response = $httpClient->request('POST', self::SLACK_URL.'emoji.list', [
            'headers' => [
                'Authorization' => 'Bearer '.$this->appSlackBearer,
            ],
        ]);

        return $response->toArray();
    }

    public function getChannelList()
    {
        $httpClient = HttpClient::create();
        $response = $httpClient->request('POST', self::SLACK_URL.self::SLACK_ACTION['channel-list'], [
            'headers' => [
                'Authorization' => 'Bearer '.$this->appSlackBearer,
            ],
        ]);

        return $response->toArray();
    }

    public function addSlackReminderForUser(User $user, string $message, \DateTime $date, ?string $type = 'call'): void
    {
        $types = [
            'call' => 'Anruftermin',
            'work' => 'Montage-, Installationstermin',
            'view' => 'Besichtigungstermin',
            'else' => 'Termin',
        ];
        if (!empty($user->getSlackId())) {
            $payload = [
                'post_at' => $date->modify(self::SLACK_REMINDER_TIME_TEST[$type])->format('U'), // Hier geben wir an, dass die Nachricht in 1 Stunde gesendet werden soll
                'type' => 'modal',
                'title' => [
                    'type' => 'mrkdwn',
                    'text' => $types[$type],
                    'emoji' => true,
                ],
                'text' => $message.' <@'.$user->getSlackId().'>',
            ];
            $this->callInSlack($payload, $user->getSlackId(), self::SLACK_ACTION['reminder'], $message);
        }
    }

    public function faProSlackReminderToUser(User $user, string $title, string $message, \DateTime $date)
    {
        if (!empty($user->getSlackId())) {
            $payload = [
                'post_at' => $date->format('U'), // Hier geben wir an, dass die Nachricht in 1 Stunde gesendet werden soll
                'type' => 'modal',
                'text' => $title."\n".$message,
            ];

            return $this->callInSlack($payload, $user->getSlackId(), self::SLACK_ACTION['reminder'], $message);
        }

        return null;
    }

    public function faProSlackReminderToChannel(string $channel, string $title, string $message, \DateTime $date)
    {
        if (!empty($channel)) {
            $payload = [
                'post_at' => $date->format('U'), // Hier geben wir an, dass die Nachricht in 1 Stunde gesendet werden soll
                'type' => 'modal',
                'text' => $title."\n".$message,
            ];

            return $this->callInSlack($payload, $channel, self::SLACK_ACTION['reminder'], $message);
        }

        return null;
    }

    public function faProSlackToUser(string $channel, string $message): ?string
    {
        $payload = [
            'text' => $message/* .' <@'.$channel.'>' */,
        ];

        return $this->callInSlack($payload, $channel, self::SLACK_ACTION['chat-post'], $message);
    }

    public function faProSlackToChannel(User $sender, string $channel, string $message): ?string
    {
        if (!empty($sender->getSlackId())) {
            $payload = [
                'text' => $message/* .' <@'.$channel.'>' */,
                'close' => [
                    'type' => 'mrkdwn',
                    'text' => 'Schließen',
                    'emoji' => true,
                ],
            ];

            return $this->callInSlack($payload, $channel, self::SLACK_ACTION['chat-post'], $message);
        }

        return null;
    }

    private function callInSlack(array $payload, string $channel, string $url, string $message): string
    {
        $payloadAll = [
            'channel' => $channel,
            'text' => $message,
        ];
        $httpClient = HttpClient::create();
        $response = $httpClient->request('POST', self::SLACK_URL.$url, [
            'headers' => [
                'Authorization' => 'Bearer '.$this->appSlackBearer,
            ],
            'json' => array_merge($payloadAll, $payload),
        ]);
        if (200 === $response->getStatusCode()) {
            $data = $response->toArray();
            if ($data['ok']) {
                $text = 'Die Mitteilung wurde erfolgreich gesendet!';
            } else {
                $text = 'Fehler beim Senden der Mitteilung: '.$data['error'];
            }
        } else {
            $text = 'Fehler beim Senden der Mitteilung: '.$response->getStatusCode().' '.$response->getContent(false);
        }

        return $text;
    }

    public function addSlackLogToChannel(string $channel, string $message)
    {
        try {
            $chatMessage = (new ChatMessage($message))->transport($channel);
            $this->chatter->send($chatMessage);
        } catch (\Exception $exception) {
        }
    }

    public function addSlackMessageToUser(User $user, string $message)
    {
        // $this->appSlackBearer.'?default&channel='.$user->getSlackId()
        if ($user->getSlackId()) {
            $response = $this->client->withOptions([
                'base_uri' => self::SLACK_URL.self::SLACK_ACTION['chat-post'],
                'headers' => [
                    'Authorization' => 'Bearer '.$this->appSlackBearer,
                    // 'Content-type' => 'application/x-www-form-urlencoded',
                ],
                'extra' => [
                    'channel' => $user->getSlackId(),
                    'text' => $message,
                ],
            ]);
        }
    }

    public function sendIncludeButtonAction()
    {
        //                $contributeToSymfonyBlocks = (new SlackActionsBlock())
        //                    ->button(
        //                        'URL ansehen',
        //                        'https://kundenservice.zukunftsorientierte-energie.de'.$request->getRequestUri(),
        //                        'primary'
        //                    );
        //                $slackOptions = (new SlackOptions())
        //                    ->block((new SlackSectionBlock())
        //                        ->text($user->getUsername().' war abwesend: '.round($seconds / 60, 0).' Minuten')
        //                    )
        //                    ->block((new SlackSectionBlock())
        //                        ->text('Jetzt aktive in '.$request->getRequestUri())
        //                    )
        //                    ->block(new SlackDividerBlock())
        //                    ->block($contributeToSymfonyBlocks);
    }
}
