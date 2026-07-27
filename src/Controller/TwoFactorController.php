<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\TwoFactorSetupFormType;
use Doctrine\ORM\EntityManagerInterface;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use OTPHP\TOTP;
use ParagonIE\ConstantTime\Base32;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class TwoFactorController extends AbstractController
{
    #[Route('/2fa/setup', name: 'app_2fa_setup')]
    public function setup(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if ($user->isTotpAuthenticationEnabled()) {
            $this->addFlash('info', '2FA is already active.');
        }

        $form = $this->createForm(TwoFactorSetupFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $config = $user->getTotpAuthenticationConfiguration();
            $totp = TOTP::create($config->getSecret(), $config->getPeriod(), $config->getAlgorithm(), $config->getDigits());
            if ($totp->verify($form->get('code')->getData(), null, 1)) {
                $user->setTotpEnabled(true);
                $em->flush();
                $this->addFlash('success', '2FA enabled successfully!');
                return $this->redirectToRoute('app_profile');
            }
            $this->addFlash('error', 'Invalid code. Try again.');
        }

        if (!$user->getTotpSecret()) {
            $secret = Base32::encodeUpper(random_bytes(20));
            $user->setTotpSecret($secret);
            $user->setTotpEnabled(false);
            $em->flush();
            $user->setTotpSecret($secret);
        } elseif (!preg_match('/^[A-Z2-7]+=*$/', $user->getTotpSecret())) {
            $secret = Base32::encodeUpper(random_bytes(20));
            $user->setTotpSecret($secret);
            $em->flush();
            $user->setTotpSecret($secret);
        }

        $config = $user->getTotpAuthenticationConfiguration();
        $totp = TOTP::create($config->getSecret(), $config->getPeriod(), $config->getAlgorithm(), $config->getDigits());
        $totp->setLabel($user->getEmail());
        $totp->setIssuer('nika');
        $uri = $totp->getProvisioningUri();
        $code = new QrCode(data: $uri, size: 200);
        $result = (new PngWriter())->write($code);
        $qrCode = $result->getDataUri();

        return $this->render('security/two_factor_setup.html.twig', [
            'form' => $form->createView(),
            'qrCode' => $qrCode,
            'secret' => $user->getTotpSecret(),
        ]);
    }

    #[Route('/2fa/disable', name: 'app_2fa_disable', methods: ['POST'])]
    public function disable(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $csrfToken = $request->request->get('_csrf_token');
        if (!$this->isCsrfTokenValid('disable-2fa', (string) $csrfToken)) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('app_profile');
        }

        $password = $request->request->get('password');
        if (!$password || !$passwordHasher->isPasswordValid($user, (string) $password)) {
            $this->addFlash('error', 'Incorrect password.');
            return $this->redirectToRoute('app_profile');
        }

        $user->setTotpSecret(null);
        $user->setTotpEnabled(false);
        $em->flush();

        $this->addFlash('success', '2FA disabled.');
        return $this->redirectToRoute('app_profile');
    }
}
