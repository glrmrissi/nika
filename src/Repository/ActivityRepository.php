<?php

namespace App\Repository;

use App\Entity\Activity;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ActivityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Activity::class);
    }

    public function countToday(string $timezone = 'UTC'): int
    {
        $tz = new \DateTimeZone($timezone);
        $today = new \DateTime('today', $tz);
        $tomorrow = new \DateTime('tomorrow', $tz);
        $todayUtc = $today->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        $tomorrowUtc = $tomorrow->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');

        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('a.createdAt >= :today')
            ->andWhere('a.createdAt < :tomorrow')
            ->setParameter('today', $todayUtc)
            ->setParameter('tomorrow', $tomorrowUtc)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countStreakDays(?User $user = null, string $timezone = 'UTC'): int
    {
        $conn = $this->getEntityManager()->getConnection();
        $table = 'activity';

        $sql = "SELECT DISTINCT DATE(created_at) as day
                FROM {$table}
                WHERE DATE(created_at) >= DATE('now', '-60 days')";

        $params = [];
        if ($user) {
            $sql .= " AND user_id = :userId";
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
        $monday = (new \DateTime('now', $tz))->modify('-' . ($dayOfWeek - 1) . ' days')->setTime(0, 0, 0);
        $monday->setTimezone(new \DateTimeZone('UTC'));
        $nextMonday = (clone $monday)->modify('+7 days');

        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('a.createdAt >= :start')
            ->andWhere('a.createdAt < :end')
            ->andWhere('a.user = :user')
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

        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('a.createdAt >= :start')
            ->andWhere('a.createdAt < :end')
            ->andWhere('a.user = :user')
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

        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('a.createdAt >= :start')
            ->andWhere('a.createdAt < :end')
            ->andWhere('a.user = :user')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getHeatmapData(User $user, string $timezone = 'UTC'): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $table = 'activity';

        $sql = "SELECT DATE(created_at) as day, COUNT(*) as count
                FROM {$table}
                WHERE user_id = :userId
                AND created_at >= :start
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
}
