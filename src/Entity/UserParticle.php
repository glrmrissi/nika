<?php

declare(strict_types=1);

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

    #[ORM\Column(type: Types::FLOAT)]
    private float $stability = 0;

    #[ORM\Column(type: Types::FLOAT)]
    private float $difficulty = 0;

    #[ORM\Column]
    private int $state = 0;

    #[ORM\Column]
    private int $lapses = 0;

    #[ORM\Column]
    private int $step = 0;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastReviewedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $utc = new \DateTimeZone('UTC');
        $this->nextReviewAt = new \DateTime('now', $utc);
        $this->createdAt = new \DateTime('now', $utc);
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
    public function getStability(): float { return $this->stability; }
    public function setStability(float $stability): self { $this->stability = $stability; return $this; }
    public function getDifficulty(): float { return $this->difficulty; }
    public function setDifficulty(float $difficulty): self { $this->difficulty = $difficulty; return $this; }
    public function getState(): int { return $this->state; }
    public function setState(int $state): self { $this->state = $state; return $this; }
    public function getLapses(): int { return $this->lapses; }
    public function setLapses(int $lapses): self { $this->lapses = $lapses; return $this; }
    public function getStep(): int { return $this->step; }
    public function setStep(int $step): self { $this->step = $step; return $this; }
    public function getLastReviewedAt(): ?\DateTimeInterface { return $this->lastReviewedAt; }
    public function setLastReviewedAt(?\DateTimeInterface $lastReviewedAt): self { $this->lastReviewedAt = $lastReviewedAt; return $this; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(\DateTimeInterface $createdAt): self { $this->createdAt = $createdAt; return $this; }
}
