<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\ChatDTO;
use App\Entity\Chat;
use App\Entity\OfferQuestion;
use App\Entity\QuestionArea;
use App\Entity\User;
use App\Form\ChatType;
use App\Repository\ChatRepository;
use App\Repository\QuestionAreaRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Bundle\TimeBundle\DateTimeFormatter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class ChatController extends AbstractController
{
    use TargetPathTrait;

    public function __construct(private EntityManagerInterface $entityManager, private MessageBusInterface $bus)
    {
    }

    #[Route(path: '/admin/chat-api/create', name: 'app_chat_create')]
    #[IsGranted('ROLE_MONTAGE')]
    public function addChatMessage(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $chat = new Chat();
        $chat->setUser($user);
        $chat->setDate(new \DateTime());

        $form = $this->createForm(ChatType::class, $chat);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($chat);
            $this->entityManager->flush();
            $this->entityManager->refresh($chat);
            $t = new ChatDTO();
            $t->date = $chat->getDate()->format('Y-m-d H:i:s');
            $t->content = $chat->getText();
            $t->userId = $chat->getUser()->getId();
            $t->chatId = $chat->getId();
            $this->addMessageCount($user);
            // $this->bus->dispatch($t);
        }

        return $this->json($chat->getId());
    }

    #[Route('/admin/chat-api/{id}', name: 'api_chat_all')]
    #[IsGranted('ROLE_MONTAGE')]
    public function getMessages(Request $request, DateTimeFormatter $formatter, ChatRepository $chatReoo, UserRepository $userRepo, int $id = 0): JsonResponse
    {
        if ($id < 0) {
            $id = 0;
        }
        /** @var User $user */
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        if ($currentUser->getUnreadChats() > 0) {
            $currentUser->setUnreadChats(0);
            $this->entityManager->persist($currentUser);
            $this->entityManager->flush();
        }

        $chatMessages = $chatReoo->getScalar($id);
        $chatMessage = $chatReoo->findNext($id);
        $chat = [];
        $chats = [];
        $date = new \DateTime();
        if (!$chatMessage instanceof Chat) {
            return $this->json(false);
        }

        foreach ($chatMessages as $key => $message) {
            $user = $userRepo->find($message['userId']);
            $chat['avatar'] = $user->getAvatarUri();
            $chat['content'] = $message['content'];
            $chat['userId'] = $message['userId'];
            $chat['chatId'] = $message['chatId'];
            $chatDate = new \DateTime($message['date']);
            $chat['date'] = $formatter->formatDiff(
                $chatDate,
                $date,
                $request->getLocale());
            array_push($chats, $chat);
        }

        return $this->json($chats);
    }

    #[Route(path: [
        'en' => '/chat/employee',
        'de' => '/chat/mitarbeiter',
    ], name: 'app_chat', methods: ['GET', 'POST'])]
    public function index(Request $request, ChatRepository $chatRepo): Response
    {
        $chat = new Chat();
        $date = new \DateTime();
        /** @var User $user */
        $user = $this->getUser();
        $chat->setUser($user);
        $chat->setDate($date);
        $form = $this->createForm(ChatType::class, $chat);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($chat);
            $this->entityManager->flush();
            $t = new ChatDTO();
            $t->userId = $user->getId();
            $t->chatId = $chat->getId();
            $t->date = $chat->getDate()->format('Y-m-d H:i:s');
            $t->content = $chat->getText();
            $this->removeMessageCounts($user);
            // $this->bus->dispatch($t);
            $user->setUnreadChats(0);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }
        $lastResult = $chatRepo->getLastScalar(Chat::MAX_RESULT);
        sort($lastResult);
        $lastId = $chatRepo->lastId();

        return $this->render('chat/index.html.twig', [
            'chats' => $lastResult,
            'lastId' => $lastId - Chat::MAX_RESULT,
            'form' => $form->createView(),
        ]);
    }

    public function addMessageCount(User $currentUser)
    {
        $uRepo = $this->entityManager->getRepository(User::class);
        $users = $uRepo->findBy([
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

    public function removeMessageCounts(User $currentUser)
    {
        $currentUser->setUnreadChats(0);
        $this->entityManager->persist($currentUser);

        $this->entityManager->flush();
    }
}
