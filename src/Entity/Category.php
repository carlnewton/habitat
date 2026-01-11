<?php

namespace App\Entity;

use App\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;
    /**
     * @var Collection<int, Post>
     */
    #[ORM\OneToMany(targetEntity: Post::class, mappedBy: 'category')]
    private Collection $posts;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $location = null;

    #[ORM\Column]
    private ?bool $allow_posting = null;

    #[ORM\Column]
    private ?int $weight = 0;

    /**
     * @var Collection<int, UserHiddenCategory>
     */
    #[ORM\OneToMany(targetEntity: UserHiddenCategory::class, mappedBy: 'category', orphanRemoval: true)]
    private Collection $userHiddenCategories;

    public function __construct()
    {
        $this->posts = new ArrayCollection();
        $this->userHiddenCategories = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

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
            $post->setCategory($this);
        }

        return $this;
    }

    public function removePost(Post $post): static
    {
        if ($this->posts->removeElement($post)) {
            // set the owning side to null (unless already changed)
            if ($post->getCategory() === $this) {
                $post->setCategory(null);
            }
        }

        return $this;
    }

    public function getLocation(): ?CategoryLocationOptionsEnum
    {
        return CategoryLocationOptionsEnum::from($this->location);
    }

    public function setLocation(CategoryLocationOptionsEnum $location): static
    {
        $this->location = $location->value;

        return $this;
    }

    public function isAllowPosting(): ?bool
    {
        return $this->allow_posting;
    }

    public function setAllowPosting(bool $allow_posting): static
    {
        $this->allow_posting = $allow_posting;

        return $this;
    }

    public function getWeight(): ?int
    {
        return $this->weight;
    }

    public function setWeight(int $weight): static
    {
        $this->weight = $weight;

        return $this;
    }

    /**
     * @return Collection<int, UserHiddenCategory>
     */
    public function getUserHiddenCategories(): Collection
    {
        return $this->userHiddenCategories;
    }

    public function addUserHiddenCategory(UserHiddenCategory $userHiddenCategory): static
    {
        if (!$this->userHiddenCategories->contains($userHiddenCategory)) {
            $this->userHiddenCategories->add($userHiddenCategory);
            $userHiddenCategory->setCategory($this);
        }

        return $this;
    }

    public function removeUserHiddenCategory(UserHiddenCategory $userHiddenCategory): static
    {
        if ($this->userHiddenCategories->removeElement($userHiddenCategory)) {
            // set the owning side to null (unless already changed)
            if ($userHiddenCategory->getCategory() === $this) {
                $userHiddenCategory->setCategory(null);
            }
        }

        return $this;
    }
}
