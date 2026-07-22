<?php

namespace App\EventSubscriber;

use App\Service\DiscordNotifier;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class HoneypotSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private DiscordNotifier $discord,
    ) {}

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if ($request->getMethod() !== 'POST' || $request->getPathInfo() !== '/login') {
            return;
        }

        $website = $request->request->get('website');
        $confirmEmail = $request->request->get('confirm_email');

        if (($website !== null && $website !== '') || ($confirmEmail !== null && $confirmEmail !== '')) {
            $ip = $request->getClientIp() ?? 'unknown';
            $userAgent = $request->headers->get('User-Agent') ?? 'unknown';

            $this->discord->send(
                sprintf('Bot detected from IP `%s`', $ip),
                [
                    'IP' => $ip,
                    'User-Agent' => $userAgent,
                    'Method' => $request->getMethod(),
                    'Path' => $request->getPathInfo(),
                ],
            );

            $event->setResponse(new Response('', 403));
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 20],
        ];
    }
}
