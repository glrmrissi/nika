<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Kanji;
use App\Entity\User;
use App\Entity\UserKanji;
use App\Repository\KanjiRepository;
use App\Service\SrsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
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

        $dueCount = $kanjiRepo->countDueReviews($user);

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

        $dueCount = $kanjiRepo->countDueReviews($user);

        return $this->render('review/interactive.html.twig', [
            'dueCount' => $dueCount,
        ]);
    }

    #[Route('/review/next', name: 'app_review_next', methods: ['GET'])]
    public function next(KanjiRepository $kanjiRepo, SrsService $srs, EntityManagerInterface $em, Request $request): JsonResponse
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

        $k = $kanjiRepo->findRandomDueReview($user, $level);

        if (!$k) {
            return $this->json(['done' => true]);
        }

        $userKanji = $em->getRepository(UserKanji::class)->findOneBy([
            'user' => $user,
            'kanji' => $k,
        ]);

        if (!$userKanji) {
            $userKanji = new UserKanji();
            $userKanji->setUser($user);
            $userKanji->setKanji($k);
        }

        $stageLabels = ['New', 'Learning', 'Review', 'Relearning'];

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
            'stage' => $stageLabels[$userKanji->getState()] ?? 'New',
            'intervals' => $srs->previewIntervals($userKanji),
        ]);
    }

    #[Route('/review/submit', name: 'app_review_submit', methods: ['POST'])]
    public function submit(
        Request $request,
        SrsService $srs,
        EntityManagerInterface $em,
        #[Autowire(service: 'limiter.review_submit')] RateLimiterFactory $reviewSubmitLimiter,
    ): JsonResponse
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

        $data = $this->decodeJson($request);
        if ($data === null) {
            return $this->json(['error' => 'Invalid JSON body'], 400);
        }

        $kanjiId = $data['kanji_id'] ?? null;
        $rating = $data['rating'] ?? null;

        if (!is_int($kanjiId) || $kanjiId < 1) {
            return $this->json(['error' => 'Invalid kanji_id'], 400);
        }

        if (!is_int($rating) || $rating < 1 || $rating > 4) {
            return $this->json(['error' => 'Rating must be 1-4'], 400);
        }

        $kanji = $em->getRepository(Kanji::class)->find((int) $kanjiId);
        if (!$kanji) {
            return $this->json(['error' => 'Kanji not found'], 404);
        }

        try {
            $srs->review($kanji, (int) $rating);
        } catch (\DomainException $e) {
            return $this->json(['error' => $e->getMessage()], 409);
        } catch (\Throwable $e) {
            return $this->json(['error' => $e->getMessage(), 'class' => $e::class, 'trace' => $e->getTraceAsString()], 500);
        }

        return $this->json(['success' => true]);
    }

    private function decodeJson(Request $request): ?array
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        return is_array($data) ? $data : null;
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
