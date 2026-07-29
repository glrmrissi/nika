<?php

namespace App\Service;

use App\Entity\Activity;
use App\Entity\ActivityType;
use App\Entity\GrammarParticle;
use App\Entity\Kanji;
use App\Entity\ReviewLog;
use App\Entity\User;
use App\Entity\UserKanji;
use App\Entity\UserParticle;
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

        $user = $this->ensureUser();

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

        $this->applySm2($userKanji, $quality);

        $this->createActivity($user, 'review_kanji', [
            'kanji_id' => $kanji->getId(),
            'quality' => $quality,
        ]);

        $this->em->flush();
    }

    public function reviewParticle(User $user, GrammarParticle $particle, int $quality): UserParticle
    {
        $quality = max(0, min(5, $quality));

        $userParticle = $this->em->getRepository(UserParticle::class)->findOneBy([
            'user' => $user,
            'particle' => $particle,
        ]);

        if (!$userParticle) {
            $userParticle = new UserParticle();
            $userParticle->setUser($user);
            $userParticle->setParticle($particle);
            $this->em->persist($userParticle);
        }

        $this->applySm2($userParticle, $quality);

        $this->createActivity($user, 'review_grammar', [
            'particle_id' => $particle->getId(),
            'quality' => $quality,
        ]);

        $this->em->flush();

        return $userParticle;
    }

    public function quizParticle(User $user, GrammarParticle $particle, int $quality): UserParticle
    {
        $quality = max(0, min(5, $quality));

        $userParticle = $this->em->getRepository(UserParticle::class)->findOneBy([
            'user' => $user,
            'particle' => $particle,
        ]);

        if (!$userParticle) {
            $userParticle = new UserParticle();
            $userParticle->setUser($user);
            $userParticle->setParticle($particle);
            $this->em->persist($userParticle);
        }

        $this->applySm2($userParticle, $quality);

        $this->createActivity($user, 'quiz', [
            'particle_id' => $particle->getId(),
            'quality' => $quality,
        ]);

        $this->em->flush();

        return $userParticle;
    }

    private function applySm2(object $entity, int $quality): void
    {
        $ef = $entity->getEaseFactor();
        $interval = $entity->getInterval();
        $reps = $entity->getRepetitions();

        $newEf = $this->calculateEaseFactor($ef, $quality);
        $entity->setEaseFactor($newEf);

        if ($quality < 3) {
            $reps = 0;
            $interval = 1;
        } else {
            $reps++;
            $interval = match ($reps) {
                1 => 1,
                2 => 6,
                default => (int) round($interval * $newEf),
            };
        }

        $entity->setRepetitions($reps);
        $entity->setInterval($interval);
        $entity->setNextReviewAt(new \DateTime("+{$interval} days"));

        if ($reps >= 3 && $interval >= 30 && $newEf >= 2.5) {
            $entity->setIsComplete(true);
        }
    }

    private function createActivity(User $user, string $typeSlug, array $metadata): void
    {
        $type = $this->em->getRepository(ActivityType::class)->findOneBy(['slug' => $typeSlug]);
        if (!$type) {
            return;
        }

        $activity = new Activity();
        $activity->setUser($user);
        $activity->setType($type);
        $activity->setMetadata(json_encode($metadata));
        $this->em->persist($activity);
    }

    private function ensureUser(): User
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new \RuntimeException('User must be logged in');
        }
        return $user;
    }

    private function calculateEaseFactor(float $ef, int $quality): float
    {
        $newEf = $ef + (0.1 - (5 - $quality) * (0.08 + (5 - $quality) * 0.02));

        return max(1.3, $newEf);
    }
}
