<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserParticle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserParticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserParticle::class);
    }

    public function findDue(User $user): array
    {
        $now = new \DateTime('now', new \DateTimeZone('UTC'));

        return $this->createQueryBuilder('up')
            ->andWhere('up.user = :user')
            ->andWhere('up.nextReviewAt <= :now')
            ->setParameter('user', $user)
            ->setParameter('now', $now)
            ->orderBy('up.nextReviewAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countDue(User $user): int
    {
        $now = new \DateTime('now', new \DateTimeZone('UTC'));

        return (int) $this->createQueryBuilder('up')
            ->select('COUNT(up.id)')
            ->andWhere('up.user = :user')
            ->andWhere('up.nextReviewAt <= :now')
            ->setParameter('user', $user)
            ->setParameter('now', $now)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
