<?php

namespace App\DataFixtures\Setup;

use App\Entity\Settings;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class SetupMailFixtures extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    private const SETTINGS = [
        'smtpFromEmailAddress' => 'from@example.com',
        'smtpToEmailAddress' => 'to@example.com',
        'smtpPort' => '587',
        'smtpServer' => 'mail.example.com',
        'smtpPassword' => 'password',
        'smtpUsername' => 'username',
        'setup' => 'complete',
    ];

    public static function getGroups(): array
    {
        return [
            'setup',
            'setup-mail'
        ];
    }

    public function getDependencies(): array
    {
        return [
            SetupImageStorageFixtures::class,
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
