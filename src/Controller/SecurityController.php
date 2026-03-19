<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\CustomerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Notifier\ChatterInterface;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\LoginLink\LoginLinkHandlerInterface;
use Symfony\Component\Security\Http\SecurityEvents;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class SecurityController extends AbstractController
{
    use TargetPathTrait;

    private readonly EntityManagerInterface $em;

    private readonly Request $request;

    private readonly Security $security;

    private readonly AuthenticationUtils $authenticationUtil;

    public function __construct(
        UserAuthenticatorInterface $userAuthenticator,
        TokenStorageInterface $tokenstorage,
        EntityManagerInterface $em,
        RequestStack $requestStack,
        Security $security,
        AuthenticationUtils $authenticationUtil
    ) {
        $this->userAuthenticator = $userAuthenticator;
        $this->tokenStorage = $tokenstorage;
        $this->em = $em;
        $this->request = $requestStack->getCurrentRequest();
        $this->security = $security;
        $this->authenticationUtil = $authenticationUtil;
    }

    #[Route(path: '/google-login', name: 'security_google_callback', methods: ['GET', 'POST'])]
    public function googleCallback(Request $request, EventDispatcherInterface $dispatcher): Response
    {
        $clientId = $this->getParameter('app_google_client_id');
        $client = new \Google_Client(['client_id' => $clientId]);  // Specify the CLIENT_ID of the app that accesses the backend
        $token = json_decode($request->getContent());
        $payload = $client->verifyIdToken($token->token);
        if ($payload) {
            $user = $this->em->getRepository(User::class)->findOneBy([
                'email' => $payload['email'],
            ]);
            if ($user instanceof User) {
                // Den Benutzer manuell mit Symfony anmelden
                $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
                $this->tokenStorage->setToken($token);
                // Event-Listener für den Login-Vorgang hast, kannst du ihn hier auslösen
                $event = new InteractiveLoginEvent($request, $token);
                $dispatcher->dispatch($event, SecurityEvents::INTERACTIVE_LOGIN);

                return $this->json(true);
            }
        }

        return $this->json(false);
    }

    #[Route(path: '/login', name: 'security_login')]
    public function login(ChatterInterface $chatter, LoginLinkHandlerInterface $loginLinkHandler, CustomerRepository $customerRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if ($user instanceof User && $_ENV['APP_ENV'] !== 'dev') {
            #$chatMessage = (new ChatMessage('Login: '.$user->getUsername()))->transport('slack_login');
            #$chatter->send($chatMessage);
        }
        // if user is already logged in, don't display the login page again
        if ((!empty($this->request->request->get('_montage')) && $this->security->isGranted('ROLE_MONTAGE'))
            || ($this->security->isGranted('ROLE_MONTAGE') && !$this->security->isGranted('ROLE_EMPLOYEE_SERVICE'))) {
            //            if (empty($path) || stristr($path, 'ajax')) {
            //                $this->saveTargetPath($this->request->getSession(), 'main', $this->generateUrl('sf_montage'));
            //            }

            return $this->redirectToRoute('sf_montage');
        }
        // if user is already logged in, don't display the login page again
        if ($this->security->isGranted('ROLE_SUPER_ADMIN') && empty($this->request->query->get('user'))) {
            //            if (empty($path) || stristr($path, 'ajax')) {
            //                $this->saveTargetPath($this->request->getSession(), 'main', $this->generateUrl('dashboard_index'));
            //            }

            return $this->redirectToRoute('dashboard_index');
        }
        if ($this->security->isGranted('ROLE_EMPLOYEE_SERVICE') && empty($this->request->query->get('user'))) {
            //            if (empty($path) || stristr($path, 'ajax')) {
            //                $this->saveTargetPath($this->request->getSession(), 'main', $this->generateUrl('offer_index'));
            //            }

            return $this->redirectToRoute('dashboard_index');
        }
        // if user is already logged in, don't display the login page again
        if ($this->security->isGranted('ROLE_CUSTOMER')) {
            //            if (empty($path) || stristr($path, 'ajax')) {
            //                $this->saveTargetPath($this->request->getSession(), 'main', $this->generateUrl('dashboard_index'));
            //            }

            return $this->redirectToRoute('public_index');
        }

        $clientId = $this->getParameter('app_google_client_id');
        $clientSecret = $this->getParameter('app_google_client_secret');

        return $this->render('security/login.html.twig', [
            // last username entered by the user (if any)
            'clientId' => $clientId,
            'clientSecret' => $clientSecret,
            'last_username' => $this->authenticationUtil->getLastUsername() ?? '',
            'montage' => false,
            // last authentication error (if any)
            'error' => $this->authenticationUtil->getLastAuthenticationError(),
        ]);
    }

    #[Route(path: '/montage-login', name: 'sf_montage_login', methods: ['GET', 'POST'])]
    public function index(): Response
    {
        return $this->render('security/login.html.twig', [
            // last username entered by the user (if any)
            'last_username' => $this->authenticationUtil->getLastUsername() ?? '',
            'montage' => true,
        ]);
    }

    /**
     * This is the route the user can use to logout.
     *
     * But, this will never be executed. Symfony will intercept this first
     * and handle the logout automatically. See logout in config/packages/security.yaml
     */
    #[Route(path: '/logout', name: 'security_logout')]
    public function logout(): never
    {
        throw new \Exception('This should never be reached!');
    }
}
