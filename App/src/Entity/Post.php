<?php

namespace App\Entity;

use App\Repository\PostRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PostRepository::class)]
class Post
{
    public const TITLE_MAX_LENGTH = 255;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: self::TITLE_MAX_LENGTH)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $body = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $location = null;

    #[ORM\ManyToOne(inversedBy: 'posts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $posted = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updated = null;

    /**
     * @var Collection<int, Heart>
     */
    #[ORM\OneToMany(targetEntity: Heart::class, mappedBy: 'post', orphanRemoval: true)]
    private Collection $hearts;

    /**
     * @var Collection<int, Comment>
     */
    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'post', orphanRemoval: true)]
    #[ORM\OrderBy(['posted' => 'DESC'])]
    private Collection $comments;

    private bool $currentUserHearted = false;

    public function __construct()
    {
        $this->hearts = new ArrayCollection();
        $this->comments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(?string $body): static
    {
        $this->body = $body;

        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): static
    {
        $this->location = $location;

        return $this;
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

    public function getPosted(): ?\DateTimeInterface
    {
        return $this->posted;
    }

    public function setPosted(\DateTimeInterface $posted): static
    {
        $this->posted = $posted;

        return $this;
    }

    public function getUpdated(): ?\DateTimeInterface
    {
        return $this->updated;
    }

    public function setUpdated(?\DateTimeInterface $updated): static
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * @return Collection<int, Heart>
     */
    public function getHearts(): Collection
    {
        return $this->hearts;
    }

    public function getHeartCount(): int
    {
        return $this->hearts->count();
    }

    public function addHeart(Heart $heart): static
    {
        if (!$this->hearts->contains($heart)) {
            $this->hearts->add($heart);
            $heart->setPost($this);
        }

        return $this;
    }

    public function removeHeart(Heart $heart): static
    {
        if ($this->hearts->removeElement($heart)) {
            // set the owning side to null (unless already changed)
            if ($heart->getPost() === $this) {
                $heart->setPost(null);
            }
        }

        return $this;
    }

    public function setCurrentUserHearted(bool $currentUserHearted): static
    {
        $this->currentUserHearted = $currentUserHearted;

        return $this;
    }

    public function getCurrentUserHearted(): bool
    {
        return $this->currentUserHearted;
    }

    /**
     * @return Collection<int, Comment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function getCommentCount(): int
    {
        return $this->comments->count();
    }

    public function addComment(Comment $comment): static
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setPost($this);
        }

        return $this;
    }

    public function removeComment(Comment $comment): static
    {
        if ($this->comments->removeElement($comment)) {
            // set the owning side to null (unless already changed)
            if ($comment->getPost() === $this) {
                $comment->setPost(null);
            }
        }

        return $this;
    }
}
