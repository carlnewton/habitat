<?php

namespace App\DataFixtures\Setup;

use App\Entity\Settings;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class SetupImageStorageFixtures extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    public static function getGroups(): array
    {
        return [
            'setup',
            'setup-image-storage'
        ];
    }

    public function getDependencies(): array
    {
        return [
            SetupCategoriesFixtures::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        $settingsRepository = $manager->getRepository(Settings::class);

        $storageSetting = new Settings();
        $storageSetting
            ->setName('imageStorage')
            ->setValue('local')
        ;
        $manager->persist($storageSetting);

        $setupSetting = $settingsRepository->findOneBy(['name' => 'setup']);
        if (is_null($setupSetting)) {
            $setupSetting = new Settings();
        }
        $setupSetting
            ->setName('setup')
            ->setValue('mail')
        ;
        $manager->persist($setupSetting);

        $manager->flush();
    }
}
