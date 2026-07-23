<?php

namespace App\Entity;

use App\Repository\KanjiRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

#[ORM\Entity(repositoryClass: KanjiRepository::class)]
#[ORM\Table(name: 'kanji')]
class Kanji
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 10, unique: true)]
    private ?string $character = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $meanings = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $onyomi = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $kunyomi = null;

    #[ORM\Column(length: 5, nullable: true)]
    private ?string $jlptLevel = null;

    #[ORM\Column(nullable: true)]
    private ?int $strokeCount = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\OneToMany(targetEntity: ReviewLog::class, mappedBy: 'kanji', cascade: ['persist', 'remove'])]
    private Collection $reviewLogs;

    #[ORM\OneToMany(targetEntity: UserKanji::class, mappedBy: 'kanji', cascade: ['persist', 'remove'])]
    private Collection $userKanjis;

    public function __construct()
    {
        $this->reviewLogs = new ArrayCollection();
        $this->userKanjis = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }
    public function getCharacter(): ?string { return $this->character; }
    public function setCharacter(string $character): self { $this->character = $character; return $this; }
    public function getMeanings(): ?string { return $this->meanings; }
    public function setMeanings(string $meanings): self { $this->meanings = $meanings; return $this; }
    public function getOnyomi(): ?string { return $this->onyomi; }
    public function setOnyomi(string $onyomi): self { $this->onyomi = $onyomi; return $this; }
    public function getKunyomi(): ?string { return $this->kunyomi; }
    public function setKunyomi(string $kunyomi): self { $this->kunyomi = $kunyomi; return $this; }
    public function getJlptLevel(): ?string { return $this->jlptLevel; }
    public function setJlptLevel(?string $jlptLevel): self { $this->jlptLevel = $jlptLevel; return $this; }
    public function getStrokeCount(): ?int { return $this->strokeCount; }
    public function setStrokeCount(?int $strokeCount): self { $this->strokeCount = $strokeCount; return $this; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(\DateTimeInterface $createdAt): self { $this->createdAt = $createdAt; return $this; }
    public function getReviewLogs(): Collection { return $this->reviewLogs; }
    public function addReviewLog(ReviewLog $reviewLog): self { if (!$this->reviewLogs->contains($reviewLog)) { $this->reviewLogs->add($reviewLog); $reviewLog->setKanji($this); } return $this; }
    public function removeReviewLog(ReviewLog $reviewLog): self { if ($this->reviewLogs->removeElement($reviewLog)) { if ($reviewLog->getKanji() === $this) { $reviewLog->setKanji(null); } } return $this; }
    public function getUserKanjis(): Collection { return $this->userKanjis; }
    public function addUserKanji(UserKanji $userKanji): self { if (!$this->userKanjis->contains($userKanji)) { $this->userKanjis->add($userKanji); $userKanji->setKanji($this); } return $this; }
    public function removeUserKanji(UserKanji $userKanji): self { if ($this->userKanjis->removeElement($userKanji)) { if ($userKanji->getKanji() === $this) { $userKanji->setKanji(null); } } return $this; }

    public function getMeaningList(): array
    {
        return array_map('trim', explode(',', $this->meanings ?? ''));
    }

    public function getOnyomiList(): array
    {
        return array_map('trim', explode(',', $this->onyomi ?? ''));
    }

    public function getKunyomiList(): array
    {
        return array_map('trim', explode(',', $this->kunyomi ?? ''));
    }
}
