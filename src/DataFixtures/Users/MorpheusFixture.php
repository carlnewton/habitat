<?php

namespace App\DataFixtures\Users;

class MorpheusFixture extends AbstractUserFixture
{
    public const USERS = [
        [
            'username' => 'Morpheus',
            'email' => 'morpheus@example.com',
            'created' => '2024/02/02 02:02:02',
        ],
    ];

    public static function getGroups(): array
    {
        return array_merge(parent::getGroups(), ['morpheus']);
    }
}
