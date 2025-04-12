<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Post;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class PostFixtures extends Fixture implements DependentFixtureInterface
{
    private const DATETIME_FORMAT = 'Y/m/d H:i:s';

    private const POSTS = [
        [
            'user' => 'Morpheus',
            'title' => 'King\'s Cross Station',
            'body' => 'King\'s Cross railway station, also known as London King\'s Cross, is a passenger railway terminus in the London Borough of Camden, on the edge of Central London.',
            'posted' => '2024/02/01 10:24:00',
            'latitude' => 51.5303,
            'longitude' => -0.124063,
            'category' => 'History',
        ],
        [
            'user' => 'Trinity',
            'title' => 'Queen Mary\'s Garden',
            'body' => 'Picturesque garden created in the 1930s, showcasing 12,000 rose bushes on landscaped grounds.',
            'posted' => '2024/02/02 10:24:00',
            'latitude' => 51.5276,
            'longitude' => -0.153726,
            'category' => 'Sightseeing',
        ],
        [
            'user' => 'Neo',
            'title' => 'Hyde Park',
            'body' => 'Hyde Park is a 350-acre, historic Grade I-listed urban park in Westminster.',
            'posted' => '2024/02/03 10:24:00',
            'latitude' => 51.5078,
            'longitude' => -0.162235,
            'category' => 'Sightseeing',
        ],
        [
            'user' => 'Tank',
            'title' => 'Southwark Bridge',
            'body' => 'Southwark Bridge is an arch bridge in London, for traffic linking the district of Southwark and the City across the River Thames.',
            'posted' => '2024/02/04 10:24:00',
            'latitude' => 51.5093,
            'longitude' => -0.0938010,
            'category' => 'History',
        ],
        [
            'user' => 'Switch',
            'title' => 'Kia Oval',
            'body' => 'The Oval, currently named for sponsorship reasons as the Kia Oval, is an international cricket ground in Kennington, located in the borough of Lambeth, in south London. The Oval has been the home ground of Surrey County Cricket Club since it was opened in 1845.',
            'posted' => '2024/02/05 10:24:00',
            'latitude' => 51.4839,
            'longitude' => -0.115048,
            'category' => 'Sports and Recreation',
        ],
        [
            'user' => 'Cypher',
            'title' => 'Kensington Cross',
            'body' => 'Kennington Cross is a locality in the London Borough of Lambeth. It is at the junction of two major roads, Kennington Lane that links Vauxhall Cross with the Elephant and Castle and Kennington Road that runs from Waterloo to Kennington Park.',
            'posted' => '2024/02/06 10:24:00',
            'latitude' => 51.4887,
            'longitude' => -0.111094,
            'category' => 'Random',
        ],
        [
            'user' => 'Apoc',
            'title' => 'Millbank Millenium Pier',
            'body' => 'Millbank Pier is a pier on the west bank of the River Thames, in London, United Kingdom. It is served by boats operated by Uber Boat by Thames Clippers under licence from London River Services and is situated between Lambeth Bridge and Vauxhall Bridge on Millbank.',
            'posted' => '2024/02/06 10:24:00',
            'latitude' => 51.4918,
            'longitude' => -0.124744,
            'category' => 'Community Initiatives',
        ],
        [
            'user' => 'Neo',
            'title' => 'Mayfair',
            'body' => 'Bordering leafy Hyde Park, Mayfair is an upscale district of elegant Georgian townhouses, exclusive hotels, and gourmet restaurants. Its world-famous retailers include bespoke tailors on Savile Row and designer fashions on Bond Street. Shoppers also head to high-end Burlington Arcade and Shepherd Market, a cluster of independent boutiques and traditional pubs.',
            'posted' => '2024/02/06 10:24:00',
            'latitude' => 51.5112,
            'longitude' => -0.147051,
            'category' => 'Businesses',
        ],
        [
            'user' => 'Trinity',
            'title' => 'Soho',
            'body' => 'The energetic streets of Soho, in the West End, feature a variety of dining, nightlife, and shopping options. Dean, Frith, Beak, and Old Compton streets are the epicentre of activity day and night, and long-running Ronnie Scott\'s Jazz Club is also here.',
            'posted' => '2024/02/06 10:24:00',
            'latitude' => 51.5145,
            'longitude' => -0.134524,
            'category' => 'Sightseeing',
        ],
        [
            'user' => 'Morpheus',
            'title' => 'Covent Garden',
            'body' => 'A shopping and entertainment hub in London\'s West End, Covent Garden centres on the elegant, car-free Piazza, home to fashion stores, craft stalls at the Apple Market, and the Royal Opera House. Street entertainers perform by 17th-century St. Paul’s Church, and the London Transport Museum houses vintage vehicles.',
            'posted' => '2024/02/06 10:24:00',
            'latitude' => 51.5126,
            'longitude' => -0.122683,
            'category' => 'Random',
        ],
        [
            'user' => 'Mouse',
            'title' => 'Leicester Square',
            'body' => 'Leicester Square is a pedestrianised square in the West End of London, England. It was laid out in 1670 as Leicester Fields, which was named after the recently built Leicester House, itself named after Robert Sidney, 2nd Earl of Leicester.',
            'posted' => '2024/02/06 10:24:00',
            'latitude' => 51.5104,
            'longitude' => -0.130079,
            'category' => 'News and Events',
        ],
        [
            'user' => 'Smith',
            'title' => 'Elephant and Castle',
            'body' => '',
            'posted' => '2024/02/06 10:24:00',
            'latitude' => 51.4951,
            'longitude' => -0.100366,
            'category' => 'Businesses',
        ],
        [
            'user' => 'Oracle',
            'title' => 'US Embassy',
            'body' => '',
            'posted' => '2024/02/06 10:24:00',
            'latitude' => 51.4826,
            'longitude' => -0.132253,
            'category' => 'Community Initiatives',
        ],
        [
            'user' => 'Cypher',
            'title' => 'Belgrave Square',
            'body' => '',
            'posted' => '2024/02/06 10:24:00',
            'latitude' => 51.4992,
            'longitude' => -0.153593,
            'category' => 'Sports and Recreation',
        ],
        [
            'user' => 'Architect',
            'title' => 'Temple Garden',
            'body' => '',
            'posted' => '2024/02/06 10:24:00',
            'latitude' => 51.5119,
            'longitude' => -0.109660,
            'category' => 'Sightseeing',
        ],
        [
            'user' => 'Neo',
            'title' => 'Westminster Bridge',
            'body' => 'Westminster Bridge is a road-and-foot-traffic bridge over the River Thames in London, linking Westminster on the west side and Lambeth on the east side.',
            'posted' => '2024/02/06 10:24:00',
            'latitude' => 51.5009,
            'longitude' => -0.121807,
            'category' => 'News and Events',
        ],
    ];

    public function load(ObjectManager $manager): void
    {
        $index = 0;
        foreach (self::POSTS as $post) {
            $postEntity = new Post();
            $postEntity
                ->setUser($this->getReference('user/' . strtolower($post['user']), User::class))
                ->setTitle($post['title'])
                ->setBody($post['body'])
                ->setLatitude($post['latitude'])
                ->setLongitude($post['longitude'])
                ->setPosted(\DateTimeImmutable::createFromFormat(self::DATETIME_FORMAT, $post['posted']))
                ->setCategory($this->getReference('category/' . $post['category'], Category::class))
            ;

            $manager->persist($postEntity);

            $this->addReference('post/' . ++$index, $postEntity);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
        ];
    }
}
