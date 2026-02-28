<?php

namespace App\DataFixtures\Setup;

use App\Entity\Settings;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class SetupLocationFixtures extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    private const SETTINGS = [
        'habitatName' => 'City of London',
        'locationRadiusMeters' => 3000,
        'locationMeasurement' => 'km',
        'locationZoom' => 14,
        'locationLatLng' => '51.5071,-0.1283',
        'setup' => 'categories',
    ];

    public static function getGroups(): array
    {
        return [
            'setup',
            'setup-location',
        ];
    }

    public function getDependencies(): array
    {
        return [
            SetupAdminFixtures::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        $settingsRepository = $manager->getRepository(Settings::class);
        foreach (self::SETTINGS as $name => $value) {
            $setting = $settingsRepository->findOneBy(['name' => $name]);
            if (is_null($setting)) {
                $setting = new Settings();
            }
            $setting
                ->setName($name)
                ->setValue($value)
            ;
            $manager->persist($setting);
        }

        $manager->flush();
    }
}
