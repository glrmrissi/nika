<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\ActivityRepository;
use App\Repository\KanjiRepository;
use App\Repository\UserRepository;
use App\Service\DiscordNotifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:discord:reminders',
    description: 'Send Discord reminders to users with pending reviews',
)]
class SendDiscordRemindersCommand extends Command
{
    public function __construct(
        private UserRepository $userRepository,
        private KanjiRepository $kanjiRepository,
        private ActivityRepository $activityRepository,
        private DiscordNotifier $discordNotifier,
        private EntityManagerInterface $em,
        private string $defaultUri,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $users = $this->userRepository->findWithDiscordWebhook();

        if (empty($users)) {
            $io->note('No users with a Discord webhook configured.');

            return Command::SUCCESS;
        }

        $sent = 0;
        $nowUtc = new \DateTime('now', new \DateTimeZone('UTC'));

        foreach ($users as $user) {
            $webhookUrl = $user->getDiscordWebhookUrl();
            if (!$webhookUrl) {
                continue;
            }

            $tz = $user->getEffectiveTimezone();
            $todayInTz = new \DateTime('today', new \DateTimeZone($tz));
            $todayUtc = (clone $todayInTz)->setTimezone(new \DateTimeZone('UTC'));

            $lastReminder = $user->getDiscordReminderAt();
            if ($lastReminder && $lastReminder >= $todayUtc) {
                continue;
            }

            $dueCount = $this->kanjiRepository->countDueReviews($user);
            $reviewedToday = $this->activityRepository->countToday($user, $tz);

            if ($dueCount === 0 || $reviewedToday > 0) {
                continue;
            }

            if (!preg_match('~^https://(?:[a-zA-Z0-9-]+\.)?discord(?:app)?\.com/api/webhooks/\d+/[A-Za-z0-9_-]+/?(?:\?.*)?$~', $webhookUrl)) {
                continue;
            }

            $streak = $this->activityRepository->countStreakDays($user, $tz);
            $reviewUrl = rtrim($this->defaultUri, '/') . '/review';

            $this->discordNotifier->sendReviewReminder($webhookUrl, $dueCount, $streak, $reviewedToday, $reviewUrl);

            $user->setDiscordReminderAt($nowUtc);
            $this->em->persist($user);
            ++$sent;
        }

        $this->em->flush();

        $io->success(sprintf('Sent %d reminder(s).', $sent));

        return Command::SUCCESS;
    }
}
