<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Customer;
use App\Entity\CustomerNotes;
use App\Entity\Product;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use function PHPUnit\Framework\throwException;

/**
 * Class AjaxController.
 */
#[Route(path: '/ajax/ajax-public-chat')]
#[IsGranted('ROLE_CUSTOMER')]
class AjaxPublicController extends BaseController
{
    /** add Kundenservice-Kundenchat antwort*/
    #[Route(path: ['de' => '/add-kunden-chats/{id}', 'en' => '/add-customer-chats/{id}'], name: 'ajax_chat_customer_add', methods: ['GET', 'POST'])]
    public function addChatAnswer(Request $request, Customer $customer): Response
    {
        if ($customer->getId() === $this->getUser()->getId() || $this->isGranted('ROLE_EMPLOYEE_SERVICE')) {
            $post = json_decode($request->getContent());
            $date = new \DateTimeImmutable();
            $chat = new CustomerNotes();
            $chat->setUser($this->getUser());
            $chat->setCustomer($customer);
            $chat->setCreatedAt($date);
            $chat->setType('sos');
            $chat->setAnsweredAt($date);
            $chat->setAnsweredAt($date);
            $chat->setNote($post->note);

            $this->em->persist($chat);
            $notes = $this->em->getRepository(CustomerNotes::class)->findBy([
                'customer' => $customer,
            ]);
            /** @var CustomerNotes $note */
            foreach ($notes as $note) {
                if ($note->getAnsweredAt() === null) {
                    $note->setAnsweredAt($date);
                    $this->em->persist($note);
                }
            }
            $this->em->flush();

            return $this->render('public/admin/_customer_chat.html.twig', [
                'customer' => $customer,
            ]);
        }

        return $this->render('public/admin/_customer_chat.html.twig', [
            'customer' => $this->getUser(),
        ]);
    }

    /** Ladet Kundendaten für den Kundenservice-Kundenchat */
    #[Route(path: ['de' => '/load-kunden-chats/{id}', 'en' => '/load-customer-chats/{id}'], name: 'ajax_chat_customer', methods: ['GET', 'POST'])]
    public function loadChat(Customer $customer): Response
    {
        return $this->render('public/admin/_customer_chat.html.twig', [
            'customer' => $customer,
        ]);
    }

    /** Ladet Kundendaten für den Kundenservice-Kundenchat */
    #[Route(path: ['de' => '/kunden-chats', 'en' => '/customer-chats'], name: 'ajax_chat_customer_load', methods: ['GET', 'POST'])]
    public function getNewCustomersChatData(): JsonResponse
    {
        $chats = $this->em->getRepository(CustomerNotes::class)->findNewCustomerChats();

        return $this->json([
            'panel' => $this->render('public/admin/chatbox_panel.html.twig', ['chats' => $chats]),
            'popup' => $this->render('public/admin/chatbox_popup.html.twig', ['chats' => $chats]),
        ]);
    }

    #[Route(path: '/product/{id}', name: 'ajax_public_products', methods: ['GET'])]
    public function getProductFaqs(Product $product): Response
    {
        return $this->render('faq/product.html.twig', [
            'product' => $product,
        ]);
    }
}
