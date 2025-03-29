<?php

namespace App\Twig;

use App\Repository\SettingsRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class RelativeDistanceExtension extends AbstractExtension
{
    protected SettingsRepository $settingsRepository;
    protected Security $security;

    public function __construct(
        SettingsRepository $settingsRepository,
        Security $security,
    ) {
        $this->settingsRepository = $settingsRepository;
        $this->security = $security;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('miles_to_relative_distance', [$this, 'milesToRelativeDistance']),
        ];
    }

    public function milesToRelativeDistance(?float $distance): string
    {
        if (null === $distance) {
            return 'Distance unknown';
        }

        $measurement = null;
        $user = $this->security->getUser();
        if (!is_null($user)) {
            foreach ($user->getSettings() as $userSetting) {
                if ('locationMeasurement' === $userSetting->getName()) {
                    $measurement = $userSetting->getValue();
                }
            }
        }

        if (is_null($measurement)) {
            $measurement = $this->settingsRepository->getSettingByName('locationMeasurement')->getValue();
        }

        if ('km' === $measurement) {
            $distance = $distance * 1.609344;
        }

        if ($distance >= 100) {
            return round($distance) . ' ' . $measurement . ' away';
        }

        return round($distance, 1) . ' ' . $measurement . ' away';
    }
}
