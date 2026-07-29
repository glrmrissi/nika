<?php

namespace App\Twig;

use App\Repository\ActivityRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    private const CACHE_TTL = 300;

    public function __construct(
        private readonly ActivityRepository $activityRepo,
        private readonly Security $security,
        private readonly RequestStack $requestStack,
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('app_streak', $this->getStreak(...)),
        ];
    }

    public function getStreak(): int
    {
        $session = $this->requestStack->getSession();
        $cache = $session->get('_streak_cache');
        if ($cache && (time() - ($cache['ts'] ?? 0)) < self::CACHE_TTL) {
            return $cache['value'];
        }

        $user = $this->security->getUser();
        $tz = $user?->getEffectiveTimezone() ?? 'UTC';
        $value = $this->activityRepo->countStreakDays($user, $tz);

        $session->set('_streak_cache', [
            'value' => $value,
            'ts' => time(),
        ]);

        return $value;
    }
}
