<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    private const DATETIME_FORMAT = 'Y/m/d H:i:s';

    private const USERS = [
        [
            'username' => 'Architect',
            'email' => 'architect@example.com',
            'created' => '2024/01/01 00:00:00',
            'roles' => ['ROLE_SUPER_ADMIN'],
        ],
        [
            'username' => 'Neo',
            'email' => 'neo@example.com',
            'created' => '2024/01/01 01:01:01',
            'roles' => [],
        ],
        [
            'username' => 'Trinity',
            'email' => 'trinity@example.com',
            'created' => '2024/01/02 10:21:00',
            'roles' => [],
        ],
        [
            'username' => 'Morpheus',
            'email' => 'morpheus@example.com',
            'created' => '2024/01/03 10:22:00',
            'roles' => [],
        ],
        [
            'username' => 'Smith',
            'email' => 'smith@example.com',
            'created' => '2024/01/04 10:23:00',
            'roles' => [],
        ],
        [
            'username' => 'Oracle',
            'email' => 'oracle@example.com',
            'created' => '2024/01/05 10:24:00',
            'roles' => [],
        ],
        [
            'username' => 'Cypher',
            'email' => 'cypher@example.com',
            'created' => '2024/01/06 10:25:00',
            'roles' => [],
        ],
        [
            'username' => 'Dozer',
            'email' => 'dozer@example.com',
            'created' => '2024/01/07 10:25:00',
            'roles' => [],
        ],
        [
            'username' => 'Tank',
            'email' => 'tank@example.com',
            'created' => '2024/01/08 10:25:00',
            'roles' => [],
        ],
        [
            'username' => 'Switch',
            'email' => 'switch@example.com',
            'created' => '2024/01/09 10:26:00',
            'roles' => [],
        ],
        [
            'username' => 'Apoc',
            'email' => 'apoc@example.com',
            'created' => '2024/01/10 10:24:00',
            'roles' => [],
        ],
        [
            'username' => 'Mouse',
            'email' => 'mouse@example.com',
            'created' => '2024/01/11 10:24:00',
            'roles' => [],
        ],
        [
            'username' => 'Niobi',
            'email' => 'niobi@example.com',
            'created' => '2024/01/12 10:24:00',
            'roles' => [],
        ],
        [
            'username' => 'Merovingian',
            'email' => 'merovingian@example.com',
            'created' => '2024/01/13 10:24:00',
            'roles' => [],
        ],
        [
            'username' => 'Persephone',
            'email' => 'persephone@example.com',
            'created' => '2024/01/14 10:24:00',
            'roles' => [],
        ],
        [
            'username' => 'Seraph',
            'email' => 'seraph@example.com',
            'created' => '2024/01/15 10:24:00',
            'roles' => [],
        ],
    ];

    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        foreach (self::USERS as $user) {
            $userEntity = new User();
            $userEntity
                ->setUsername($user['username'])
                ->setEmailAddress($user['email'])
                ->setCreated(\DateTimeImmutable::createFromFormat(self::DATETIME_FORMAT, $user['created']))
                ->setRoles($user['roles'])
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
