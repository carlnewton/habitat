<?php

namespace App\EventListener;

use App\Entity\Settings;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class RedirectToSetupRequestListener
{
    private const SETUP_ROUTES_PREFIX = 'app_setup_';

    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected UrlGeneratorInterface $router
    ) {
    }

    public function __invoke(
        RequestEvent $event,
    ): void {
        if (!$event->isMainRequest()) {
            return;
        }

        if (!$request = $event->getRequest()) {
            return;
        }

        if (!$settingRepository = $this->entityManager->getRepository(Settings::class)) {
            return;
        }

        if ($settingRepository->isValue('setup', 'complete')) {
            return;
        }

        if (str_starts_with($request->get('_route'), self::SETUP_ROUTES_PREFIX)) {
            return;
        }

        $event->setResponse(new RedirectResponse($this->router->generate('app_setup_admin'), RedirectResponse::HTTP_FOUND));
    }
}
