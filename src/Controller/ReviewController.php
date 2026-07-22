<?php

namespace App\Controller;

use App\Entity\Kanji;
use App\Repository\KanjiRepository;
use App\Service\SrsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ReviewController extends AbstractController
{
    #[Route('/review', name: 'app_review')]
    public function start(KanjiRepository $kanjiRepo): Response
    {
        $due = $kanjiRepo->findDueReviews();
        $dueCount = count($due);

        return $this->render('review/index.html.twig', [
            'dueCount' => $dueCount,
        ]);
    }

    #[Route('/review/next', name: 'app_review_next', methods: ['GET'])]
    public function next(KanjiRepository $kanjiRepo, Request $request): JsonResponse
    {
        $level = $request->query->get('level');
        $kanji = $level
            ? $kanjiRepo->findDueReviewsByLevel($level)
            : $kanjiRepo->findDueReviews();

        if (empty($kanji)) {
            return $this->json(['done' => true]);
        }

        shuffle($kanji);
        $k = $kanji[0];

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
    public function submit(Request $request, SrsService $srs, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $kanjiId = $data['kanji_id'] ?? null;
        $quality = $data['quality'] ?? null;

        if (!$kanjiId || $quality === null) {
            return $this->json(['error' => 'Invalid request'], 400);
        }

        $kanji = $em->getRepository(Kanji::class)->find($kanjiId);
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
            'easeFactor' => $kanji->getEaseFactor(),
            'interval' => $kanji->getInterval(),
            'repetitions' => $kanji->getRepetitions(),
            'nextReviewAt' => $kanji->getNextReviewAt()?->format('Y-m-d'),
        ]);
    }
}
