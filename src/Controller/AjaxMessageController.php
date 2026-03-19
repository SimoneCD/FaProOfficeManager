<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Offer;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class AjaxMessageController.
 */
#[Route(path: '/ajax/ajax-message')]
class AjaxMessageController extends AbstractController
{
    public function __construct()
    {
    }

    #[Route(path: '/{id}', name: 'ajax_message', methods: ['POST'])]
    public function sendOpenMessage(Offer $offer): JsonResponse
    {
//        $message = new SendActiveOfferMessage($offer->getId(), $offer->getCustomer()->getFullNormalName());
//        $this->bus->dispatch($message);

        return new JsonResponse([]);
    }
}
