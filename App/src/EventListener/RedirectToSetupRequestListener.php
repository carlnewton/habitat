<?php

namespace App\EventListener;

use App\Entity\Settings;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class RedirectToSetupRequestListener
{
    private const PRE_SETUP_ALLOWED_ROUTES = [
        'app_setup_admin',
        'app_setup_location',
        'app_setup_categories',
    ];

    public function __construct(protected EntityManagerInterface $entityManager)
    {
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

        if (in_array($request->get('_route'), self::PRE_SETUP_ALLOWED_ROUTES)) {
            return;
        }

        $event->setResponse(new RedirectResponse('setup', RedirectResponse::HTTP_FOUND));
    }
}