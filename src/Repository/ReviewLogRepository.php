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

    public function countStreakDays(?User $user = null, string $timezone = 'UTC'): int
    {
        $conn = $this->getEntityManager()->getConnection();
        $table = $this->getClassMetadata()->getTableName();
        $col = $this->getClassMetadata()->getColumnName('reviewedAt');

        $sql = "SELECT DISTINCT DATE({$col}) as day
                FROM {$table}
                WHERE DATE({$col}) >= DATE('now', '-60 days')";

        $params = [];
        if ($user) {
            $sql .= " AND reviewUser_id = :userId";
            $params['userId'] = $user->getId();
        }

        $sql .= " ORDER BY day DESC LIMIT 60";

        $result = $conn->executeQuery($sql, $params)->fetchFirstColumn();

        if (empty($result)) {
            return 0;
        }

        $streak = 0;
        $tz = new \DateTimeZone($timezone);
        $today = new \DateTime('now', $tz);
        $today->setTime(0, 0, 0);
        $todayUtc = (clone $today)->setTimezone(new \DateTimeZone('UTC'));

        foreach ($result as $day) {
            $date = new \DateTime($day . ' 00:00:00', new \DateTimeZone('UTC'));
            $diff = (int) $todayUtc->diff($date)->format('%r%a');

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

    public function findRecentWithKanji(int $limit = 4, ?User $user = null, int $offset = 0): array
    {
        $qb = $this->createQueryBuilder('r')
            ->join('r.kanji', 'k')
            ->select('k.id', 'k.character', 'k.meanings', 'r.reviewedAt')
            ->orderBy('r.reviewedAt', 'DESC')
            ->setMaxResults(($offset + $limit) * 3);

        if ($user) {
            $qb->andWhere('r.reviewUser = :user')
                ->setParameter('user', $user);
        }

        $rows = $qb->getQuery()->getResult();

        $seen = [];
        $result = [];
        $kanjiIds = [];
        $skipped = 0;

        foreach ($rows as $row) {
            $char = $row['character'];
            if (isset($seen[$char])) {
                continue;
            }
            $seen[$char] = true;

            if ($skipped < $offset) {
                $skipped++;
                continue;
            }

            $result[] = $row;
            $kanjiIds[] = $row['id'];

            if (count($result) >= $limit) {
                break;
            }
        }

        if ($user && !empty($kanjiIds)) {
            $em = $this->getEntityManager();
            $userKanjiEntities = $em->createQueryBuilder()
                ->select('uk')
                ->from(UserKanji::class, 'uk')
                ->andWhere('uk.user = :user')
                ->andWhere('uk.kanji IN (:kanjiIds)')
                ->setParameter('user', $user)
                ->setParameter('kanjiIds', $kanjiIds)
                ->getQuery()
                ->getResult();

            $ukMap = [];
            foreach ($userKanjiEntities as $uk) {
                $ukMap[$uk->getKanji()->getId()] = $uk;
            }

            foreach ($result as &$row) {
                $row['mastery'] = 0;
                if (isset($ukMap[$row['id']])) {
                    $uk = $ukMap[$row['id']];
                    $row['mastery'] = $this->calculateMastery(
                        $uk->getRepetitions(),
                        $uk->getInterval(),
                        $uk->getEaseFactor(),
                    );
                }
                $meanings = explode(',', $row['meanings']);
                $row['meaning'] = trim($meanings[0] ?? '');
                unset($row['id'], $row['meanings'], $row['reviewedAt']);
            }
            unset($row);
        } else {
            foreach ($result as &$row) {
                $row['mastery'] = 0;
                $meanings = explode(',', $row['meanings']);
                $row['meaning'] = trim($meanings[0] ?? '');
                unset($row['id'], $row['meanings'], $row['reviewedAt']);
            }
            unset($row);
        }

        return $result;
    }

    public function countRecentUniqueKanji(?User $user = null): int
    {
        $conn = $this->getEntityManager()->getConnection();
        $table = $this->getClassMetadata()->getTableName();
        $col = $this->getClassMetadata()->getColumnName('reviewedAt');

        $sql = "SELECT COUNT(DISTINCT k.character) as cnt
                FROM {$table} r
                JOIN kanji k ON r.kanji_id = k.id";

        $params = [];
        if ($user) {
            $sql .= " WHERE r.reviewUser_id = :userId";
            $params['userId'] = $user->getId();
        }

        return (int) $conn->executeQuery($sql, $params)->fetchOne();
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
