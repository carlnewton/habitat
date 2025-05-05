<?php

namespace App\DataFixtures\Users;

class CypherFixture extends AbstractUserFixture
{
    public const USERS = [
        [
            'username' => 'Cypher',
            'email' => 'cypher@example.com',
            'created' => '2024/03/03 03:03:03',
        ],
    ];

    public static function getGroups(): array
    {
        return array_merge(parent::getGroups(), ['cypher']);
    }
}
