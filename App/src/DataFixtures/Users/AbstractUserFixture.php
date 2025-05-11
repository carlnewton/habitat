<?php

namespace App\DataFixtures\Users;

use App\DataFixtures\Setup\SetupMailFixtures;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

abstract class AbstractUserFixture extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
    private const DATETIME_FORMAT = 'Y/m/d H:i:s';

    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public static function getGroups(): array
    {
        return [
            'users',
        ];
    }

    public function getDependencies(): array
    {
        return [
            SetupMailFixtures::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        foreach (static::USERS as $user) {
            $userEntity = new User();
            $userEntity
                ->setUsername($user['username'])
                ->setEmailAddress($user['email'])
                ->setCreated(\DateTimeImmutable::createFromFormat(self::DATETIME_FORMAT, $user['created']))
                ->setEmailVerified(true)
            ;

            $hashedPassword = $this->passwordHasher->hashPassword($userEntity, $user['username']);
            $userEntity->setPassword($hashedPassword);

            $manager->persist($userEntity);

            $this->addReference('user/' . strtolower($userEntity->getUsername()), $userEntity);
        }

        $manager->flush();
    }
}
