<?php

namespace App\Entity;

use App\Repository\RegistrationQuestionAnswerRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RegistrationQuestionAnswerRepository::class)]
class RegistrationQuestionAnswer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $answer = null;

    #[ORM\ManyToOne(inversedBy: 'answers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?RegistrationQuestion $question = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAnswer(): ?string
    {
        return $this->answer;
    }

    public function setAnswer(string $answer): static
    {
        $this->answer = $answer;

        return $this;
    }

    public function getQuestion(): ?RegistrationQuestion
    {
        return $this->question;
    }

    public function setQuestion(?RegistrationQuestion $question): static
    {
        $this->question = $question;

        return $this;
    }
}
