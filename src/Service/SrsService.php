<?php

namespace App\Service;

use App\Entity\Kanji;
use App\Entity\ReviewLog;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class SrsService
{
    public function __construct(
        private EntityManagerInterface $em,
        private Security $security,
    ) {}

    public function review(Kanji $kanji, int $quality): void
    {
        $quality = max(0, min(5, $quality));

        $log = new ReviewLog();
        $log->setKanji($kanji);
        $log->setQuality($quality);

        $user = $this->security->getUser();
        if ($user instanceof User) {
            $log->setReviewUser($user);
        }

        $this->em->persist($log);

        $ef = $kanji->getEaseFactor();
        $interval = $kanji->getInterval();
        $reps = $kanji->getRepetitions();

        $newEf = $this->calculateEaseFactor($ef, $quality);
        $kanji->setEaseFactor($newEf);

        if ($quality < 3) {
            $reps = 0;
            $interval = 1;
        } else {
            $reps++;
            match ($reps) {
                1 => $interval = 1,
                2 => $interval = 6,
                default => $interval = (int) round($interval * $newEf),
            };
        }

        $kanji->setRepetitions($reps);
        $kanji->setInterval($interval);

        $nextReview = new \DateTime("+{$interval} days");
        $kanji->setNextReviewAt($nextReview);
        $kanji->setUpdatedAt(new \DateTime());

        $this->em->flush();
    }

    private function calculateEaseFactor(float $ef, int $quality): float
    {
        $newEf = $ef + (0.1 - (5 - $quality) * (0.08 + (5 - $quality) * 0.02));

        if ($newEf < 1.3) {
            $newEf = 1.3;
        }

        return $newEf;
    }
}
