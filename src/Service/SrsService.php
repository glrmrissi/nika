<?php

declare(strict_types=1);

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
use Scottlaurent\FSRS\Card;
use Scottlaurent\FSRS\Manager;
use Scottlaurent\FSRS\State;
use Symfony\Bundle\SecurityBundle\Security;

class SrsService
{
    private const array LEARNING_STEPS_MINUTES = [1, 10];
    private const array RELEARNING_STEPS_MINUTES = [10];
    private const int MAXIMUM_INTERVAL_DAYS = 36500;

    private Manager $fsrs;

    public function __construct(
        private EntityManagerInterface $em,
        private Security $security,
    ) {
        $this->fsrs = new Manager(
            defaultRequestRetention: 0.90,
            defaultMaximumInterval: self::MAXIMUM_INTERVAL_DAYS,
            learningSteps: self::LEARNING_STEPS_MINUTES,
            relearningSteps: self::RELEARNING_STEPS_MINUTES,
            enableFuzzing: false,
        );
    }

    public function review(Kanji $kanji, int $rating): void
    {
        $rating = $this->clampRating($rating);
        $user = $this->ensureUser();
        $userKanji = $this->em->getRepository(UserKanji::class)->findOneBy([
            'user' => $user,
            'kanji' => $kanji,
        ]);

        if (!$userKanji) {
            throw new \DomainException('Kanji is not selected');
        }

        $nowUtc = $this->nowUtc();
        $result = $this->applyFsrs($userKanji, $rating, $nowUtc);

        $log = new ReviewLog();
        $log->setKanji($kanji);
        $log->setReviewUser($user);
        $log->setReviewedAt($nowUtc);
        $log->setQuality($rating);
        $log->setRating($rating);
        $log->setCardState($result['log']->state);
        $log->setStability($result['card']->stability);
        $log->setDifficulty($result['card']->difficulty);
        $log->setScheduledDays((int) $result['card']->scheduledDays);
        $log->setElapsedDays($result['log']->elapsedDays);
        $this->em->persist($log);

        $this->createActivity($user, 'review_kanji', [
            'kanji_id' => $kanji->getId(),
            'rating' => $rating,
        ], $nowUtc);

        $this->em->flush();
    }

    public function reviewParticle(User $user, GrammarParticle $particle, int $rating): UserParticle
    {
        $rating = $this->clampRating($rating);
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

        $nowUtc = $this->nowUtc();
        $this->applyFsrs($userParticle, $rating, $nowUtc, false);

        $this->createActivity($user, 'review_grammar', [
            'particle_id' => $particle->getId(),
            'rating' => $rating,
        ], $nowUtc);

        $this->em->flush();

        return $userParticle;
    }

    public function quizParticle(User $user, GrammarParticle $particle, int $rating): UserParticle
    {
        $rating = $this->clampRating($rating);
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

        $nowUtc = $this->nowUtc();
        $this->applyFsrs($userParticle, $rating, $nowUtc, false);

        $this->createActivity($user, 'quiz', [
            'particle_id' => $particle->getId(),
            'rating' => $rating,
        ], $nowUtc);

        $this->em->flush();

        return $userParticle;
    }

    public function previewIntervals(object $entity): array
    {
        $nowUtc = $this->nowUtc();
        $schedule = $this->schedule($this->buildCard($entity), $entity, $nowUtc);
        $previews = [];

        foreach (range(1, 4) as $rating) {
            $previews[$rating] = $this->formatInterval($schedule[$rating]->card->due, $nowUtc);
        }

        return $previews;
    }

    private function applyFsrs(object $entity, int $rating, \DateTime $nowUtc, bool $enforceDue = true): array
    {
        $rating = $this->clampRating($rating);
        if ($enforceDue) {
            $this->assertDue($entity, $nowUtc);
        }

        $schedule = $this->schedule($this->buildCard($entity), $entity, $nowUtc);
        $info = $schedule[$rating];
        $info->reviewLog->scheduledDays = (int) $info->card->scheduledDays;

        $this->writeCard($entity, $info->card, $nowUtc);
        $this->updateMastery($entity, $info->card);

        return ['card' => $info->card, 'log' => $info->reviewLog];
    }

    private function schedule(Card $card, object $entity, \DateTime $nowUtc): array
    {
        $schedule = $this->fsrs->generateRepetitionSchedule($card, $nowUtc);
        $this->applyShortTermSchedule($schedule, $card, $nowUtc);

        foreach ($schedule as $info) {
            if ($info->card->state === State::REVIEW) {
                $info->card->step = 0;
            }
        }

        foreach ($schedule as $rating => $info) {
            $this->applyFuzzing($info->card, $entity, (int) $rating, $nowUtc);
        }

        return $schedule;
    }

    private function applyShortTermSchedule(array $schedule, Card $card, \DateTime $nowUtc): void
    {
        if ($card->state === State::NEW) {
            $this->setShortTermOutcome($schedule[1]->card, State::LEARNING, 0, self::LEARNING_STEPS_MINUTES[0], $nowUtc);
            $this->setShortTermOutcome($schedule[2]->card, State::LEARNING, 0, $this->hardStepMinutes(self::LEARNING_STEPS_MINUTES), $nowUtc);
            $this->setShortTermOutcome($schedule[3]->card, State::LEARNING, 1, self::LEARNING_STEPS_MINUTES[1], $nowUtc);
            return;
        }

        if ($card->state === State::LEARNING) {
            $step = min(max($card->step, 0), count(self::LEARNING_STEPS_MINUTES) - 1);
            $this->setShortTermOutcome($schedule[1]->card, State::LEARNING, 0, self::LEARNING_STEPS_MINUTES[0], $nowUtc);
            $this->setShortTermOutcome($schedule[2]->card, State::LEARNING, $step, $this->hardStepMinutes(self::LEARNING_STEPS_MINUTES, $step), $nowUtc);

            $nextStep = $step + 1;
            if ($nextStep < count(self::LEARNING_STEPS_MINUTES)) {
                $this->setShortTermOutcome($schedule[3]->card, State::LEARNING, $nextStep, self::LEARNING_STEPS_MINUTES[$nextStep], $nowUtc);
            }

            return;
        }

        if ($card->state === State::RELEARNING) {
            $step = min(max($card->step, 0), count(self::RELEARNING_STEPS_MINUTES) - 1);
            $this->setShortTermOutcome($schedule[1]->card, State::RELEARNING, 0, self::RELEARNING_STEPS_MINUTES[0], $nowUtc);
            $this->setShortTermOutcome($schedule[2]->card, State::RELEARNING, $step, self::RELEARNING_STEPS_MINUTES[$step], $nowUtc);

            $nextStep = $step + 1;
            if ($nextStep < count(self::RELEARNING_STEPS_MINUTES)) {
                $this->setShortTermOutcome($schedule[3]->card, State::RELEARNING, $nextStep, self::RELEARNING_STEPS_MINUTES[$nextStep], $nowUtc);
            }

            return;
        }

        $this->setShortTermOutcome($schedule[1]->card, State::RELEARNING, 0, self::RELEARNING_STEPS_MINUTES[0], $nowUtc);
    }

    private function setShortTermOutcome(Card $card, int $state, int $step, int $minutes, \DateTime $nowUtc): void
    {
        $card->state = $state;
        $card->step = $step;
        $card->scheduledDays = 0;
        $card->due = (clone $nowUtc)->modify('+' . $minutes . ' minutes');
    }

    private function hardStepMinutes(array $steps, int $step = 0): int
    {
        if ($step > 0 || count($steps) === 1) {
            return (int) round($steps[$step] * (count($steps) === 1 ? 1.5 : 1));
        }

        return (int) round(($steps[0] + $steps[1]) / 2);
    }

    private function applyFuzzing(Card $card, object $entity, int $rating, \DateTime $nowUtc): void
    {
        if ($card->state !== State::REVIEW || $card->scheduledDays < 3) {
            return;
        }

        $interval = (int) $card->scheduledDays;
        $delta = 1.0;
        $delta += 0.15 * max(min($interval, 7) - 2.5, 0.0);
        $delta += 0.10 * max(min($interval, 20) - 7.0, 0.0);
        $delta += 0.05 * max($interval - 20.0, 0.0);

        $minimum = max(2, (int) round($interval - $delta));
        $maximum = min(self::MAXIMUM_INTERVAL_DAYS, (int) round($interval + $delta));
        $minimum = min($minimum, $maximum);
        $span = max(1, $maximum - $minimum + 1);
        $seed = $this->entityKey($entity) . ':' . $nowUtc->format('Y-m-d') . ':' . $rating;
        $hash = (int) hexdec(substr(hash('sha256', $seed), 0, 8));
        $fuzzedInterval = $minimum + ($hash % $span);

        $card->scheduledDays = $fuzzedInterval;
        $card->due = (clone $nowUtc)->modify('+' . $fuzzedInterval . ' days');
    }

    private function buildCard(object $entity): Card
    {
        $due = $entity->getNextReviewAt()
            ? $this->toUtc($entity->getNextReviewAt())
            : $this->nowUtc();
        $lastReview = $entity->getLastReviewedAt()
            ? $this->toUtc($entity->getLastReviewedAt())
            : null;
        $state = in_array($entity->getState(), [State::NEW, State::LEARNING, State::REVIEW, State::RELEARNING], true)
            ? $entity->getState()
            : State::NEW;

        return new Card(
            due: $due,
            stability: (float) $entity->getStability(),
            difficulty: (float) $entity->getDifficulty(),
            reps: (int) $entity->getRepetitions(),
            lapses: (int) $entity->getLapses(),
            state: $state,
            step: (int) $entity->getStep(),
            lastReview: $lastReview,
            cardId: $this->entityKey($entity),
        );
    }

    private function writeCard(object $entity, Card $card, \DateTime $nowUtc): void
    {
        $entity->setStability($card->stability);
        $entity->setDifficulty($card->difficulty);
        $entity->setRepetitions($card->reps);
        $entity->setLapses($card->lapses);
        $entity->setState($card->state);
        $entity->setStep($card->step);
        $entity->setNextReviewAt($card->due);
        $entity->setLastReviewedAt($nowUtc);
    }

    private function updateMastery(object $entity, Card $card): void
    {
        $entity->setIsComplete(
            $card->state === State::REVIEW
            && $card->reps >= 3
            && $card->stability >= 21.0
        );
    }

    private function assertDue(object $entity, \DateTime $nowUtc): void
    {
        $nextReviewAt = $entity->getNextReviewAt();
        if ($nextReviewAt && $this->toUtc($nextReviewAt)->getTimestamp() > $nowUtc->getTimestamp()) {
            throw new \DomainException('Card is not due');
        }
    }

    private function toUtc(\DateTimeInterface $dt): \DateTime
    {
        $utc = new \DateTime('@' . $dt->getTimestamp());
        $utc->setTimezone(new \DateTimeZone('UTC'));
        return $utc;
    }

    private function nowUtc(): \DateTime
    {
        return new \DateTime('now', new \DateTimeZone('UTC'));
    }

    private function entityKey(object $entity): string
    {
        $id = method_exists($entity, 'getId') ? $entity->getId() : null;
        $subjectId = null;
        if (method_exists($entity, 'getKanji')) {
            $subjectId = $entity->getKanji()?->getId();
        } elseif (method_exists($entity, 'getParticle')) {
            $subjectId = $entity->getParticle()?->getId();
        }

        $userId = method_exists($entity, 'getUser') ? $entity->getUser()?->getId() : null;
        return $entity::class . ':' . ($id ?? $userId . ':' . $subjectId);
    }

    private function formatInterval(\DateTime $due, \DateTime $nowUtc): string
    {
        $seconds = $due->getTimestamp() - $nowUtc->getTimestamp();

        if ($seconds < 60) {
            return '<1m';
        }

        $minutes = (int) round($seconds / 60);
        if ($minutes < 60) {
            return $minutes . 'm';
        }

        $hours = (int) round($minutes / 60);
        if ($hours < 24) {
            return $hours . 'h';
        }

        $days = (int) round($hours / 24);
        if ($days < 30) {
            return $days . 'd';
        }

        $months = (int) round($days / 30);
        if ($months < 12) {
            return $months . 'mo';
        }

        return round($months / 12, 1) . 'y';
    }

    private function clampRating(int $rating): int
    {
        return max(1, min(4, $rating));
    }

    private function createActivity(User $user, string $typeSlug, array $metadata, \DateTime $nowUtc): void
    {
        $type = $this->em->getRepository(ActivityType::class)->findOneBy(['slug' => $typeSlug]);
        if (!$type) {
            return;
        }

        $activity = new Activity();
        $activity->setUser($user);
        $activity->setType($type);
        $activity->setCreatedAt($nowUtc);
        $activity->setMetadata(json_encode($metadata, JSON_THROW_ON_ERROR));
        $this->em->persist($activity);
    }

    private function ensureUser(): User
    {
        $tokenUser = $this->security->getUser();
        if (!$tokenUser instanceof User) {
            throw new \RuntimeException('User must be logged in');
        }

        $user = $this->em->find(User::class, $tokenUser->getId());
        if (!$user instanceof User) {
            throw new \RuntimeException('User not found');
        }

        return $user;
    }
}
