<?php

namespace App\Entity;

use App\Repository\SidebarContentRepository;
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
        return $this->content;
    }

    public function setContent(?string $content): static
    {
        $this->content = $this->stripTags($content);

        return $this;
    }

    public static function stripTags(?string $content): string
    {
        if (is_null($content)) {
            return '';
        }

        $content = trim($content);
        if ('' === $content || '<p></p>' === $content) {
            return '';
        }

        $allowedTags = '';
        foreach (self::ALLOWED_TAGS as $allowedTag) {
            $allowedTags .= '<' . $allowedTag . '>';
        }

        return strip_tags($content, $allowedTags);
    }
}
