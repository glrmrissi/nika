<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\GrammarParticleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GrammarParticleRepository::class)]
#[ORM\Table(name: 'grammar_particle')]
class GrammarParticle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 10)]
    private ?string $particle = null;

    #[ORM\Column(length: 20)]
    private ?string $romaji = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $meaning = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $usageNote = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $exampleOne = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $exampleOneReading = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $exampleOneTranslation = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $exampleTwo = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $exampleTwoReading = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $exampleTwoTranslation = null;

    #[ORM\Column(length: 50)]
    private ?string $category = null;

    #[ORM\Column]
    private ?int $sortOrder = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }
    public function getParticle(): ?string { return $this->particle; }
    public function setParticle(string $particle): self { $this->particle = $particle; return $this; }
    public function getRomaji(): ?string { return $this->romaji; }
    public function setRomaji(string $romaji): self { $this->romaji = $romaji; return $this; }
    public function getName(): ?string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }
    public function getMeaning(): ?string { return $this->meaning; }
    public function setMeaning(string $meaning): self { $this->meaning = $meaning; return $this; }
    public function getUsageNote(): ?string { return $this->usageNote; }
    public function setUsageNote(string $usageNote): self { $this->usageNote = $usageNote; return $this; }
    public function getExampleOne(): ?string { return $this->exampleOne; }
    public function setExampleOne(string $exampleOne): self { $this->exampleOne = $exampleOne; return $this; }
    public function getExampleOneReading(): ?string { return $this->exampleOneReading; }
    public function setExampleOneReading(string $exampleOneReading): self { $this->exampleOneReading = $exampleOneReading; return $this; }
    public function getExampleOneTranslation(): ?string { return $this->exampleOneTranslation; }
    public function setExampleOneTranslation(string $exampleOneTranslation): self { $this->exampleOneTranslation = $exampleOneTranslation; return $this; }
    public function getExampleTwo(): ?string { return $this->exampleTwo; }
    public function setExampleTwo(string $exampleTwo): self { $this->exampleTwo = $exampleTwo; return $this; }
    public function getExampleTwoReading(): ?string { return $this->exampleTwoReading; }
    public function setExampleTwoReading(string $exampleTwoReading): self { $this->exampleTwoReading = $exampleTwoReading; return $this; }
    public function getExampleTwoTranslation(): ?string { return $this->exampleTwoTranslation; }
    public function setExampleTwoTranslation(string $exampleTwoTranslation): self { $this->exampleTwoTranslation = $exampleTwoTranslation; return $this; }
    public function getCategory(): ?string { return $this->category; }
    public function setCategory(string $category): self { $this->category = $category; return $this; }
    public function getSortOrder(): ?int { return $this->sortOrder; }
    public function setSortOrder(int $sortOrder): self { $this->sortOrder = $sortOrder; return $this; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(\DateTimeInterface $createdAt): self { $this->createdAt = $createdAt; return $this; }
}
