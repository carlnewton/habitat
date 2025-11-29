<?php

namespace App\DataFixtures\Users;

class SmithFixture extends AbstractUserFixture
{
    public const USERS = [
        [
            'username' => 'Smith',
            'email' => 'smith@example.com',
            'created' => '2023/01/02 03:04:05',
            'roles' => ['ROLE_MODERATOR'],
        ],
    ];

    public static function getGroups(): array
    {
        return array_merge(parent::getGroups(), ['smith']);
    }
}
