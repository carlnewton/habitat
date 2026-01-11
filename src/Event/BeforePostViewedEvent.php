<?php

namespace App\Event;

use App\Entity\Post;
use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

class BeforePostViewedEvent extends Event
{
    public const NAME = 'before.post.viewed';

    private Post $post;
    private ?User $user;

    public function __construct(Post $post, ?User $user)
    {
        $this->post = $post;
        $this->user = $user;
    }

    public function getPost(): Post
    {
        return $this->post;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }
}
