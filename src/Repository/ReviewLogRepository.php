<?php

namespace App\Repository;

use App\Entity\ReviewLog;
use App\Entity\User;
use App\Entity\UserKanji;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ReviewLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReviewLog::class);
    }

    public function countReviewsToday(string $timezone = 'UTC'): int
    {
        $tz = new \DateTimeZone($timezone);
        $today = new \DateTime('today', $tz);
        $tomorrow = new \DateTime('tomorrow', $tz);

        $todayUtc = $today->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        $tomorrowUtc = $tomorrow->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');

        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.reviewedAt >= :today')
            ->andWhere('r.reviewedAt < :tomorrow')
            ->setParameter('today', $todayUtc)
            ->setParameter('tomorrow', $tomorrowUtc)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countStreakDays(string $timezone = 'UTC'): int
    {
        $conn = $this->getEntityManager()->getConnection();
        $table = $this->getClassMetadata()->getTableName();
        $col = $this->getClassMetadata()->getColumnName('reviewedAt');

        $sql = "SELECT DISTINCT DATE({$col}) as day
                FROM {$table}
                ORDER BY day DESC
                LIMIT 365";

        $result = $conn->executeQuery($sql)->fetchFirstColumn();

        if (empty($result)) {
            return 0;
        }

        $streak = 0;
        $tz = new \DateTimeZone($timezone);
        $today = new \DateTime('today', $tz);

        foreach ($result as $day) {
            $date = new \DateTime($day);
            $diff = (int) $today->diff($date)->format('%r%a');

            if ($diff === $streak) {
                $streak++;
            } elseif ($diff > $streak) {
                break;
            }
        }

        return $streak;
    }

    public function countThisWeek(User $user, string $timezone = 'UTC'): int
    {
        $tz = new \DateTimeZone($timezone);
        $now = new \DateTime('now', $tz);
        $dayOfWeek = (int) $now->format('N');
        $monday = new \DateTime('now', $tz)->modify('-' . ($dayOfWeek - 1) . ' days')->setTime(0, 0, 0);
        $monday->setTimezone(new \DateTimeZone('UTC'));
        $nextMonday = (clone $monday)->modify('+7 days');

        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.reviewedAt >= :start')
            ->andWhere('r.reviewedAt < :end')
            ->andWhere('r.reviewUser = :user')
            ->setParameter('start', $monday)
            ->setParameter('end', $nextMonday)
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countThisMonth(User $user, string $timezone = 'UTC'): int
    {
        $tz = new \DateTimeZone($timezone);
        $now = new \DateTime('now', $tz);
        $start = (clone $now)->modify('first day of this month')->setTime(0, 0, 0);
        $start->setTimezone(new \DateTimeZone('UTC'));
        $end = (clone $start)->modify('+1 month');

        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.reviewedAt >= :start')
            ->andWhere('r.reviewedAt < :end')
            ->andWhere('r.reviewUser = :user')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countThisYear(User $user, string $timezone = 'UTC'): int
    {
        $tz = new \DateTimeZone($timezone);
        $now = new \DateTime('now', $tz);
        $start = (clone $now)->modify('first day of January')->setTime(0, 0, 0);
        $start->setTimezone(new \DateTimeZone('UTC'));
        $end = (clone $start)->modify('+1 year');

        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.reviewedAt >= :start')
            ->andWhere('r.reviewedAt < :end')
            ->andWhere('r.reviewUser = :user')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getHeatmapData(User $user, string $timezone = 'UTC'): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $table = $this->getClassMetadata()->getTableName();
        $col = $this->getClassMetadata()->getColumnName('reviewedAt');

        $sql = "SELECT DATE({$col}) as day, COUNT(*) as count
                FROM {$table}
                WHERE reviewUser_id = :userId
                AND {$col} >= :start
                GROUP BY day
                ORDER BY day ASC";

        $tz = new \DateTimeZone($timezone);
        $start = (new \DateTime('now', $tz))->modify('-364 days')->setTime(0, 0, 0);
        $start->setTimezone(new \DateTimeZone('UTC'));

        $rows = $conn->executeQuery($sql, [
            'userId' => $user->getId(),
            'start' => $start->format('Y-m-d H:i:s'),
        ])->fetchAllAssociative();

        $map = [];
        foreach ($rows as $row) {
            $map[$row['day']] = (int) $row['count'];
        }

        $result = [];
        for ($i = 364; $i >= 0; $i--) {
            $date = (new \DateTime('now', new \DateTimeZone('UTC')))->modify("-{$i} days")->format('Y-m-d');
            $result[] = [
                'date' => $date,
                'count' => $map[$date] ?? 0,
            ];
        }

        return $result;
    }

    public function findRecentWithKanji(int $limit = 4, ?User $user = null): array
    {
        $qb = $this->createQueryBuilder('r')
            ->join('r.kanji', 'k')
            ->select('k.character', 'k.meanings', 'r.reviewedAt')
            ->orderBy('r.reviewedAt', 'DESC')
            ->setMaxResults($limit * 2);

        if ($user) {
            $qb->andWhere('r.reviewUser = :user')
                ->setParameter('user', $user);
        }

        $rows = $qb->getQuery()->getResult();

        $seen = [];
        $result = [];
        foreach ($rows as $row) {
            $char = $row['character'];
            if (isset($seen[$char])) {
                continue;
            }
            $seen[$char] = true;

            if (count($result) >= $limit) {
                break;
            }

            $meanings = explode(',', $row['meanings']);

            $kanjiEntity = $this->getEntityManager()
                ->getRepository(\App\Entity\Kanji::class)
                ->findOneBy(['character' => $char]);

            $mastery = 0;
            if ($user && $kanjiEntity) {
                $uk = $this->getEntityManager()
                    ->getRepository(UserKanji::class)
                    ->findOneBy(['user' => $user, 'kanji' => $kanjiEntity]);

                if ($uk) {
                    $mastery = $this->calculateMastery(
                        $uk->getRepetitions(),
                        $uk->getInterval(),
                        $uk->getEaseFactor(),
                    );
                }
            }

            $result[] = [
                'character' => $char,
                'meaning' => trim($meanings[0] ?? ''),
                'mastery' => $mastery,
            ];
        }

        return $result;
    }

    private function calculateMastery(int $repetitions, int $interval, float $easeFactor): int
    {
        if ($repetitions === 0) {
            return 0;
        }

        $score = ($repetitions * 15)
               + (min(log($interval + 1, 2) * 8, 40))
               + (($easeFactor - 1.3) * 25);

        return min(100, max(0, (int) round($score)));
    }
}
