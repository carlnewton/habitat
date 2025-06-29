<?php

namespace App\Twig\Components;

use App\Entity\Announcement as AnnouncementEntity;
use App\Repository\AnnouncementRepository;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class Announcement
{
    private ?AnnouncementEntity $announcement = null;

    public function __construct(
        private AnnouncementRepository $announcementRepository,
    ) {
        $announcement = $this->announcementRepository->findOneBy(['id' => 1]);

        if (is_null($announcement)) {
            return;
        }

        $content = $announcement->getContent();
        if (AnnouncementEntity::stripTags($content) !== $content) {
            return;
        }

        if (empty($announcement->getTitle()) && empty($announcement->getContent())) {
            return;
        }

        if (!empty($announcement->getShowDate()) && $announcement->getShowDate() > new \DateTime()) {
            return;
        }

        if (!empty($announcement->getHideDate()) && $announcement->getHideDate() <= new \DateTime()) {
            return;
        }

        $this->announcement = $announcement;
    }

    public function getAnnouncement(): ?AnnouncementEntity
    {
        return $this->announcement;
    }

    public function isCollapsed(): bool
    {
        if (!$this->getAnnouncement()) {
            return false;
        }

        if (empty($this->getAnnouncement()->getTitle()) || empty($this->getAnnouncement()->getContent())) {
            return false;
        }

        return $this->getAnnouncement()->isCollapse();
    }
}
