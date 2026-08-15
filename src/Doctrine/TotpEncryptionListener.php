<?php

declare(strict_types=1);

namespace App\Doctrine;

use App\Entity\User;
use App\Service\TotpEncryptionService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostLoadEventArgs;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::postLoad)]
#[AsDoctrineListener(event: Events::prePersist)]
#[AsDoctrineListener(event: Events::preUpdate)]
readonly class TotpEncryptionListener
{
    public function __construct(
        private TotpEncryptionService $totpEncryption,
    ) {}

    public function postLoad(PostLoadEventArgs $args): void
    {
        $entity = $args->getObject();
        if ($entity instanceof User && $entity->getTotpSecret() !== null) {
            $entity->setTotpSecret($this->totpEncryption->decrypt($entity->getTotpSecret()));
        }
    }

    public function prePersist(PrePersistEventArgs $args): void
    {
        $entity = $args->getObject();
        if ($entity instanceof User && $entity->getTotpSecret() !== null) {
            $entity->setTotpSecret($this->totpEncryption->encrypt($entity->getTotpSecret()));
        }
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getObject();
        if ($entity instanceof User && $args->hasChangedField('totpSecret') && $entity->getTotpSecret() !== null) {
            $entity->setTotpSecret($this->totpEncryption->encrypt($entity->getTotpSecret()));
        }
    }
}
