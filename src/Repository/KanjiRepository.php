<?php

namespace App\Repository;

use App\Entity\Kanji;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class KanjiRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Kanji::class);
    }

    public function findDueReviews(string $timezone = 'UTC'): array
    {
        $now = new \DateTime('now', new \DateTimeZone($timezone));

        return $this->createQueryBuilder('k')
            ->andWhere('k.nextReviewAt <= :now')
            ->setParameter('now', $now)
            ->orderBy('k.nextReviewAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findDueReviewsByLevel(string $level, string $timezone = 'UTC'): array
    {
        $now = new \DateTime('now', new \DateTimeZone($timezone));

        return $this->createQueryBuilder('k')
            ->andWhere('k.nextReviewAt <= :now')
            ->andWhere('k.jlptLevel = :level')
            ->setParameter('now', $now)
            ->setParameter('level', $level)
            ->orderBy('k.nextReviewAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countDueReviews(string $timezone = 'UTC'): int
    {
        $now = new \DateTime('now', new \DateTimeZone($timezone));

        return (int) $this->createQueryBuilder('k')
            ->select('COUNT(k.id)')
            ->andWhere('k.nextReviewAt <= :now')
            ->setParameter('now', $now)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countByLevel(): array
    {
        return $this->createQueryBuilder('k')
            ->select('k.jlptLevel')
            ->addSelect('COUNT(k.id) as total')
            ->groupBy('k.jlptLevel')
            ->orderBy('k.jlptLevel', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
