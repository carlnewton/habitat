<?php

namespace App\Entity;

use App\Repository\UserFreezeLogRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserFreezeLogRepository::class)]
class UserFreezeLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'freezeLogs')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $freeze_date = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $unfreeze_date = null;

    #[ORM\Column(length: 255)]
    private ?string $reason = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getFreezeDate(): ?\DateTimeImmutable
    {
        return $this->freeze_date;
    }

    public function setFreezeDate(\DateTimeImmutable $freeze_date): static
    {
        $this->freeze_date = $freeze_date;

        return $this;
    }

    public function getUnfreezeDate(): ?\DateTimeImmutable
    {
        return $this->unfreeze_date;
    }

    public function setUnfreezeDate(\DateTimeImmutable $unfreeze_date): static
    {
        $this->unfreeze_date = $unfreeze_date;

        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(string $reason): static
    {
        $this->reason = $reason;

        return $this;
    }
}
