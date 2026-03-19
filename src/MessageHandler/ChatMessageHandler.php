<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\ChatMessage;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class ChatMessageHandler
{
    public function __construct(private EntityManagerInterface $entityManager, private UserRepository $userRepository, ?string $bus = null, ?string $fromTransport = null, ?string $handles = null, ?string $method = null, int $priority = 0)
    {
        $this->bus = $bus;
        $this->fromTransport = $fromTransport;
        $this->handles = $handles;
        $this->method = $method;
        $this->priority = $priority;
    }

    public function __invoke(ChatMessage $message)
    {
        $currentUser = $this->userRepository->find($message->getUserId());
        $users = $this->userRepository->findBy([
            'status' => 1,
        ]);

        foreach ($users as $user) {
            if ($currentUser->getId() != $user->getId()) {
                $user->setUnreadChats($user->getUnreadChats() + 1);
                $this->entityManager->persist($user);
            }
        }
        $this->entityManager->flush();
    }
}
