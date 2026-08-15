<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\GrammarParticle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class GrammarParticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GrammarParticle::class);
    }

    public function findAllWithPagination(int $page, int $limit = 50): array
    {
        return $this->createQueryBuilder('p')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->orderBy('p.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findRandomDueReview(UserParticle $userParticle = null): ?GrammarParticle
    {
        return $this->createQueryBuilder('p')
            ->orderBy('RANDOM()')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
