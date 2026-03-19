<?php

namespace App\Security;

use JetBrains\PhpStorm\NoReturn;
use Symfony\Component\Notifier\ChatterInterface;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class CustomLogoutListener
{
    public function __construct(private ChatterInterface $chatter)
    {
    }

    #[NoReturn]
    public function onSymfonyComponentSecurityHttpEventLogoutEvent(LogoutEvent $logoutEvent): void
    {
        if (null !== $logoutEvent->getToken()) {
            $user = $logoutEvent->getToken()->getUser();
            if ('dev' !== $_ENV['APP_ENV']) {
                // $chatMessage = (new ChatMessage('Logout: '.$user->getUsername()))->transport('slack_login');
                // $this->chatter->send($chatMessage);
            }
        }
    }
}
