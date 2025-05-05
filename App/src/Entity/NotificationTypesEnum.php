<?php

namespace App\Entity;

use App\DTO\NotificationTypeNewPostComment;

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
