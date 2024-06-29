<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Comment;
use App\Entity\CategoryLocationOptionsEnum;
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
            'location' => CategoryLocationOptionsEnum::REQUIRED,
        ],
        'News and Events' => [
            'description' => 'Posts related to news updates, events, festivals, concerts, or community gatherings.',
            'location' => CategoryLocationOptionsEnum::OPTIONAL,
        ],
        'Food and Drink' => [
            'description' => 'Discussions and pictures of restaurants, cafes, food trucks, or special dishes.',
            'location' => CategoryLocationOptionsEnum::OPTIONAL,
        ],
        'History' => [
            'description' => 'Pictures and discussions specifically focused on the historical significance, stories, and events related to local historical sites, buildings, or events in the area.',
            'location' => CategoryLocationOptionsEnum::OPTIONAL,
        ],
        'Businesses' => [
            'description' => 'Posts promoting or discussing shops, boutiques, or services.',
            'location' => CategoryLocationOptionsEnum::OPTIONAL,
        ],
        'Sports and Recreation' => [
            'description' => 'Conversations about outdoor activities, or recreational facilities.',
            'location' => CategoryLocationOptionsEnum::OPTIONAL,
        ],
        'Community Initiatives' => [
            'description' => 'Posts about charities, volunteer opportunities, or community projects.',
            'location' => CategoryLocationOptionsEnum::OPTIONAL,
        ],
        'Habitat Meta' => [
            'description' => 'Discussions about this instance of Habitat.',
            'location' => CategoryLocationOptionsEnum::DISABLED,
        ],
        'Random' => [
            'description' => 'A catch-all for various topics that do not fit anywhere else.',
            'location' => CategoryLocationOptionsEnum::OPTIONAL,
        ],
    ];

    public function load(ObjectManager $manager)
    {
        foreach (self::CATEGORIES as $categoryName => $categorySettings) {
            $categoryEntity = new Category();
            $categoryEntity
                ->setName($categoryName)
                ->setDescription($categorySettings['description'])
                ->setLocation($categorySettings['location'])
            ;

            $manager->persist($categoryEntity);

            $this->addReference('category/' . $categoryName, $categoryEntity);
        }

        $manager->flush();
    }
}
