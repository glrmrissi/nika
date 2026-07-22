<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ProfileFormType;
use App\Repository\KanjiRepository;
use App\Repository\ReviewLogRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ProfileController extends AbstractController
{
    private const AVATAR_DIR = 'uploads/avatars';

    #[Route('/profile', name: 'app_profile')]
    public function index(KanjiRepository $kanjiRepo, ReviewLogRepository $reviewLogRepo): Response
    {
        $user = $this->getUser();
        $tz = $user->getEffectiveTimezone();

        $reviewedToday = $reviewLogRepo->countReviewsToday($tz);
        $streak = $reviewLogRepo->countStreakDays($tz);
        $totalKanji = $kanjiRepo->count([]);
        $dueCount = count($kanjiRepo->findDueReviews($tz));

        return $this->render('profile/index.html.twig', [
            'reviewedToday' => $reviewedToday,
            'streak' => $streak,
            'totalKanji' => $totalKanji,
            'dueCount' => $dueCount,
        ]);
    }

    #[Route('/profile/edit', name: 'app_profile_edit')]
    public function edit(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(ProfileFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('avatar')->getData();
            if ($file instanceof UploadedFile) {
                $this->removeOldAvatar($user);
                $filename = sprintf('%s-%s.%s',
                    $user->getId(),
                    bin2hex(random_bytes(8)),
                    $file->guessExtension()
                );
                $file->move(
                    $this->getParameter('kernel.project_dir') . '/public/' . self::AVATAR_DIR,
                    $filename
                );
                $user->setAvatarPath(self::AVATAR_DIR . '/' . $filename);
            }
            $em->flush();
            $this->addFlash('success', 'Profile updated.');
            return $this->redirectToRoute('app_profile');
        }

        return $this->render('profile/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/profile/avatar/remove', name: 'app_profile_avatar_remove', methods: ['POST'])]
    public function removeAvatar(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $this->removeOldAvatar($user);
        $user->setAvatarPath(null);
        $em->flush();
        $this->addFlash('success', 'Avatar removed.');
        return $this->redirectToRoute('app_profile');
    }

    #[Route('/auth/me', name: 'app_auth_me')]
    public function me(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Not authenticated'], 401);
        }

        return $this->json([
            'id' => $user->getId(),
            'name' => $user->getName(),
            'email' => $user->getEmail(),
            'isVerified' => $user->isVerified(),
            'totpEnabled' => $user->isTotpEnabled(),
            'timezone' => $user->getEffectiveTimezone(),
            'createdAt' => $user->getCreatedAt()?->format('c'),
        ]);
    }

    private function removeOldAvatar(User $user): void
    {
        $old = $user->getAvatarPath();
        if ($old) {
            $path = $this->getParameter('kernel.project_dir') . '/public/' . $old;
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }
}
