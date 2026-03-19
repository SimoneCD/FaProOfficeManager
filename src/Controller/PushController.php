<?php

namespace App\Controller;

use App\Entity\PushSubscription;
use App\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/push')]
class PushController extends BaseController
{
    #[Route('/subscribe', name: 'api_push-subscribe', methods: ['POST'])]
    public function subscribe(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!$data || !isset($data['endpoint']) || !isset($data['keys'])) {
            return new JsonResponse(['error' => 'Ungültige Daten'], 400);
        }

        $user = $this->getUser() ?? $this->em->getRepository(User::class)->find(2); // Fallback
        if (!$user) {
            return new JsonResponse(['error' => 'Kein Benutzer'], 400);
        }
        // dd($user);
        // Prüfe, ob eine Subscription für den Benutzer existiert
        $existingSubscription = $this->em->getRepository(PushSubscription::class)
            ->findOneBy(['user' => $user]);
        if ($existingSubscription) {
            // Aktualisiere die bestehende Subscription
            $existingSubscription->setEndpoint($data['endpoint']);
            $existingSubscription->setKeys($data['keys']);
            $subscription = $existingSubscription;
        } else {
            // Erstelle eine neue Subscription
            $subscription = new PushSubscription();
            $subscription->setEndpoint($data['endpoint']);
            $subscription->setKeys($data['keys']);
            $subscription->setUser($user);
        }

        $this->em->persist($subscription);
        $this->em->flush();

        return new JsonResponse(['status' => 'subscribed', 'id' => $subscription->getId()]);
    }
}
