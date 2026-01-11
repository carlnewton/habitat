<?php

namespace App\DataFixtures\Users;

class NeoFixture extends AbstractUserFixture
{
    public const USERS = [
        [
            'username' => 'Neo',
            'email' => 'neo@example.com',
            'created' => '2024/01/01 01:01:01',
        ],
    ];

    public static function getGroups(): array
    {
        return array_merge(parent::getGroups(), ['neo']);
    }
}
