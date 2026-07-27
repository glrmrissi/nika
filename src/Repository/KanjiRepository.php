<?php

namespace App\Repository;

use App\Entity\Kanji;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class KanjiRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Kanji::class);
    }

    public function findDueReviews(User $user, string $timezone = 'UTC'): array
    {
        $now = new \DateTime('now', new \DateTimeZone($timezone));

        return $this->createQueryBuilder('k')
            ->join('k.userKanjis', 'uk')
            ->andWhere('uk.user = :user')
            ->andWhere('uk.nextReviewAt <= :now')
            ->setParameter('user', $user)
            ->setParameter('now', $now)
            ->orderBy('uk.nextReviewAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findDueReviewsByLevel(User $user, string $level, string $timezone = 'UTC'): array
    {
        $now = new \DateTime('now', new \DateTimeZone($timezone));

        return $this->createQueryBuilder('k')
            ->join('k.userKanjis', 'uk')
            ->andWhere('uk.user = :user')
            ->andWhere('uk.nextReviewAt <= :now')
            ->andWhere('k.jlptLevel = :level')
            ->setParameter('user', $user)
            ->setParameter('now', $now)
            ->setParameter('level', $level)
            ->orderBy('uk.nextReviewAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countDueReviews(User $user, string $timezone = 'UTC'): int
    {
        $now = new \DateTime('now', new \DateTimeZone($timezone));

        return (int) $this->createQueryBuilder('k')
            ->select('COUNT(k.id)')
            ->join('k.userKanjis', 'uk')
            ->andWhere('uk.user = :user')
            ->andWhere('uk.nextReviewAt <= :now')
            ->setParameter('user', $user)
            ->setParameter('now', $now)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countByLevel(?User $user = null): array
    {
        $qb = $this->createQueryBuilder('k')
            ->select('k.jlptLevel')
            ->addSelect('COUNT(k.id) as total');

        if ($user) {
            $qb->join('k.userKanjis', 'uk')
                ->andWhere('uk.user = :user')
                ->setParameter('user', $user);
        }

        return $qb->groupBy('k.jlptLevel')
            ->orderBy('k.jlptLevel', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countSelected(User $user): int
    {
        return (int) $this->createQueryBuilder('k')
            ->select('COUNT(k.id)')
            ->join('k.userKanjis', 'uk')
            ->andWhere('uk.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findIdsByLevel(string $level): array
    {
        return $this->createQueryBuilder('k')
            ->select('k.id')
            ->andWhere('k.jlptLevel = :level')
            ->setParameter('level', $level)
            ->getQuery()
            ->getSingleColumnResult();
    }

    public function countCompleted(User $user): int
    {
        return (int) $this->createQueryBuilder('k')
            ->select('COUNT(k.id)')
            ->join('k.userKanjis', 'uk')
            ->andWhere('uk.user = :user')
            ->andWhere('uk.isComplete = :complete')
            ->setParameter('user', $user)
            ->setParameter('complete', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findCompleted(User $user, int $limit = 6): array
    {
        return $this->createQueryBuilder('k')
            ->select('k.character', 'k.meanings', 'k.jlptLevel')
            ->join('k.userKanjis', 'uk')
            ->andWhere('uk.user = :user')
            ->andWhere('uk.isComplete = :complete')
            ->setParameter('user', $user)
            ->setParameter('complete', true)
            ->orderBy('uk.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findRandomDueReview(User $user, ?string $level = null, string $timezone = 'UTC'): ?Kanji
    {
        $now = new \DateTime('now', new \DateTimeZone($timezone));

        $qb = $this->createQueryBuilder('k')
            ->select('k.id')
            ->join('k.userKanjis', 'uk')
            ->andWhere('uk.user = :user')
            ->andWhere('uk.nextReviewAt <= :now')
            ->setParameter('user', $user)
            ->setParameter('now', $now)
            ->orderBy('RANDOM()')
            ->setMaxResults(1);

        if ($level) {
            $qb->andWhere('k.jlptLevel = :level')
                ->setParameter('level', $level);
        }

        $id = $qb->getQuery()->getSingleScalarResult();

        if (!$id) {
            return null;
        }

        return $this->find((int) $id);
    }
}
