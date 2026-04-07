<?php

namespace App\Entity;

use App\Repository\SidebarContentRepository;
use App\Utilities\UserSubmittedHTML;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SidebarContentRepository::class)]
class SidebarContent
{
    public const ALLOWED_TAGS = ['p', 'h3', 'ul', 'li', 'a'];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $content = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContent(): ?string
    {
        if (!UserSubmittedHTML::isClean($this->content, self::ALLOWED_TAGS)) {
            return null;
        }

        return $this->content;
    }

    public function setContent(?string $content): static
    {
        $this->content = UserSubmittedHTML::clean($content, self::ALLOWED_TAGS);

        return $this;
    }
}
