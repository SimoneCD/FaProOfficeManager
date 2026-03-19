<?php

declare(strict_types=1);

namespace App\Message;

use App\DTO\ChatDTO;

/**
 * Try Messenger.
 */
final class ChatMessage
{
    private string $content;
    private int $userId;
    private int $chatId;
    private string $date;

    public function __construct()
    {
    }

    public function newMessageFrom(ChatDTO $chatDTO): self
    {
        $message = new self();
        $message->content = $chatDTO->content;
        $message->userId = $chatDTO->userId;
        $message->chatId = $chatDTO->chatId;
        $message->date = $chatDTO->date;

        return $message;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getDate(): string
    {
        return $this->date;
    }

    public function getChatId(): int
    {
        return $this->chatId;
    }
}
