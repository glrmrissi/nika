<?php

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

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $reviewedAt = null;

    public function __construct()
    {
        $this->reviewedAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }
    public function getKanji(): ?Kanji { return $this->kanji; }
    public function setKanji(?Kanji $kanji): self { $this->kanji = $kanji; return $this; }
    public function getReviewUser(): ?User { return $this->reviewUser; }
    public function setReviewUser(?User $reviewUser): self { $this->reviewUser = $reviewUser; return $this; }
    public function getQuality(): ?int { return $this->quality; }
    public function setQuality(int $quality): self { $this->quality = $quality; return $this; }
    public function getReviewedAt(): ?\DateTimeInterface { return $this->reviewedAt; }
    public function setReviewedAt(\DateTimeInterface $reviewedAt): self { $this->reviewedAt = $reviewedAt; return $this; }
}
