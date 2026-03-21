<?php

namespace App\Command;

use App\Entity\Settings;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractLanguageCommand extends Command
{
    protected string $language = 'en';

    public function __construct(
        protected EntityManagerInterface $entityManager,
        private TranslatorInterface $translator,
    ) {
        $this->setLanguage();
        parent::__construct();
    }

    protected function setLanguage(): void
    {
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

        $this->language = $languageSetting->getValue();
    }

    protected function trans(string $id, array $parameters = [], ?string $domain = null): string
    {
        return $this->translator->trans($id, $parameters, $domain, $this->language);
    }
}
