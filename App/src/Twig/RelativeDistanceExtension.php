<?php

namespace App\Twig;

use App\Repository\SettingsRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class RelativeDistanceExtension extends AbstractExtension
{
    protected SettingsRepository $settingsRepository;

    public function __construct(SettingsRepository $settingsRepository)
    {
        $this->settingsRepository = $settingsRepository;
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

        $measurement = $this->settingsRepository->getSettingByName('locationMeasurement')->getValue();
        if ('km' === $measurement) {
            $distance = $distance * 1.609344;
        }

        if ($distance >= 100) {
            return round($distance) . ' ' . $measurement . ' away';
        }

        return round($distance, 1) . ' ' . $measurement . ' away';
    }
}
