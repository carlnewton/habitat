<?php

namespace App\DataFixtures\Setup;

use App\Entity\Settings;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SetupAdminFixtures extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    private const SETTINGS = [
        'setup' => 'location',
    ];

    private const DATETIME_FORMAT = 'Y/m/d H:i:s';

    private const ADMIN_USER = [
        'username' => 'Architect',
        'email' => 'architect@example.com',
        'created' => '2024/01/01 00:00:00',
        'roles' => ['ROLE_SUPER_ADMIN'],
    ];

    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public static function getGroups(): array
    {
        return [
            'setup',
            'setup-admin',
        ];
    }

    public function getDependencies(): array
    {
        return [
            SetupLanguageFixtures::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        $userEntity = new User();
        $userEntity
            ->setUsername(self::ADMIN_USER['username'])
            ->setEmailAddress(self::ADMIN_USER['email'])
            ->setCreated(\DateTimeImmutable::createFromFormat(self::DATETIME_FORMAT, self::ADMIN_USER['created']))
            ->setRoles(self::ADMIN_USER['roles'])
            ->setEmailVerified(true)
        ;

        $hashedPassword = $this->passwordHasher->hashPassword($userEntity, self::ADMIN_USER['username']);
        $userEntity->setPassword($hashedPassword);

        $manager->persist($userEntity);

        $this->addReference('user/' . strtolower($userEntity->getUsername()), $userEntity);

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
