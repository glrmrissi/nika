<?php

namespace App\Controller;

use App\Entity\Kanji;
use App\Entity\UserKanji;
use App\Repository\KanjiRepository;
use Doctrine\ORM\EntityManagerInterface;
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
        $user = $this->getUser();
        $kanjiClickAction = $user ? $user->getKanjiClickAction() : 'icon';

        return $this->render('kanji/index.html.twig', [
            'kanjiClickAction' => $kanjiClickAction,
        ]);
    }

    #[Route('/api/kanji', name: 'app_api_kanji', methods: ['GET'])]
    public function list(Request $request, KanjiRepository $kanjiRepo): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $level = $request->query->get('level');
        $status = $request->query->get('status');
        $limit = 50;
        $offset = ($page - 1) * $limit;

        $user = $this->getUser();

        $qb = $kanjiRepo->createQueryBuilder('k');

        if ($level) {
            $qb->andWhere('k.jlptLevel = :level')->setParameter('level', $level);
        }

        if ($status === 'studying' && $user) {
            $qb->join('k.userKanjis', 'uk_status')
                ->andWhere('uk_status.user = :userStatus')
                ->andWhere('uk_status.isComplete = :notDone')
                ->setParameter('userStatus', $user)
                ->setParameter('notDone', false);
        } elseif ($status === 'complete' && $user) {
            $qb->join('k.userKanjis', 'uk_status')
                ->andWhere('uk_status.user = :userStatus')
                ->andWhere('uk_status.isComplete = :done')
                ->setParameter('userStatus', $user)
                ->setParameter('done', true);
        }

        $total = (clone $qb)
            ->select('COUNT(k.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $qb
            ->select('k.id', 'k.character', 'k.meanings', 'k.onyomi', 'k.kunyomi', 'k.jlptLevel', 'k.strokeCount');

        if ($user) {
            $qb->addSelect('uk.id as selected')
                ->addSelect('uk.isComplete as done')
                ->leftJoin('k.userKanjis', 'uk', 'WITH', 'uk.user = :currentUser')
                ->setParameter('currentUser', $user);
        } else {
            $qb->addSelect('0 as selected')
                ->addSelect('0 as done');
        }

        $items = $qb
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

    #[Route('/kanji/{id}/select', name: 'app_kanji_select', methods: ['POST'])]
    public function select(Kanji $kanji, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $existing = $em->getRepository(UserKanji::class)->findOneBy([
            'user' => $user,
            'kanji' => $kanji,
        ]);

        if ($existing) {
            $em->remove($existing);
            $em->flush();
            return $this->json(['selected' => false, 'done' => false]);
        }

        $userKanji = new UserKanji();
        $userKanji->setUser($user);
        $userKanji->setKanji($kanji);
        $em->persist($userKanji);
        $em->flush();

        return $this->json(['selected' => true, 'done' => false]);
    }

    #[Route('/api/kanji/select-batch', name: 'app_kanji_select_batch', methods: ['POST'])]
    public function selectBatch(Request $request, KanjiRepository $kanjiRepo, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $data = json_decode($request->getContent(), true);
        $action = $data['action'] ?? 'select';
        $ids = $data['ids'] ?? [];
        $level = $data['level'] ?? null;

        if ($level) {
            $ids = $kanjiRepo->findIdsByLevel($level);
        }

        if (empty($ids)) {
            return $this->json(['error' => 'No kanji specified'], 400);
        }

        $kanjiList = $em->getRepository(Kanji::class)->findBy(['id' => $ids]);
        $count = 0;

        foreach ($kanjiList as $kanji) {
            $existing = $em->getRepository(UserKanji::class)->findOneBy([
                'user' => $user,
                'kanji' => $kanji,
            ]);

            if ($action === 'select') {
                if (!$existing) {
                    $userKanji = new UserKanji();
                    $userKanji->setUser($user);
                    $userKanji->setKanji($kanji);
                    $em->persist($userKanji);
                    $count++;
                }
            } else {
                if ($existing) {
                    $em->remove($existing);
                    $count++;
                }
            }
        }

        $em->flush();

        return $this->json([
            'success' => true,
            'action' => $action,
            'count' => $count,
        ]);
    }

    #[Route('/kanji/{id}/toggle-done', name: 'app_kanji_toggle_done', methods: ['POST'])]
    public function toggleDone(Kanji $kanji, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $userKanji = $em->getRepository(UserKanji::class)->findOneBy([
            'user' => $user,
            'kanji' => $kanji,
        ]);

        if (!$userKanji) {
            return $this->json(['error' => 'Kanji not selected'], 400);
        }

        $userKanji->setIsComplete(!$userKanji->isComplete());
        $em->flush();

        return $this->json(['done' => $userKanji->isComplete()]);
    }
}
