<?php

namespace App\Security\Authentication;

use App\Entity\Customer;
use App\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class AuthenticationSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    private RouterInterface $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        $user = $token->getUser();
        if ($user instanceof Customer) {
            $expires = $request->query->get('expires');
            if (!empty($expires)) {
                $url = $this->router->generate('login_password', [
                    'expires' => $expires,
                    'user' => $user,
                ]);

                return new RedirectResponse($url);
            }
        } elseif ($user instanceof User) {
            return new RedirectResponse(
                $this->router->generate('dashboard_index')
            );
        }

        return new RedirectResponse(
            $this->router->generate('security_login')
        );
    }
}
