<?php

namespace App\Controller;

use App\Repository\KanjiRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class KanjiController extends AbstractController
{
    #[Route('/kanji', name: 'app_kanji_list')]
    public function index(): Response
    {
        return $this->render('kanji/index.html.twig');
    }

    #[Route('/api/kanji', name: 'app_api_kanji', methods: ['GET'])]
    public function list(Request $request, KanjiRepository $kanjiRepo): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $level = $request->query->get('level');
        $limit = 50;
        $offset = ($page - 1) * $limit;

        $qb = $kanjiRepo->createQueryBuilder('k');

        if ($level) {
            $qb->andWhere('k.jlptLevel = :level')->setParameter('level', $level);
        }

        $total = (clone $qb)
            ->select('COUNT(k.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $items = $qb
            ->select('k.id', 'k.character', 'k.meanings', 'k.onyomi', 'k.kunyomi', 'k.jlptLevel', 'k.strokeCount')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->orderBy('k.character', 'ASC')
            ->getQuery()
            ->getResult();

        $pages = max(1, (int) ceil($total / $limit));

        $response = $this->json([
            'data' => $items,
            'page' => $page,
            'pages' => $pages,
            'total' => (int) $total,
            'level' => $level,
        ]);

        $response->setSharedMaxAge(86400);
        $response->headers->set('Vary', 'Accept-Encoding');

        return $response;
    }
}
