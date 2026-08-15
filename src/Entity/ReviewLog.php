<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ReviewLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReviewLogRepository::class)]
#[ORM\Table(name: 'review_log')]
class ReviewLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;
 
    #[ORM\ManyToOne(inversedBy: 'reviewLogs')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Kanji $kanji = null;

    #[ORM\ManyToOne(inversedBy: 'reviewLogs')]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $reviewUser = null;

    #[ORM\Column]
    private ?int $quality = null;

    #[ORM\Column(nullable: true)]
    private ?int $rating = null;

    #[ORM\Column(nullable: true)]
    private ?int $cardState = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $stability = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $difficulty = null;

    #[ORM\Column(nullable: true)]
    private ?int $scheduledDays = null;

    #[ORM\Column(nullable: true)]
    private ?int $elapsedDays = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $reviewedAt = null;

    public function __construct()
    {
        $this->reviewedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    public function getId(): ?int { return $this->id; }
    public function getKanji(): ?Kanji { return $this->kanji; }
    public function setKanji(?Kanji $kanji): self { $this->kanji = $kanji; return $this; }
    public function getReviewUser(): ?User { return $this->reviewUser; }
    public function setReviewUser(?User $reviewUser): self { $this->reviewUser = $reviewUser; return $this; }
    public function getQuality(): ?int { return $this->quality; }
    public function setQuality(int $quality): self { $this->quality = $quality; return $this; }
    public function getRating(): ?int { return $this->rating; }
    public function setRating(?int $rating): self { $this->rating = $rating; return $this; }
    public function getCardState(): ?int { return $this->cardState; }
    public function setCardState(?int $cardState): self { $this->cardState = $cardState; return $this; }
    public function getStability(): ?float { return $this->stability; }
    public function setStability(?float $stability): self { $this->stability = $stability; return $this; }
    public function getDifficulty(): ?float { return $this->difficulty; }
    public function setDifficulty(?float $difficulty): self { $this->difficulty = $difficulty; return $this; }
    public function getScheduledDays(): ?int { return $this->scheduledDays; }
    public function setScheduledDays(?int $scheduledDays): self { $this->scheduledDays = $scheduledDays; return $this; }
    public function getElapsedDays(): ?int { return $this->elapsedDays; }
    public function setElapsedDays(?int $elapsedDays): self { $this->elapsedDays = $elapsedDays; return $this; }
    public function getReviewedAt(): ?\DateTimeInterface { return $this->reviewedAt; }
    public function setReviewedAt(\DateTimeInterface $reviewedAt): self { $this->reviewedAt = $reviewedAt; return $this; }
}
