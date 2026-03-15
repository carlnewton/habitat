<?php

namespace App\DataFixtures\Setup;

use App\Entity\Settings;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

class SetupLanguageFixtures extends Fixture implements FixtureGroupInterface
{
    private const SETTINGS = [
        'language' => 'en',
        'setup' => 'admin',
    ];

    public static function getGroups(): array
    {
        return [
            'setup',
            'setup-language',
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
