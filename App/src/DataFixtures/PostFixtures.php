<?php

namespace App\DataFixtures;

use App\Entity\Post;
use DateTimeImmutable;
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
            'location' => '51.5303,-0.124063',
        ],
        [
            'user' => 'Trinity',
            'title' => 'Queen Mary\'s Garden',
            'body' => 'Picturesque garden created in the 1930s, showcasing 12,000 rose bushes on landscaped grounds.',
            'posted' => '2024/02/02 10:24:00',
            'location' => '51.5276,-0.153726',
        ],
        [
            'user' => 'Neo',
            'title' => 'Hyde Park',
            'body' => 'Hyde Park is a 350-acre, historic Grade I-listed urban park in Westminster.',
            'posted' => '2024/02/03 10:24:00',
            'location' => '51.5078,-0.162235',
        ],
        [
            'user' => 'Tank',
            'title' => 'Southwark Bridge',
            'body' => 'Southwark Bridge is an arch bridge in London, for traffic linking the district of Southwark and the City across the River Thames.',
            'posted' => '2024/02/04 10:24:00',
            'location' => '51.5093,-0.0938010',
        ],
        [
            'user' => 'Switch',
            'title' => 'Kia Oval',
            'body' => 'The Oval, currently named for sponsorship reasons as the Kia Oval, is an international cricket ground in Kennington, located in the borough of Lambeth, in south London. The Oval has been the home ground of Surrey County Cricket Club since it was opened in 1845.',
            'posted' => '2024/02/05 10:24:00',
            'location' => '51.4839,-0.115048',
        ],
        [
            'user' => 'Cypher',
            'title' => 'Kensington Cross',
            'body' => 'Kennington Cross is a locality in the London Borough of Lambeth. It is at the junction of two major roads, Kennington Lane that links Vauxhall Cross with the Elephant and Castle and Kennington Road that runs from Waterloo to Kennington Park.',
            'posted' => '2024/02/06 10:24:00',
            'location' => '51.4887,-0.111094',
        ],
        [
            'user' => 'Apoc',
            'title' => 'Millbank Millenium Pier',
            'body' => 'Millbank Pier is a pier on the west bank of the River Thames, in London, United Kingdom. It is served by boats operated by Uber Boat by Thames Clippers under licence from London River Services and is situated between Lambeth Bridge and Vauxhall Bridge on Millbank.',
            'posted' => '2024/02/06 10:24:00',
            'location' => '51.4918,-0.124744',
        ],
        [
            'user' => 'Neo',
            'title' => 'Mayfair',
            'body' => 'Bordering leafy Hyde Park, Mayfair is an upscale district of elegant Georgian townhouses, exclusive hotels, and gourmet restaurants. Its world-famous retailers include bespoke tailors on Savile Row and designer fashions on Bond Street. Shoppers also head to high-end Burlington Arcade and Shepherd Market, a cluster of independent boutiques and traditional pubs.',
            'posted' => '2024/02/06 10:24:00',
            'location' => '51.5112,-0.147051',
        ],
        [
            'user' => 'Trinity',
            'title' => 'Soho',
            'body' => 'The energetic streets of Soho, in the West End, feature a variety of dining, nightlife, and shopping options. Dean, Frith, Beak, and Old Compton streets are the epicentre of activity day and night, and long-running Ronnie Scott\'s Jazz Club is also here.',
            'posted' => '2024/02/06 10:24:00',
            'location' => '51.5145,-0.134524',
        ],
        [
            'user' => 'Morpheus',
            'title' => 'Covent Garden',
            'body' => 'A shopping and entertainment hub in London\'s West End, Covent Garden centres on the elegant, car-free Piazza, home to fashion stores, craft stalls at the Apple Market, and the Royal Opera House. Street entertainers perform by 17th-century St. Paul’s Church, and the London Transport Museum houses vintage vehicles.',
            'posted' => '2024/02/06 10:24:00',
            'location' => '51.5126,-0.122683',
        ],
        [
            'user' => 'Mouse',
            'title' => 'Leicester Square',
            'body' => 'Leicester Square is a pedestrianised square in the West End of London, England. It was laid out in 1670 as Leicester Fields, which was named after the recently built Leicester House, itself named after Robert Sidney, 2nd Earl of Leicester.',
            'posted' => '2024/02/06 10:24:00',
            'location' => '51.5104,-0.130079',
        ],
        [
            'user' => 'Smith',
            'title' => 'Elephant and Castle',
            'body' => '',
            'posted' => '2024/02/06 10:24:00',
            'location' => '51.4951,-0.100366',
        ],
        [
            'user' => 'Oracle',
            'title' => 'US Embassy',
            'body' => '',
            'posted' => '2024/02/06 10:24:00',
            'location' => '51.4826,-0.132253',
        ],
        [
            'user' => 'Cypher',
            'title' => 'Belgrave Square',
            'body' => '',
            'posted' => '2024/02/06 10:24:00',
            'location' => '51.4992,-0.153593',
        ],
        [
            'user' => 'Architect',
            'title' => 'Temple Garden',
            'body' => '',
            'posted' => '2024/02/06 10:24:00',
            'location' => '51.5119,-0.109660',
        ],
        [
            'user' => 'Neo',
            'title' => 'Westminster Bridge',
            'body' => 'Westminster Bridge is a road-and-foot-traffic bridge over the River Thames in London, linking Westminster on the west side and Lambeth on the east side.',
            'posted' => '2024/02/06 10:24:00',
            'location' => '51.5009,-0.121807',
        ],
    ];

    public function load(ObjectManager $manager)
    {
        $index = 0;
        foreach (self::POSTS as $post) {
            $postEntity = new Post();
            $postEntity
                ->setUser($this->getReference('user/' . strtolower($post['user'])))
                ->setTitle($post['title'])
                ->setBody($post['body'])
                ->setLocation($post['location'])
                ->setPosted(DateTimeImmutable::createFromFormat(self::DATETIME_FORMAT, $post['posted']))
            ;

            $manager->persist($postEntity);

            $this->addReference('post/' . ++$index, $postEntity);
        }

        $manager->flush();
    }

    public function getDependencies()
    {
        return [
            UserFixtures::class,
        ];
    }
}
