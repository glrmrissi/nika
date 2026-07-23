<?php

namespace App\Entity;

use App\Repository\NameHistoryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NameHistoryRepository::class)]
#[ORM\Table(name: 'name_history')]
class NameHistory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'nameHistories')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\Column(name: 'changed_at', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $changedAt = null;

    public function __construct()
    {
        $this->changedAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): self { $this->user = $user; return $this; }

    public function getName(): ?string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }

    public function getChangedAt(): ?\DateTimeInterface { return $this->changedAt; }
    public function setChangedAt(\DateTimeInterface $changedAt): self { $this->changedAt = $changedAt; return $this; }
}
