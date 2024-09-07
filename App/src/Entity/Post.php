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

    /**
     * @var Collection<int, PostAttachment>
     */
    #[ORM\OneToMany(targetEntity: PostAttachment::class, mappedBy: 'post')]
    private Collection $attachments;

    #[ORM\ManyToOne(inversedBy: 'posts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Category $category = null;

    #[ORM\Column]
    private ?bool $removed = false;

    #[ORM\Column(nullable: true)]
    private ?float $latitude = null;

    #[ORM\Column(nullable: true)]
    private ?float $longitude = null;

    private ?float $distanceMiles;

    public function __construct()
    {
        $this->hearts = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->attachments = new ArrayCollection();
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

    public function getDistanceMiles(): ?float
    {
        return $this->distanceMiles;
    }

    public function setDistanceMiles(?float $distanceMiles): static
    {
        $this->distanceMiles = $distanceMiles;

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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getLatLng(): ?string
    {
        if (is_null($this->latitude) || is_null($this->longitude)) {
            return null;
        }

        return floatval($this->latitude) . ',' . floatval($this->longitude);
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

    /**
     * @return Collection<int, PostAttachment>
     */
    public function getAttachments(): Collection
    {
        return $this->attachments;
    }

    public function addAttachment(PostAttachment $attachment): static
    {
        if (!$this->attachments->contains($attachment)) {
            $this->attachments->add($attachment);
            $attachment->setPost($this);
        }

        return $this;
    }

    public function removeAttachment(PostAttachment $attachment): static
    {
        if ($this->attachments->removeElement($attachment)) {
            // set the owning side to null (unless already changed)
            if ($attachment->getPost() === $this) {
                $attachment->setPost(null);
            }
        }

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function isRemoved(): ?bool
    {
        return $this->removed;
    }

    public function setRemoved(bool $removed): static
    {
        $this->removed = $removed;

        return $this;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(?float $latitude): static
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(?float $longitude): static
    {
        $this->longitude = $longitude;

        return $this;
    }
}
