<?php

namespace App\Entity;

enum NotificationTypesEnum: int
{
    case NEW_POST_COMMENTS = 0;

    public function message(): string
    {
        return match ($this) {
            self::NEW_POST_COMMENTS => 'new_post_comments',
        };
    }
}
