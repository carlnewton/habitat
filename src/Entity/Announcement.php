<?php

namespace App\Entity;

use App\Repository\AnnouncementRepository;
use App\Utilities\UserSubmittedHTML;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AnnouncementRepository::class)]
class Announcement
{
    public const ALLOWED_TAGS = ['p', 'ul', 'li', 'a'];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $content = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $show_date = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $hide_date = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $type = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $title = null;

    #[ORM\Column]
    private ?bool $collapse = null;

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

    public function getShowDate(): ?\DateTimeInterface
    {
        return $this->show_date;
    }

    public function setShowDate(?\DateTimeInterface $show_date): static
    {
        $this->show_date = $show_date;

        return $this;
    }

    public function getHideDate(): ?\DateTimeInterface
    {
        return $this->hide_date;
    }

    public function setHideDate(?\DateTimeInterface $hide_date): static
    {
        $this->hide_date = $hide_date;

        return $this;
    }

    public function getType(): ?AnnouncementTypesEnum
    {
        return AnnouncementTypesEnum::from($this->type);
    }

    public function setType(AnnouncementTypesEnum $type): static
    {
        $this->type = $type->value;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function isCollapse(): ?bool
    {
        return $this->collapse;
    }

    public function setCollapse(bool $collapse): static
    {
        $this->collapse = $collapse;

        return $this;
    }
}
