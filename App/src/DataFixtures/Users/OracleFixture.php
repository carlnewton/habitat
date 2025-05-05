<?php

namespace App\DataFixtures\Users;

class OracleFixture extends AbstractUserFixture
{
    public const USERS = [
        [
            'username' => 'Oracle',
            'email' => 'oracle@example.com',
            'created' => '2024/03/03 03:03:03',
        ],
    ];

    public static function getGroups(): array
    {
        return array_merge(parent::getGroups(), ['oracle']);
    }
}
