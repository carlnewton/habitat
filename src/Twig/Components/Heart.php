<?php

namespace App\Twig\Components;

use App\Entity\Post;
use App\Entity\User;
use App\Repository\HeartRepository;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class Heart
{
    public function __construct(
        private HeartRepository $heartRepository,
    ) {
    }

    public function getHearted(Post $post, ?User $user): bool
    {
        if (is_null($user)) {
            return false;
        }

        $heart = $this->heartRepository->findOneBy(
            [
                'user' => $user,
                'post' => $post,
            ]
        );

        if (is_null($heart)) {
            return false;
        }

        return true;
    }

    public function getHeartsCount(Post $post): int
    {
        return $this->heartRepository->count(['post' => $post]);
    }
}
