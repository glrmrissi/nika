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
