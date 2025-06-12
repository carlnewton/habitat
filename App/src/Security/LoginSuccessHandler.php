<?php

namespace App\Security;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private RouterInterface $router,
        private Security $security
    ) {
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        $user = $token->getUser();

        if (!$user->isEmailVerified()) {
            $this->security->logout(false);
            $request->getSession()->invalidate();


            return new RedirectResponse(
                $this->router->generate('app_login', [
                    'email_verification_failed' => true
                ]), RedirectResponse::HTTP_FOUND
            );
        }

        return new RedirectResponse($this->router->generate('app_index_index'), RedirectResponse::HTTP_FOUND);
    }
}
