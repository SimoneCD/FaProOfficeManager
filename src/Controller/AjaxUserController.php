<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class AjaxMessageController.
 */
#[Route(path: '/ajax/ajax-user')]
#[IsGranted('ROLE_MONTAGE')]
class AjaxUserController extends AbstractController
{

    #[Route(path: '/{user}', name: 'ajax_user_unread_messages', methods: ['GET'])]
    public function getUnreadMessages(User $user): JsonResponse
    {
        return $this->json($user->getUnreadChats());
    }
}
