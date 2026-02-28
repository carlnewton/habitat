<?php

namespace App\Entity;

use App\Repository\ReportRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReportRepository::class)]
class Report
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'reports')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $reported_by = null;

    #[ORM\ManyToOne(inversedBy: 'reports')]
    private ?Post $post = null;

    #[ORM\ManyToOne(inversedBy: 'reports')]
    private ?Comment $comment = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $reported_date = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $reason = null;

    #[ORM\Column(length: 255)]
    private ?string $type = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReportedBy(): ?User
    {
        return $this->reported_by;
    }

    public function setReportedBy(?User $reported_by): static
    {
        $this->reported_by = $reported_by;

        return $this;
    }

    public function getPost(): ?Post
    {
        return $this->post;
    }

    public function setPost(?Post $post): static
    {
        $this->post = $post;

        return $this;
    }

    public function getComment(): ?Comment
    {
        return $this->comment;
    }

    public function setComment(?Comment $comment): static
    {
        $this->comment = $comment;

        return $this;
    }

    public function getReportedDate(): ?\DateTimeImmutable
    {
        return $this->reported_date;
    }

    public function setReportedDate(\DateTimeImmutable $reported_date): static
    {
        $this->reported_date = $reported_date;

        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(?string $reason): static
    {
        $this->reason = $reason;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }
}
