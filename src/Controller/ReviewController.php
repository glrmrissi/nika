<?php

namespace App\Controller;

use App\Entity\Kanji;
use App\Entity\User;
use App\Repository\KanjiRepository;
use App\Service\SrsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

class ReviewController extends AbstractController
{
    #[Route('/review', name: 'app_review')]
    public function start(KanjiRepository $kanjiRepo): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $tz = $user->getEffectiveTimezone();
        $dueCount = $kanjiRepo->countDueReviews($user, $tz);

        return $this->render('review/index.html.twig', [
            'dueCount' => $dueCount,
        ]);
    }

    #[Route('/review/interactive', name: 'app_review_interactive')]
    public function interactive(KanjiRepository $kanjiRepo): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $tz = $user->getEffectiveTimezone();
        $dueCount = $kanjiRepo->countDueReviews($user, $tz);

        return $this->render('review/interactive.html.twig', [
            'dueCount' => $dueCount,
        ]);
    }

    #[Route('/review/next', name: 'app_review_next', methods: ['GET'])]
    public function next(KanjiRepository $kanjiRepo, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $level = $request->query->get('level');
        $allowedLevels = ['N5', 'N4', 'N3', 'N2', 'N1'];
        if ($level && !in_array($level, $allowedLevels, true)) {
            return $this->json(['error' => 'Invalid level'], 400);
        }

        $k = $kanjiRepo->findRandomDueReview($user, $level, $user->getEffectiveTimezone());

        if (!$k) {
            return $this->json(['done' => true]);
        }

        return $this->json([
            'done' => false,
            'kanji' => [
                'id' => $k->getId(),
                'character' => $k->getCharacter(),
                'meanings' => $k->getMeanings(),
                'onyomi' => $k->getOnyomi(),
                'kunyomi' => $k->getKunyomi(),
                'jlptLevel' => $k->getJlptLevel(),
                'strokeCount' => $k->getStrokeCount(),
            ],
        ]);
    }

    #[Route('/review/submit', name: 'app_review_submit', methods: ['POST'])]
    public function submit(Request $request, SrsService $srs, EntityManagerInterface $em, RateLimiterFactory $reviewSubmitLimiter): JsonResponse
    {
        $csrfToken = $request->headers->get('X-CSRF-Token');
        if (!$csrfToken || !$this->isCsrfTokenValid('api', $csrfToken)) {
            return $this->json(['error' => 'Invalid CSRF token'], 403);
        }

        $rateLimiter = $reviewSubmitLimiter->create($request->getClientIp());
        $limit = $rateLimiter->consume(1);
        if (!$limit->isAccepted()) {
            return $this->json(['error' => 'Too many requests'], 429);
        }

        $data = json_decode($request->getContent(), true);
        $kanjiId = $data['kanji_id'] ?? null;
        $quality = $data['quality'] ?? null;

        if (!$kanjiId || !is_int((int) $kanjiId) || (int) $kanjiId < 1) {
            return $this->json(['error' => 'Invalid kanji_id'], 400);
        }

        if ($quality === null || !is_int((int) $quality) || (int) $quality < 0 || (int) $quality > 5) {
            return $this->json(['error' => 'Quality must be 0-5'], 400);
        }

        $kanji = $em->getRepository(Kanji::class)->find((int) $kanjiId);
        if (!$kanji) {
            return $this->json(['error' => 'Kanji not found'], 404);
        }

        $srs->review($kanji, (int) $quality);

        return $this->json(['success' => true]);
    }

    #[Route('/kanji/{id}', name: 'app_kanji_detail', methods: ['GET'])]
    public function detail(Kanji $kanji): JsonResponse
    {
        return $this->json([
            'id' => $kanji->getId(),
            'character' => $kanji->getCharacter(),
            'meanings' => $kanji->getMeanings(),
            'onyomi' => $kanji->getOnyomi(),
            'kunyomi' => $kanji->getKunyomi(),
            'jlptLevel' => $kanji->getJlptLevel(),
            'strokeCount' => $kanji->getStrokeCount(),
        ]);
    }
}
