<?php

namespace App\EventListener;

use App\Entity\Notification;
use App\Event\BeforePostViewedEvent;
use Doctrine\ORM\EntityManagerInterface;

class BeforePostViewedListener
{
    public function __construct(
        protected EntityManagerInterface $entityManager,
    ) {
    }

    public function beforePostViewed(BeforePostViewedEvent $event): void
    {
        $post = $event->getPost();
        $user = $event->getUser();

        if (is_null($user)) {
            return;
        }

        if ($post->getUser()->getId() !== $user->getId()) {
            return;
        }

        $notificationsRepository = $this->entityManager->getRepository(Notification::class);
        $notifications = $notificationsRepository->findBy([
            'user' => $user->getId(),
            'post' => $post->getId(),
        ]);

        foreach ($notifications as $notification) {
            $this->entityManager->remove($notification);
        }
        $this->entityManager->flush();
    }
}
