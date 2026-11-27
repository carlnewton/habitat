<?php

namespace App\Entity;

use App\Repository\EmailRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EmailRepository::class)]
class Email
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $toEmailAddress = null;

    #[ORM\Column(length: 255)]
    private ?string $fromEmailAddress = null;

    #[ORM\Column(length: 255)]
    private ?string $subject = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $body = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $created = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getToEmailAddress(): ?string
    {
        return $this->toEmailAddress;
    }

    public function setToEmailAddress(string $toEmailAddress): static
    {
        $this->toEmailAddress = $toEmailAddress;

        return $this;
    }

    public function getFromEmailAddress(): ?string
    {
        return $this->fromEmailAddress;
    }

    public function setFromEmailAddress(string $fromEmailAddress): static
    {
        $this->fromEmailAddress = $fromEmailAddress;

        return $this;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): static
    {
        $this->subject = $subject;

        return $this;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(string $body): static
    {
        $this->body = $body;

        return $this;
    }

    public function getCreated(): ?\DateTimeImmutable
    {
        return $this->created;
    }

    public function setCreated(\DateTimeImmutable $created): static
    {
        $this->created = $created;

        return $this;
    }
}
