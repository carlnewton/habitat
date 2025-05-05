<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_USERNAME', fields: ['username'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const USERNAME_MIN_LENGTH = 3;
    public const USERNAME_MAX_LENGTH = 30;
    public const PASSWORD_MIN_LENGTH = 8;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 30)]
    #[Assert\NotBlank]
    #[Assert\Length(
        min: self::USERNAME_MIN_LENGTH,
        max: self::USERNAME_MAX_LENGTH,
        minMessage: 'Your username must be at least {{ limit }} characters long',
        maxMessage: 'Your username cannot be longer than {{ limit }} characters',
    )]
    #[Assert\NoSuspiciousCharacters]
    private ?string $username = null;
    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    #[Assert\NotBlank]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    #[Assert\Email(
        message: '{{ value }} is not a valid email address.',
    )]
    private ?string $email_address = null;

    /**
     * @var Collection<int, Post>
     */
    #[ORM\OneToMany(targetEntity: Post::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $posts;

    /**
     * @var Collection<int, Heart>
     */
    #[ORM\OneToMany(targetEntity: Heart::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $hearts;

    /**
     * @var Collection<int, Comment>
     */
    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $comments;

    /**
     * @var Collection<int, PostAttachment>
     */
    #[ORM\OneToMany(targetEntity: PostAttachment::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $postAttachments;

    #[ORM\Column]
    private ?\DateTimeImmutable $created = null;

    #[ORM\Column]
    private ?bool $email_verified = false;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $email_verification_string = null;

    /**
     * @var Collection<int, UserHiddenCategory>
     */
    #[ORM\OneToMany(targetEntity: UserHiddenCategory::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $hiddenCategories;

    /**
     * @var Collection<int, UserSettings>
     */
    #[ORM\OneToMany(targetEntity: UserSettings::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $settings;

    /**
     * @var Collection<int, Report>
     */
    #[ORM\OneToMany(targetEntity: Report::class, mappedBy: 'reported_by', orphanRemoval: true)]
    private Collection $reports;

    /**
     * @var Collection<int, Notification>
     */
    #[ORM\OneToMany(targetEntity: Notification::class, mappedBy: 'user', orphanRemoval: true)]
    #[ORM\OrderBy(['date' => 'DESC'])]
    private Collection $notifications;

    public function __construct()
    {
        $this->posts = new ArrayCollection();
        $this->hearts = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->postAttachments = new ArrayCollection();
        $this->hiddenCategories = new ArrayCollection();
        $this->settings = new ArrayCollection();
        $this->reports = new ArrayCollection();
        $this->notifications = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
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

    /**
     * @return Collection<int, Post>
     */
    public function getPosts(): Collection
    {
        return $this->posts;
    }

    public function addPost(Post $post): static
    {
        if (!$this->posts->contains($post)) {
            $this->posts->add($post);
            $post->setUser($this);
        }

        return $this;
    }

    public function removePost(Post $post): static
    {
        if ($this->posts->removeElement($post)) {
            // set the owning side to null (unless already changed)
            if ($post->getUser() === $this) {
                $post->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Heart>
     */
    public function getHearts(): Collection
    {
        return $this->hearts;
    }

    public function addHeart(Heart $heart): static
    {
        if (!$this->hearts->contains($heart)) {
            $this->hearts->add($heart);
            $heart->setUser($this);
        }

        return $this;
    }

    public function removeHeart(Heart $heart): static
    {
        if ($this->hearts->removeElement($heart)) {
            // set the owning side to null (unless already changed)
            if ($heart->getUser() === $this) {
                $heart->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Comment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): static
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setUser($this);
        }

        return $this;
    }

    public function removeComment(Comment $comment): static
    {
        if ($this->comments->removeElement($comment)) {
            // set the owning side to null (unless already changed)
            if ($comment->getUser() === $this) {
                $comment->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, PostAttachment>
     */
    public function getPostAttachments(): Collection
    {
        return $this->postAttachments;
    }

    public function addPostAttachment(PostAttachment $postAttachment): static
    {
        if (!$this->postAttachments->contains($postAttachment)) {
            $this->postAttachments->add($postAttachment);
            $postAttachment->setUser($this);
        }

        return $this;
    }

    public function removePostAttachment(PostAttachment $postAttachment): static
    {
        if ($this->postAttachments->removeElement($postAttachment)) {
            // set the owning side to null (unless already changed)
            if ($postAttachment->getUser() === $this) {
                $postAttachment->setUser(null);
            }
        }

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

    public function isEmailVerified(): ?bool
    {
        return $this->email_verified;
    }

    public function setEmailVerified(bool $email_verified): static
    {
        $this->email_verified = $email_verified;

        return $this;
    }

    public function getEmailVerificationString(): ?string
    {
        return $this->email_verification_string;
    }

    public function setEmailVerificationString(?string $email_verification_string): static
    {
        $this->email_verification_string = $email_verification_string;

        return $this;
    }

    public function hasHiddenCategory(int $categoryId): bool
    {
        $hiddenCategories = $this->getHiddenCategories();

        foreach ($hiddenCategories as $hiddenCategory) {
            if ($hiddenCategory->getCategory()->getId() === $categoryId) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return Collection<int, UserHiddenCategory>
     */
    public function getHiddenCategories(): Collection
    {
        return $this->hiddenCategories;
    }

    public function addHiddenCategory(UserHiddenCategory $hiddenCategory): static
    {
        if (!$this->hiddenCategories->contains($hiddenCategory)) {
            $this->hiddenCategories->add($hiddenCategory);
            $hiddenCategory->setUser($this);
        }

        return $this;
    }

    public function removeHiddenCategory(UserHiddenCategory $hiddenCategory): static
    {
        if ($this->hiddenCategories->removeElement($hiddenCategory)) {
            // set the owning side to null (unless already changed)
            if ($hiddenCategory->getUser() === $this) {
                $hiddenCategory->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, UserSettings>
     */
    public function getSettings(): Collection
    {
        return $this->settings;
    }

    public function addSetting(UserSettings $setting): static
    {
        if (!$this->settings->contains($setting)) {
            $this->settings->add($setting);
            $setting->setUser($this);
        }

        return $this;
    }

    public function removeSetting(UserSettings $setting): static
    {
        if ($this->settings->removeElement($setting)) {
            // set the owning side to null (unless already changed)
            if ($setting->getUser() === $this) {
                $setting->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Report>
     */
    public function getReports(): Collection
    {
        return $this->reports;
    }

    public function addReport(Report $report): static
    {
        if (!$this->reports->contains($report)) {
            $this->reports->add($report);
            $report->setReportedBy($this);
        }

        return $this;
    }

    public function removeReport(Report $report): static
    {
        if ($this->reports->removeElement($report)) {
            // set the owning side to null (unless already changed)
            if ($report->getReportedBy() === $this) {
                $report->setReportedBy(null);
            }
        }

        return $this;
    }

    public function hasNotifications(): bool
    {
        return !$this->notifications->isEmpty();
    }

    /**
     * @return Collection<int, Notification>
     */
    public function getNotifications(): Collection
    {
        return $this->notifications;
    }

    public function addNotification(Notification $notification): static
    {
        if (!$this->notifications->contains($notification)) {
            $this->notifications->add($notification);
            $notification->setUser($this);
        }

        return $this;
    }

    public function removeNotification(Notification $notification): static
    {
        if ($this->notifications->removeElement($notification)) {
            // set the owning side to null (unless already changed)
            if ($notification->getUser() === $this) {
                $notification->setUser(null);
            }
        }

        return $this;
    }
}
