<?php

namespace App\EventListener;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class LogoutSuspendedUserRequestListener
{
    public function __construct(protected Security $security)
    {
    }

    public function __invoke(
        RequestEvent $event,
    ): void {
        if (!$event->isMainRequest()) {
            return;
        }

        if (!$user = $this->security->getUser()) {
            return;
        }

        if (!$request = $event->getRequest()) {
            return;
        }

        if (!$session = $request->getSession()) {
            return;
        }

        if (!$flashBag = $session->getFlashBag()) {
            return;
        }

        if ($user->isSuspended()) {
            $this->security->logout(false);
            $flashBag->add('warning', 'Your account has been suspended.');

            return;
        }

        if (!$user->isEmailVerified()) {
            $this->security->logout(false);
            $flashBag->add('warning', 'Your email address must be verified before you can login.');

            return;
        }
    }
}
