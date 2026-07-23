<?php

namespace App\Service;

use App\Entity\Kanji;
use App\Entity\ReviewLog;
use App\Entity\User;
use App\Entity\UserKanji;
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

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new \RuntimeException('User must be logged in');
        }

        $userKanji = $this->em->getRepository(UserKanji::class)->findOneBy([
            'user' => $user,
            'kanji' => $kanji,
        ]);

        if (!$userKanji) {
            $userKanji = new UserKanji();
            $userKanji->setUser($user);
            $userKanji->setKanji($kanji);
            $this->em->persist($userKanji);
        }

        $log = new ReviewLog();
        $log->setKanji($kanji);
        $log->setQuality($quality);
        $log->setReviewUser($user);
        $this->em->persist($log);

        $ef = $userKanji->getEaseFactor();
        $interval = $userKanji->getInterval();
        $reps = $userKanji->getRepetitions();

        $newEf = $this->calculateEaseFactor($ef, $quality);
        $userKanji->setEaseFactor($newEf);

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

        $userKanji->setRepetitions($reps);
        $userKanji->setInterval($interval);
        $userKanji->setNextReviewAt(new \DateTime("+{$interval} days"));

        if ($reps >= 3 && $interval >= 30 && $newEf >= 2.5) {
            $userKanji->setIsComplete(true);
        }

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
