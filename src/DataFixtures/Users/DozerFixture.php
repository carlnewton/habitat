<?php

namespace App\DataFixtures\Users;

class DozerFixture extends AbstractUserFixture
{
    public const USERS = [
        [
            'username' => 'Dozer',
            'email' => 'dozer@example.com',
            'created' => '2024/03/03 03:03:03',
        ],
    ];

    public static function getGroups(): array
    {
        return array_merge(parent::getGroups(), ['dozer']);
    }
}
