<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $em): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword(
                $passwordHasher->hashPassword($user, $form->get('password')->getData())
            );
            $user->setVerificationToken(bin2hex(random_bytes(32)));
            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'Account created! Check your email to verify.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/verify-email', name: 'app_verify_email')]
    public function verifyEmail(Request $request, EntityManagerInterface $em): Response
    {
        $token = $request->query->get('token');
        if (!$token) {
            $this->addFlash('error', 'Invalid verification token.');
            return $this->redirectToRoute('app_login');
        }

        $user = $em->getRepository(User::class)->findOneBy(['verificationToken' => $token]);
        if (!$user) {
            $this->addFlash('error', 'Invalid or expired verification token.');
            return $this->redirectToRoute('app_login');
        }

        $user->setVerified(true);
        $user->setVerificationToken(null);
        $em->flush();

        $this->addFlash('success', 'Email verified! Log in.');
        return $this->redirectToRoute('app_login');
    }
}
