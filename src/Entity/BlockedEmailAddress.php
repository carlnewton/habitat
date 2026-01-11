<?php

namespace App\Entity;

use App\Repository\BlockedEmailAddressRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BlockedEmailAddressRepository::class)]
class BlockedEmailAddress
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $email_address = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmailAddress(): ?string
    {
        return $this->email_address;
    }

    public function setEmailAddress(string $email_address): static
    {
        $this->email_address = $email_address;

        return $this;
    }
}
