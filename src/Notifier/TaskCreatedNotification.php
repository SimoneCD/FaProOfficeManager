<?php

namespace App\Notifier;

use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\Recipient\Recipient;

class TaskCreatedNotification extends Notification
{
    private string $taskTitle;

    public function __construct(string $taskTitle)
    {
        parent::__construct('Neue Aufgabe erstellt');
        $this->taskTitle = $taskTitle;
        $this->content("Die Aufgabe '$taskTitle' wurde erstellt.")
            ->importance(Notification::IMPORTANCE_HIGH); // High = Push-Benachrichtigung
    }

    // Optional: Anpassung der Push-Nachricht für Firebase
    public function asPushMessage(Recipient $recipient, ?string $transport = null): ?\Symfony\Component\Notifier\Message\PushMessage
    {
        if ('firebase' === $transport) {
            return new \Symfony\Component\Notifier\Message\PushMessage(
                $this->getSubject(),
                "Aufgabe: {$this->taskTitle}"
            );
        }

        return null;
    }
}
