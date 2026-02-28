<?php

namespace App\Twig\Components;

use App\Entity\SidebarContent;
use App\Repository\SidebarContentRepository;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class Sidebar
{
    public function __construct(
        private SidebarContentRepository $sidebarContentRepository,
    ) {
    }

    public function getSidebarContent(): string
    {
        $sidebarContent = $this->sidebarContentRepository->findOneBy(['id' => 1]);

        if (is_null($sidebarContent)) {
            return '';
        }

        $content = $sidebarContent->getContent();
        if (is_null($content)) {
            return '';
        }

        if (SidebarContent::stripTags($content) !== $content) {
            return '';
        }

        return $content;
    }
}
