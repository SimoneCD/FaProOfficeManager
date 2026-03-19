<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Messager DTO für Chatnachrichten.
 */
class ChatDTO
{
    public int $userId;
    public int $chatId;
    public string $date;

    #[Assert\NotBlank]
    public string $content;

}