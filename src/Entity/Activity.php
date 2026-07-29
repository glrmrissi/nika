<?php

namespace App\Entity;

use App\Repository\ActivityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ActivityRepository::class)]
#[ORM\Table(name: 'activity')]
class Activity
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
    private ?ActivityType $type = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $metadata = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }
    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): self { $this->user = $user; return $this; }
    public function getType(): ?ActivityType { return $this->type; }
    public function setType(?ActivityType $type): self { $this->type = $type; return $this; }
    public function getMetadata(): ?string { return $this->metadata; }
    public function setMetadata(?string $metadata): self { $this->metadata = $metadata; return $this; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(\DateTimeInterface $createdAt): self { $this->createdAt = $createdAt; return $this; }
}
