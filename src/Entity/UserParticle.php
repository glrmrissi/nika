<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use App\Repository\UserParticleRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserParticleRepository::class)]
#[ORM\Table(name: 'user_particle')]
class UserParticle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?GrammarParticle $particle = null;

    #[ORM\Column(type: Types::FLOAT)]
    private float $easeFactor = 2.5;

    #[ORM\Column(name: "interval_value")]
    private int $interval = 0;

    #[ORM\Column]
    private int $repetitions = 0;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $nextReviewAt = null;

    #[ORM\Column]
    private bool $isComplete = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->nextReviewAt = new \DateTime();
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }
    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): self { $this->user = $user; return $this; }
    public function getParticle(): ?GrammarParticle { return $this->particle; }
    public function setParticle(?GrammarParticle $particle): self { $this->particle = $particle; return $this; }
    public function getEaseFactor(): float { return $this->easeFactor; }
    public function setEaseFactor(float $easeFactor): self { $this->easeFactor = $easeFactor; return $this; }
    public function getInterval(): int { return $this->interval; }
    public function setInterval(int $interval): self { $this->interval = $interval; return $this; }
    public function getRepetitions(): int { return $this->repetitions; }
    public function setRepetitions(int $repetitions): self { $this->repetitions = $repetitions; return $this; }
    public function getNextReviewAt(): ?\DateTimeInterface { return $this->nextReviewAt; }
    public function setNextReviewAt(\DateTimeInterface $nextReviewAt): self { $this->nextReviewAt = $nextReviewAt; return $this; }
    public function isComplete(): bool { return $this->isComplete; }
    public function setIsComplete(bool $isComplete): self { $this->isComplete = $isComplete; return $this; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(\DateTimeInterface $createdAt): self { $this->createdAt = $createdAt; return $this; }
}
