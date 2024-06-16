<?php

namespace App\DataFixtures;

use App\Entity\Post;
use App\Entity\PostAttachment;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Filesystem\Filesystem;

class PostAttachmentFixtures extends Fixture implements DependentFixtureInterface
{
    private const ATTACHMENT_FIXTURE_FILES = [
        [
            'filename' => '1.jpg',
            'width' => 720,
            'height' => 960,
        ],
        [
            'filename' => '2.jpg',
            'width' => 720,
            'height' => 960,
        ],
        [
            'filename' => '3.jpg',
            'width' => 960,
            'height' => 720,
        ],
        [
            'filename' => '4.jpg',
            'width' => 960,
            'height' => 720,
        ],
        [
            'filename' => '5.jpg',
            'width' => 720,
            'height' => 960,
        ],
        [
            'filename' => '6.jpg',
            'width' => 720,
            'height' => 960,
        ],
        [
            'filename' => '7.jpg',
            'width' => 960,
            'height' => 720,
        ],
        [
            'filename' => '8.jpg',
            'width' => 960,
            'height' => 720,
        ],
    ];

    private const POST_ATTACHMENT_GROUPS = [
        [
            'attachments' => [0, 1, 2],
            'post' => 1,
        ],
        [
            'attachments' => [2, 3],
            'post' => 2,
        ],
        [
            'attachments' => [4],
            'post' => 3,
        ],
        [
            'attachments' => [5, 6, 7, 1],
            'post' => 4,
        ],
        [
            'attachments' => [0, 2, 4],
            'post' => 5,
        ],
        [
            'attachments' => [7, 6],
            'post' => 6,
        ],
        [
            'attachments' => [6, 5, 4, 3, 2],
            'post' => 8,
        ],
        [
            'attachments' => [6],
            'post' => 10,
        ],
        [
            'attachments' => [5, 2, 4],
            'post' => 11,
        ],
        [
            'attachments' => [0, 6],
            'post' => 13,
        ],
        [
            'attachments' => [7, 3, 4, 6],
            'post' => 14,
        ],
        [
            'attachments' => [3, 1, 7],
            'post' => 15,
        ],
    ];

    public function load(ObjectManager $manager)
    {
        $filesystem = new Filesystem();
        foreach (self::ATTACHMENT_FIXTURE_FILES as $attachmentFixtureFile) {
            if (!$filesystem->exists('/var/www/uploads/' . $attachmentFixtureFile['filename'])) {
                $filesystem->copy('assets/img/fixtures/' . $attachmentFixtureFile['filename'], '/var/www/uploads/' . $attachmentFixtureFile['filename']);
            }
        }

        foreach (self::POST_ATTACHMENT_GROUPS as $postAttachmentGroup) {
            $post = $this->getReference('post/' . $postAttachmentGroup['post']);
            foreach ($postAttachmentGroup['attachments'] as $attachmentPosition) {
                $attachmentFixtureFile = self::ATTACHMENT_FIXTURE_FILES[$attachmentPosition];
                $attachmentEntity = new PostAttachment();
                $attachmentEntity->setPost($post);
                $attachmentEntity->setUser($post->getUser());
                $attachmentEntity->setWidth($attachmentFixtureFile['width']);
                $attachmentEntity->setHeight($attachmentFixtureFile['height']);
                $attachmentEntity->setFilename($attachmentFixtureFile['filename']);
                
                $manager->persist($attachmentEntity);
            }
        }

        $manager->flush();
    }

    public function getDependencies()
    {
        return [
            UserFixtures::class,
            PostFixtures::class,
        ];
    }
}
