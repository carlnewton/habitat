<?php

namespace App\DataFixtures\Users;

class TrinityFixture extends AbstractUserFixture
{
    public const USERS = [
        [
            'username' => 'Trinity',
            'email' => 'trinity@example.com',
            'created' => '2024/03/03 03:03:03',
        ],
    ];

    public static function getGroups(): array
    {
        return array_merge(parent::getGroups(), ['trinity']);
    }
}
