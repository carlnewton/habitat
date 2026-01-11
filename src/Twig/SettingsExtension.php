<?php

namespace App\Twig;

use App\Repository\SettingsRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class SettingsExtension extends AbstractExtension
{
    protected SettingsRepository $settingsRepository;

    public function __construct(SettingsRepository $settingsRepository)
    {
        $this->settingsRepository = $settingsRepository;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('setting', [$this, 'getSetting']),
        ];
    }

    public function getSetting(string $settingName, mixed $defaultValue = null): mixed
    {
        $setting = $this->settingsRepository->getSettingByName($settingName);

        if (empty($setting) || empty($setting->getValue())) {
            return $defaultValue;
        }

        return $setting->getValue();
    }
}
