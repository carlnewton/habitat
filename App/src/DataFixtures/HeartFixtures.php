<?php

namespace App\DataFixtures;

use App\Entity\Heart;
use App\Entity\Post;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class HeartFixtures extends Fixture implements DependentFixtureInterface
{
    private const HEARTS = [
        'Neo' => [1, 2, 5, 8],
        'Morpheus' => [4, 5, 6, 7],
        'Trinity' => [1, 2, 3, 4, 5, 6, 7],
        'Switch' => [6, 7, 8],
        'Apoc' => [1],
        'Mouse' => [9, 10, 11, 12],
        'Smith' => [1, 4, 7, 9, 10],
        'Oracle' => [8, 9, 11, 14],
        'Architect' => [1, 2],
        'Cypher' => [3, 5, 7, 9, 11, 12, 13],
        'Dozer' => [1, 2, 3, 4, 5],
    ];

    public function load(ObjectManager $manager): void
    {
        foreach (self::HEARTS as $username => $postReferences) {
            $user = $this->getReference('user/' . strtolower($username), User::class);
            foreach ($postReferences as $postReference) {
                $post = $this->getReference('post/' . $postReference, Post::class);
                $heartEntity = new Heart();
                $heartEntity->setUser($user);
                $heartEntity->setPost($post);

                $manager->persist($heartEntity);
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            PostFixtures::class,
        ];
    }
}
