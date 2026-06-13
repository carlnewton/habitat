<?php

namespace App\Twig;

use App\Repository\SettingsRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class SettingsExtension extends AbstractExtension
{
    private const USER_OVERRIDABLE_SETTINGS = [
        'theme',
        'locationMeasurement',
    ];

    public function __construct(
        private SettingsRepository $settingsRepository,
        private Security $security,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('setting', [$this, 'getSetting']),
        ];
    }

    public function getSetting(string $settingName, mixed $defaultValue = null): mixed
    {
        if (in_array($settingName, self::USER_OVERRIDABLE_SETTINGS)) {
            $user = $this->security->getUser();
            if (!is_null($user)) {
                foreach ($user->getSettings() as $userSetting) {
                    if ($settingName === $userSetting->getName()) {
                        return $userSetting->getValue();
                    }
                }
            }
        }

        $setting = $this->settingsRepository->getSettingByName($settingName);

        if (empty($setting) || empty($setting->getValue())) {
            return $defaultValue;
        }

        return $setting->getValue();
    }
}
