<?php

namespace App\Entity;

use App\Repository\HeartRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HeartRepository::class)]
class Heart
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'hearts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'hearts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Post $post = null;

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

    public function getPost(): ?Post
    {
        return $this->post;
    }

    public function setPost(?Post $post): static
    {
        $this->post = $post;

        return $this;
    }
}
