<?php

namespace App\DataFixtures;

use App\Entity\Settings;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class SettingFixtures extends Fixture
{
    private const SETTINGS = [
        'habitatName' => 'City of London',
        'locationRadiusMeters' => 3000,
        'locationMeasurement' => 'km',
        'locationZoom' => 14,
        'locationLatLng' => '51.5071,-0.1283',
        'setup' => 'complete',
    ];

    public function load(ObjectManager $manager)
    {
        foreach (self::SETTINGS as $name => $value) {
            $setting = new Settings();
            $setting
                ->setName($name)
                ->setValue($value)
            ;
            $manager->persist($setting);
        }

        $manager->flush();
    }
}
