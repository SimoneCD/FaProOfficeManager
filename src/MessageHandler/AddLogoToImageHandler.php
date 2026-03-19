<?php

namespace App\MessageHandler;

use App\Message\AddLogoToImage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class AddLogoToImageHandler
{
    public function __invoke(AddLogoToImage $message)
    {
        // ... do some work - like sending an SMS message!
    }
}