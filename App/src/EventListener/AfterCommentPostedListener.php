<?php

namespace App\EventListener;

use App\Entity\Notification;
use App\Entity\NotificationTypesEnum;
use App\Event\AfterCommentPostedEvent;
use Doctrine\ORM\EntityManagerInterface;

class AfterCommentPostedListener
{
    public function __construct(
        protected EntityManagerInterface $entityManager,
    ) {
    }

    public function afterCommentPosted(AfterCommentPostedEvent $event): void
    {
        $comment = $event->getComment();
        if ($comment->getUser()->getId() === $comment->getPost()->getUser()->getId()) {
            return;
        }

        $notificationsRepository = $this->entityManager->getRepository(Notification::class);
        $notification = $notificationsRepository->findOneBy([
            'type' => NotificationTypesEnum::NEW_POST_COMMENTS->value,
            'user' => $comment->getPost()->getUser()->getId(),
            'post' => $comment->getPost(),
        ]);

        if (is_null($notification)) {
            $notification = new Notification();

            $notification
                ->setType(NotificationTypesEnum::NEW_POST_COMMENTS)
                ->setUser($comment->getPost()->getUser())
                ->setPost($comment->getPost())
                ->setDate(new \DateTimeImmutable())
                ->setData([
                    'count' => 1,
                ]);
        } else {
            $notificationData = $notification->getData();
            ++$notificationData['count'];
            $notification
                ->setData($notificationData)
                ->setDate(new \DateTimeImmutable())
            ;
        }

        $this->entityManager->persist($notification);
        $this->entityManager->flush();
    }
}
