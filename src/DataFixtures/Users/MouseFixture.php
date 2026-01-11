<?php

namespace App\DataFixtures\Users;

class MouseFixture extends AbstractUserFixture
{
    public const USERS = [
        [
            'username' => 'Mouse',
            'email' => 'mouse@example.com',
            'created' => '2024/03/03 03:03:03',
        ],
    ];

    public static function getGroups(): array
    {
        return array_merge(parent::getGroups(), ['mouse']);
    }
}
