<?php

namespace App\DataFixtures\Users;

class SeraphFixture extends AbstractUserFixture
{
    public const USERS = [
        [
            'username' => 'Seraph',
            'email' => 'seraph@example.com',
            'created' => '2024/03/03 03:03:03',
        ],
    ];

    public static function getGroups(): array
    {
        return array_merge(parent::getGroups(), ['seraph']);
    }
}
