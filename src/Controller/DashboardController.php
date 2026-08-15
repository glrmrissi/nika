<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\ActivityRepository;
use App\Repository\KanjiRepository;
use App\Repository\ReviewLogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(Request $request, KanjiRepository $kanjiRepo, ReviewLogRepository $reviewLogRepo, ActivityRepository $activityRepo): Response
    {
        $user = $this->getUser();
        $tz = $user?->getEffectiveTimezone() ?? 'UTC';

        if ($user instanceof User) {
            $total = $kanjiRepo->countSelected($user);
            $dueCount = $kanjiRepo->countDueReviews($user);
            $byLevel = $kanjiRepo->countByLevel($user);
        } else {
            $total = 0;
            $dueCount = 0;
            $byLevel = [];
        }

        $reviewedToday = $activityRepo->countToday($user, $tz);
        $streak = $activityRepo->countStreakDays($user, $tz);

        if ($user instanceof User) {
            $thisWeek = $activityRepo->countThisWeek($user, $tz);
            $thisMonth = $activityRepo->countThisMonth($user, $tz);
            $thisYear = $activityRepo->countThisYear($user, $tz);
        } else {
            $thisWeek = 0;
            $thisMonth = 0;
            $thisYear = 0;
        }

        $totalAll = array_sum(array_column($byLevel, 'total'));
        foreach ($byLevel as &$level) {
            $level['percentage'] = $totalAll > 0 ? round(($level['total'] / $totalAll) * 100) : 0;
        }
        unset($level);

        $recent = $reviewLogRepo->findRecentWithKanji(20, $user instanceof User ? $user : null);

        $completedCount = $user instanceof User ? $kanjiRepo->countCompleted($user) : 0;
        $recentCompleted = $user instanceof User ? $kanjiRepo->findCompleted($user, 6) : [];

        $streakDays = [];
        $days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        $todayIdx = (int) (new \DateTime('now', new \DateTimeZone($tz)))->format('w');
        for ($i = -2; $i <= 2; $i++) {
            $idx = ($todayIdx + $i + 7) % 7;
            $streakDays[] = $days[$idx];
        }

        $session = $request->getSession();
        $loginSuccess = $session->remove('_login_success');

        return $this->render('dashboard/index.html.twig', [
            'dueCount' => $dueCount,
            'totalCount' => $total,
            'reviewedToday' => $reviewedToday,
            'streak' => $streak,
            'byLevel' => $byLevel,
            'recent' => $recent,
            'userName' => $user?->getName() ?? '',
            'streakDays' => $streakDays,
            'loginSuccess' => $loginSuccess,
            'hasKanji' => $total > 0,
            'completedCount' => $completedCount,
            'recentCompleted' => $recentCompleted,
            'thisWeek' => $thisWeek,
            'thisMonth' => $thisMonth,
            'thisYear' => $thisYear,
        ]);
    }
}
