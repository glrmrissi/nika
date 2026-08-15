<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagAwareSessionInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class RateLimitSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly \Symfony\Component\RateLimiter\RateLimiterFactory $loginLimiter,
        private readonly \Symfony\Component\RateLimiter\RateLimiterFactory $twoFactorLimiter,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onRequest', 0],
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if ($request->isMethod('POST')) {
            $path = $request->getPathInfo();

            if ($path === '/login') {
                $this->checkRateLimit($event, $this->loginLimiter, $request->getClientIp(), 'Too many login attempts. Please try again later.');
            } elseif ($path === '/2fa_check') {
                $this->checkRateLimit($event, $this->twoFactorLimiter, $request->getClientIp(), 'Too many 2FA attempts. Please try again later.');
            }
        }
    }

    private function checkRateLimit(RequestEvent $event, \Symfony\Component\RateLimiter\RateLimiterFactory $limiter, string $clientIp, string $message): void
    {
        $rateLimiter = $limiter->create($clientIp);
        $limit = $rateLimiter->consume(1);

        if (!$limit->isAccepted()) {
            $request = $event->getRequest();
            $session = $request->getSession();

            if ($session instanceof FlashBagAwareSessionInterface) {
                $session->getFlashBag()->add('error', $message);
            }

            $event->setResponse(new RedirectResponse($request->headers->get('referer', '/login')));
        }
    }
}
