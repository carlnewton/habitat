<?php

namespace App\DataFixtures\Users;

class ApocFixture extends AbstractUserFixture
{
    public const USERS = [
        [
            'username' => 'Apoc',
            'email' => 'apoc@example.com',
            'created' => '2024/03/03 03:03:03',
        ],
    ];

    public static function getGroups(): array
    {
        return array_merge(parent::getGroups(), ['apoc']);
    }
}
