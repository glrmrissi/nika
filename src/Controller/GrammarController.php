<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\GrammarParticle;
use App\Entity\User;
use App\Entity\UserParticle;
use App\Repository\GrammarParticleRepository;
use App\Service\SrsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

class GrammarController extends AbstractController
{
    #[Route('/grammar', name: 'app_grammar_list')]
    public function index(): Response
    {
        return $this->render('grammar/index.html.twig');
    }

    #[Route('/api/grammar', name: 'app_api_grammar', methods: ['GET'])]
    public function list(Request $request, GrammarParticleRepository $repo): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 50;

        $total = $repo->countAll();
        $items = $repo->findAllWithPagination($page, $limit);
        $pages = max(1, (int) ceil($total / $limit));

        $data = array_map(function (GrammarParticle $p) {
            return [
                'id' => $p->getId(),
                'particle' => $p->getParticle(),
                'romaji' => $p->getRomaji(),
                'name' => $p->getName(),
                'meaning' => $p->getMeaning(),
                'usageNote' => $p->getUsageNote(),
                'exampleOne' => $p->getExampleOne(),
                'exampleOneReading' => $p->getExampleOneReading(),
                'exampleOneTranslation' => $p->getExampleOneTranslation(),
                'exampleTwo' => $p->getExampleTwo(),
                'exampleTwoReading' => $p->getExampleTwoReading(),
                'exampleTwoTranslation' => $p->getExampleTwoTranslation(),
                'category' => $p->getCategory(),
                'sortOrder' => $p->getSortOrder(),
            ];
        }, $items);

        $response = $this->json([
            'data' => $data,
            'page' => $page,
            'pages' => $pages,
            'total' => $total,
        ]);

        $response->setSharedMaxAge(86400);
        $response->headers->set('Vary', 'Accept-Encoding, Cookie');

        return $response;
    }

    #[Route('/api/grammar/{id}', name: 'app_api_grammar_detail', methods: ['GET'])]
    public function detail(GrammarParticle $particle): JsonResponse
    {
        return $this->json([
            'id' => $particle->getId(),
            'particle' => $particle->getParticle(),
            'romaji' => $particle->getRomaji(),
            'name' => $particle->getName(),
            'meaning' => $particle->getMeaning(),
            'usageNote' => $particle->getUsageNote(),
            'exampleOne' => $particle->getExampleOne(),
            'exampleOneReading' => $particle->getExampleOneReading(),
            'exampleOneTranslation' => $particle->getExampleOneTranslation(),
            'exampleTwo' => $particle->getExampleTwo(),
            'exampleTwoReading' => $particle->getExampleTwoReading(),
            'exampleTwoTranslation' => $particle->getExampleTwoTranslation(),
            'category' => $particle->getCategory(),
            'sortOrder' => $particle->getSortOrder(),
        ]);
    }

    #[Route('/grammar/review', name: 'app_grammar_review')]
    public function review(): Response
    {
        return $this->render('grammar/review.html.twig');
    }

    #[Route('/api/grammar/review/next', name: 'app_api_grammar_review_next', methods: ['GET'])]
    public function reviewNext(GrammarParticleRepository $repo, EntityManagerInterface $em, SrsService $srs): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $upRepo = $em->getRepository(UserParticle::class);
        $due = $upRepo->findDue($user);

        if (!empty($due)) {
            shuffle($due);
            $up = $due[0];
            $particle = $up->getParticle();
        } else {
            $all = $repo->findAll();
            if (empty($all)) {
                return $this->json(['done' => true]);
            }
            $particle = $all[array_rand($all)];
            $up = $upRepo->findOneBy(['user' => $user, 'particle' => $particle]);
            if (!$up) {
                $up = new UserParticle();
                $up->setUser($user);
                $up->setParticle($particle);
            }
        }

        $stageLabels = ['New', 'Learning', 'Review', 'Relearning'];

        return $this->json([
            'done' => false,
            'stage' => $stageLabels[$up->getState()] ?? 'New',
            'intervals' => $srs->previewIntervals($up),
            'particle' => [
                'id' => $particle->getId(),
                'particle' => $particle->getParticle(),
                'romaji' => $particle->getRomaji(),
                'name' => $particle->getName(),
                'meaning' => $particle->getMeaning(),
                'usageNote' => $particle->getUsageNote(),
                'exampleOne' => $particle->getExampleOne(),
                'exampleOneReading' => $particle->getExampleOneReading(),
                'exampleOneTranslation' => $particle->getExampleOneTranslation(),
                'exampleTwo' => $particle->getExampleTwo(),
                'exampleTwoReading' => $particle->getExampleTwoReading(),
                'exampleTwoTranslation' => $particle->getExampleTwoTranslation(),
                'category' => $particle->getCategory(),
            ],
        ]);
    }

    #[Route('/api/grammar/review/submit', name: 'app_api_grammar_review_submit', methods: ['POST'])]
    public function reviewSubmit(
        Request $request,
        SrsService $srs,
        EntityManagerInterface $em,
        RateLimiterFactory $reviewSubmitLimiter,
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

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

        $particleId = $data['particle_id'] ?? null;
        $rating = $data['rating'] ?? null;

        if (!is_int($particleId) || $particleId < 1) {
            return $this->json(['error' => 'Invalid particle_id'], 400);
        }

        if (!is_int($rating) || $rating < 1 || $rating > 4) {
            return $this->json(['error' => 'Rating must be 1-4'], 400);
        }

        $particle = $em->getRepository(GrammarParticle::class)->find($particleId);
        if (!$particle) {
            return $this->json(['error' => 'Particle not found'], 404);
        }

        try {
            $srs->reviewParticle($user, $particle, $rating);
        } catch (\DomainException $e) {
            return $this->json(['error' => $e->getMessage()], 409);
        }

        return $this->json(['success' => true]);
    }

    #[Route('/grammar/quiz', name: 'app_grammar_quiz')]
    public function quiz(): Response
    {
        return $this->render('grammar/quiz.html.twig');
    }

    #[Route('/api/grammar/quiz/next', name: 'app_api_grammar_quiz_next', methods: ['GET'])]
    public function quizNext(EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $repo = $em->getRepository(GrammarParticle::class);
        $all = $repo->findAll();

        if (empty($all)) {
            return $this->json(['done' => true]);
        }

        $upRepo = $em->getRepository(UserParticle::class);
        $completedIds = $em->createQueryBuilder()
            ->select('IDENTITY(up.particle)')
            ->from(UserParticle::class, 'up')
            ->andWhere('up.user = :user')
            ->andWhere('up.isComplete = true')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleColumnResult();
        $available = array_values(array_filter($all, fn($p) => !in_array($p->getId(), $completedIds, true)));

        if (empty($available)) {
            return $this->json(['done' => true]);
        }

        $particle = $available[array_rand($available)];
        $direction = mt_rand(0, 1) === 0 ? 'meaning_to_particle' : 'particle_to_meaning';

        $correctAnswer = $direction === 'particle_to_meaning'
            ? $particle->getName()
            : $particle->getParticle() . ' (' . $particle->getRomaji() . ')';

        $others = array_values(array_filter($all, fn($p) => $p->getId() !== $particle->getId()));
        shuffle($others);
        $distractors = array_slice($others, 0, 3);

        $options = [$correctAnswer];
        foreach ($distractors as $d) {
            $options[] = $direction === 'particle_to_meaning'
                ? $d->getName()
                : $d->getParticle() . ' (' . $d->getRomaji() . ')';
        }
        shuffle($options);

        $question = $direction === 'particle_to_meaning'
            ? sprintf('What does %s (%s) mean?', $particle->getParticle(), $particle->getRomaji())
            : sprintf('Which particle means "%s"?', $particle->getName());

        return $this->json([
            'done' => false,
            'direction' => $direction,
            'question' => $question,
            'particle_id' => $particle->getId(),
            'options' => $options,
        ]);
    }

    #[Route('/api/grammar/quiz/submit', name: 'app_api_grammar_quiz_submit', methods: ['POST'])]
    public function quizSubmit(
        Request $request,
        SrsService $srs,
        EntityManagerInterface $em,
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $csrfToken = $request->headers->get('X-CSRF-Token');
        if (!$csrfToken || !$this->isCsrfTokenValid('api', $csrfToken)) {
            return $this->json(['error' => 'Invalid CSRF token'], 403);
        }

        $data = $this->decodeJson($request);
        if ($data === null) {
            return $this->json(['error' => 'Invalid JSON body'], 400);
        }

        $particleId = $data['particle_id'] ?? null;
        $selected = $data['selected'] ?? null;
        $direction = $data['direction'] ?? null;

        if (!is_int($particleId) || $particleId < 1) {
            return $this->json(['error' => 'Invalid particle_id'], 400);
        }
        if (!$selected) {
            return $this->json(['error' => 'Missing selected answer'], 400);
        }
        if (!in_array($direction, ['particle_to_meaning', 'meaning_to_particle'], true)) {
            return $this->json(['error' => 'Invalid direction'], 400);
        }

        $particle = $em->getRepository(GrammarParticle::class)->find($particleId);
        if (!$particle) {
            return $this->json(['error' => 'Particle not found'], 404);
        }

        $correctAnswer = $direction === 'particle_to_meaning'
            ? $particle->getName()
            : $particle->getParticle() . ' (' . $particle->getRomaji() . ')';

        $correct = $selected === $correctAnswer;

        $rating = $correct ? 3 : 1;

        try {
            $srs->quizParticle($user, $particle, $rating);
        } catch (\DomainException $e) {
            return $this->json(['error' => $e->getMessage()], 409);
        }

        return $this->json([
            'correct' => $correct,
            'correct_answer' => $correctAnswer,
            'rating' => $rating,
            'particle' => [
                'id' => $particle->getId(),
                'particle' => $particle->getParticle(),
                'romaji' => $particle->getRomaji(),
                'name' => $particle->getName(),
                'meaning' => $particle->getMeaning(),
                'usageNote' => $particle->getUsageNote(),
                'exampleOne' => $particle->getExampleOne(),
                'exampleOneReading' => $particle->getExampleOneReading(),
                'exampleOneTranslation' => $particle->getExampleOneTranslation(),
                'exampleTwo' => $particle->getExampleTwo(),
                'exampleTwoReading' => $particle->getExampleTwoReading(),
                'exampleTwoTranslation' => $particle->getExampleTwoTranslation(),
                'category' => $particle->getCategory(),
            ],
        ]);
    }

    #[Route('/api/grammar/quiz/stats', name: 'app_api_grammar_quiz_stats', methods: ['GET'])]
    public function quizStats(EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['due' => 0, 'total' => 0, 'mastered' => 0]);
        }

        $due = $em->getRepository(UserParticle::class)->countDue($user);
        $total = $em->getRepository(GrammarParticle::class)->countAll();
        $mastered = $em->getRepository(UserParticle::class)->count(['user' => $user, 'isComplete' => true]);

        return $this->json(['due' => $due, 'total' => $total, 'mastered' => $mastered]);
    }

    #[Route('/api/grammar/review/count', name: 'app_api_grammar_review_count', methods: ['GET'])]
    public function reviewCount(EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['due' => 0, 'total' => 0, 'mastered' => 0]);
        }

        $due = $em->getRepository(UserParticle::class)->countDue($user);
        $total = $em->getRepository(GrammarParticle::class)->countAll();
        $mastered = $em->getRepository(UserParticle::class)->count(['user' => $user, 'isComplete' => true]);

        return $this->json(['due' => $due, 'total' => $total, 'mastered' => $mastered]);
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
}
