<?php

namespace App\EventListener;

use App\Entity\Settings;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;

#[AsEventListener(priority: 15)]
class LanguageListener
{
    public function __construct(
        protected EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(
        RequestEvent $event,
    ): void {
        if (!$request = $event->getRequest()) {
            return;
        }

        if (!$settingRepository = $this->entityManager->getRepository(Settings::class)) {
            return;
        }

        $languageSetting = $settingRepository->getSettingByName('language');
        if (!$languageSetting) {
            return;
        }

        if (empty($languageSetting->getValue())) {
            return;
        }

        $request->setLocale($languageSetting->getValue());
    }
}
