<?php

namespace App\Controller;

use App\Form\TwoFactorSetupFormType;
use Doctrine\ORM\EntityManagerInterface;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfiguration;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
            $code = $form->get('code')->getData();
            if ($user->getTotpAuthenticationConfiguration()?->getCode() === $code) {
                $user->setTotpEnabled(true);
                $em->flush();
                $this->addFlash('success', '2FA enabled successfully!');
                return $this->redirectToRoute('app_profile');
            }
            $this->addFlash('error', 'Invalid code. Try again.');
        }

        if (!$user->getTotpSecret()) {
            $secret = bin2hex(random_bytes(20));
            $user->setTotpSecret($secret);
            $user->setTotpEnabled(false);
            $em->flush();
        }

        $qrCode = null;
        if ($user->getTotpAuthenticationConfiguration()) {
            $uri = $user->getTotpAuthenticationConfiguration()->getAuthenticationContextUri();
            $result = Builder::create()
                ->writer(new PngWriter())
                ->data($uri)
                ->size(200)
                ->build();
            $qrCode = $result->getDataUri();
        }

        return $this->render('security/two_factor_setup.html.twig', [
            'form' => $form->createView(),
            'qrCode' => $qrCode,
            'secret' => $user->getTotpSecret(),
        ]);
    }

    #[Route('/2fa/disable', name: 'app_2fa_disable')]
    public function disable(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $user->setTotpSecret(null);
        $user->setTotpEnabled(false);
        $em->flush();
        $this->addFlash('success', '2FA disabled.');
        return $this->redirectToRoute('app_profile');
    }
}
