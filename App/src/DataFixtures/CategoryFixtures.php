<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Comment;
use App\Entity\Post;
use App\Entity\PostAttachment;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Filesystem\Filesystem;

class CategoryFixtures extends Fixture 
{
    private const CATEGORIES = [
        'Sightseeing' => [
            'description' => 'A space for sharing and discussing visual discoveries, landmarks, nature spots, street art, hidden gems, and other unique finds in the area.',
            'locationEnabled' => true,
            'color' => '6f42c1',
        ],
        'News and Events' => [
            'description' => 'Posts related to news updates, events, festivals, concerts, or community gatherings.',
            'locationEnabled' => true,
            'color' => 'd63384',
        ],
        'Food and Drink' => [
            'description' => 'Discussions and pictures of restaurants, cafes, food trucks, or special dishes.',
            'locationEnabled' => true,
            'color' => 'dc3545',
        ],
        'History' => [
            'description' => 'Pictures and discussions specifically focused on the historical significance, stories, and events related to local historical sites, buildings, or events in the area.',
            'locationEnabled' => true,
            'color' => 'fd7e14',
        ],
        'Businesses' => [
            'description' => 'Posts promoting or discussing shops, boutiques, or services.',
            'locationEnabled' => true,
            'color' => 'ffc107',
        ],
        'Sports and Recreation' => [
            'description' => 'Conversations about outdoor activities, or recreational facilities.',
            'locationEnabled' => true,
            'color' => '198754',
        ],
        'Community Initiatives' => [
            'description' => 'Posts about charities, volunteer opportunities, or community projects.',
            'locationEnabled' => false,
            'color' => '20c997',
        ],
        'Random' => [
            'description' => 'A catch-all for various topics that do not fit anywhere else.',
            'locationEnabled' => true,
            'color' => '0dcaf0',
        ],
    ];

    public function load(ObjectManager $manager)
    {
        foreach (self::CATEGORIES as $categoryName => $categorySettings) {
            $categoryEntity = new Category();
            $categoryEntity
                ->setName($categoryName)
                ->setDescription($categorySettings['description'])
                ->setLocationEnabled($categorySettings['locationEnabled'])
                ->setColor($categorySettings['color'])
            ;

            $manager->persist($categoryEntity);

            $this->addReference('category/' . $categoryName, $categoryEntity);
        }

        $manager->flush();
    }
}
