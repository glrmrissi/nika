<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\KanjiRepository;
use App\Repository\ReviewLogRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(Request $request, KanjiRepository $kanjiRepo, ReviewLogRepository $reviewLogRepo): Response
    {
        $user = $this->getUser();
        $tz = $user?->getEffectiveTimezone() ?? 'UTC';

        if ($user instanceof User) {
            $due = $kanjiRepo->findDueReviews($user, $tz);
            $total = $kanjiRepo->countSelected($user);
            $dueCount = count($due);
            $byLevel = $kanjiRepo->countByLevel($user);
        } else {
            $due = [];
            $total = 0;
            $dueCount = 0;
            $byLevel = [];
        }

        $reviewedToday = $reviewLogRepo->countReviewsToday($tz);
        $streak = $reviewLogRepo->countStreakDays($tz);

        $totalAll = array_sum(array_column($byLevel, 'total'));
        foreach ($byLevel as &$level) {
            $level['percentage'] = $totalAll > 0 ? round(($level['total'] / $totalAll) * 100) : 0;
        }
        unset($level);

        $recent = $reviewLogRepo->findRecentWithKanji(4, $user instanceof User ? $user : null);

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
        ]);
    }
}
