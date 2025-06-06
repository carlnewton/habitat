<?php

namespace App\DataFixtures;

use App\Entity\Comment;
use App\Entity\Post;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

// TODO: Break these down into comments per user so that we can use that particular user for dependencies
class CommentFixtures extends Fixture implements DependentFixtureInterface
{
    private const COMMENTS = [
        1 => [
            [
                'user' => 'Neo',
                'comment' => 'I love this place so much!',
            ],
            [
                'user' => 'Trinity',
                'comment' => 'It has free parking in the winter',
            ],
            [
                'user' => 'Morpheus',
                'comment' => 'I go here every year!',
            ],
            [
                'user' => 'Cypher',
                'comment' => 'I didn\'t know about this place! Thanks!',
            ],
            [
                'user' => 'Trinity',
                'comment' => 'Great photos btw!',
            ],
        ],
        2 => [
            [
                'user' => 'Apoc',
                'comment' => 'This is a great place to hang out with friends.',
            ],
            [
                'user' => 'Switch',
                'comment' => 'Does anyone know if this is open on weekends?',
            ],
            [
                'user' => 'Oracle',
                'comment' => 'It\'s open between 9 and 5 everyday, including weekends',
            ],
            [
                'user' => 'Smith',
                'comment' => 'Ah good to know, the times aren\'t on the website',
            ],
            [
                'user' => 'Neo',
                'comment' => 'They are now!',
            ],
            [
                'user' => 'Persephone',
                'comment' => 'Oh finally!',
            ],
            [
                'user' => 'Oracle',
                'comment' => 'Lovely place this!',
            ],
        ],
        4 => [
            [
                'user' => 'Architect',
                'comment' => 'Nice! <3',
            ],
            [
                'user' => 'Oracle',
                'comment' => 'Lovely sunrise photo!',
            ],
        ],
        7 => [
            [
                'user' => 'Trinity',
                'comment' => 'I didn\'t know this place was still open! I used to go here as a kid! I\'ll have to return some time',
            ],
        ],
        9 => [
            [
                'user' => 'Neo',
                'comment' => 'Do they serve vegan food here?',
            ],
            [
                'user' => 'Switch',
                'comment' => 'They do!',
            ],
        ],
        14 => [
            [
                'user' => 'Merovingian',
                'comment' => 'Thanks for posting this! <3',
            ],
        ],
    ];

    public function load(ObjectManager $manager): void
    {
        foreach (self::COMMENTS as $postReference => $comments) {
            foreach ($comments as $comment) {
                $post = $this->getReference('post/' . $postReference, Post::class);
                $user = $this->getReference('user/' . strtolower($comment['user']), User::class);
                $commentEntity = new Comment();
                $commentEntity->setPost($post);
                $commentEntity->setUser($user);
                $commentEntity->setBody($comment['comment']);
                $commentEntity->setPosted(new \DateTimeImmutable());

                $manager->persist($commentEntity);
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            PostFixtures::class,
        ];
    }
}
