<?php

namespace App\DataFixtures\Users;

class MerovingianFixture extends AbstractUserFixture
{
    public const USERS = [
        [
            'username' => 'Merovingian',
            'email' => 'merovingian@example.com',
            'created' => '2024/03/03 03:03:03',
            'roles' => ['ROLE_MODERATOR'],
        ],
    ];

    public static function getGroups(): array
    {
        return array_merge(parent::getGroups(), ['merovingian']);
    }
}
