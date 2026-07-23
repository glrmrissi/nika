<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfiguration;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfigurationInterface;
use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'user')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface, TwoFactorInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column]
    private bool $isVerified = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $verificationToken = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $resetToken = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $resetTokenExpiresAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $totpSecret = null;

    #[ORM\Column]
    private bool $totpEnabled = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avatarPath = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $timezone = null;

    #[ORM\Column(length: 8, options: ['default' => 'icon'])]
    private string $kanjiClickAction = 'icon';

    #[ORM\OneToMany(targetEntity: ReviewLog::class, mappedBy: 'reviewUser')]
    private Collection $reviewLogs;

    #[ORM\OneToMany(targetEntity: UserKanji::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private Collection $userKanjis;

    #[ORM\OneToMany(targetEntity: NameHistory::class, mappedBy: 'user', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['changedAt' => 'DESC'])]
    private Collection $nameHistories;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->reviewLogs = new ArrayCollection();
        $this->userKanjis = new ArrayCollection();
        $this->nameHistories = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): self { $this->email = $email; return $this; }

    public function getUserIdentifier(): string { return (string) $this->email; }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): self { $this->roles = $roles; return $this; }

    public function getPassword(): ?string { return $this->password; }
    public function setPassword(string $password): self { $this->password = $password; return $this; }

    public function getName(): ?string { return $this->name; }
    public function setName(?string $name): self { $this->name = $name; return $this; }

    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(\DateTimeInterface $createdAt): self { $this->createdAt = $createdAt; return $this; }

    public function isVerified(): bool { return $this->isVerified; }
    public function setVerified(bool $isVerified): self { $this->isVerified = $isVerified; return $this; }

    public function getVerificationToken(): ?string { return $this->verificationToken; }
    public function setVerificationToken(?string $verificationToken): self { $this->verificationToken = $verificationToken; return $this; }

    public function getResetToken(): ?string { return $this->resetToken; }
    public function setResetToken(?string $resetToken): self { $this->resetToken = $resetToken; return $this; }

    public function getResetTokenExpiresAt(): ?\DateTimeInterface { return $this->resetTokenExpiresAt; }
    public function setResetTokenExpiresAt(?\DateTimeInterface $resetTokenExpiresAt): self { $this->resetTokenExpiresAt = $resetTokenExpiresAt; return $this; }

    public function getTotpSecret(): ?string { return $this->totpSecret; }
    public function setTotpSecret(?string $totpSecret): self { $this->totpSecret = $totpSecret; return $this; }

    public function getAvatarPath(): ?string { return $this->avatarPath; }
    public function setAvatarPath(?string $avatarPath): self { $this->avatarPath = $avatarPath; return $this; }

    public function getTimezone(): ?string { return $this->timezone; }
    public function setTimezone(?string $timezone): self { $this->timezone = $timezone; return $this; }

    public function getEffectiveTimezone(): string { return $this->timezone ?: date_default_timezone_get(); }

    public function getKanjiClickAction(): string { return $this->kanjiClickAction; }
    public function setKanjiClickAction(string $kanjiClickAction): self { $this->kanjiClickAction = $kanjiClickAction; return $this; }

    public function isTotpEnabled(): bool { return $this->totpEnabled; }
    public function setTotpEnabled(bool $totpEnabled): self { $this->totpEnabled = $totpEnabled; return $this; }

    public function getReviewLogs(): Collection { return $this->reviewLogs; }
    public function addReviewLog(ReviewLog $reviewLog): self { if (!$this->reviewLogs->contains($reviewLog)) { $this->reviewLogs->add($reviewLog); $reviewLog->setReviewUser($this); } return $this; }
    public function removeReviewLog(ReviewLog $reviewLog): self { if ($this->reviewLogs->removeElement($reviewLog)) { if ($reviewLog->getReviewUser() === $this) { $reviewLog->setReviewUser(null); } } return $this; }
    public function getUserKanjis(): Collection { return $this->userKanjis; }
    public function addUserKanji(UserKanji $userKanji): self { if (!$this->userKanjis->contains($userKanji)) { $this->userKanjis->add($userKanji); $userKanji->setUser($this); } return $this; }
    public function removeUserKanji(UserKanji $userKanji): self { if ($this->userKanjis->removeElement($userKanji)) { if ($userKanji->getUser() === $this) { $userKanji->setUser(null); } } return $this; }
    public function getNameHistories(): Collection { return $this->nameHistories; }
    public function addNameHistory(NameHistory $nameHistory): self { if (!$this->nameHistories->contains($nameHistory)) { $this->nameHistories->add($nameHistory); $nameHistory->setUser($this); } return $this; }
    public function removeNameHistory(NameHistory $nameHistory): self { if ($this->nameHistories->removeElement($nameHistory)) { if ($nameHistory->getUser() === $this) { $nameHistory->setUser(null); } } return $this; }

    public function eraseCredentials(): void {}

    public function isTotpAuthenticationEnabled(): bool
    {
        return $this->totpEnabled && $this->totpSecret !== null;
    }

    public function getTotpAuthenticationUsername(): string
    {
        return $this->email;
    }

    public function getTotpAuthenticationConfiguration(): ?TotpConfigurationInterface
    {
        if ($this->totpSecret === null) {
            return null;
        }
        return new TotpConfiguration($this->totpSecret, 'sha1', 30, 6);
    }
}
