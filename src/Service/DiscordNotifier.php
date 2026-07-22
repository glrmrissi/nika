<?php

namespace App\Service;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class DiscordNotifier
{
    private ?string $webhookUrl;

    public function __construct(
        private HttpClientInterface $httpClient,
    ) {
        $this->webhookUrl = $_ENV['DISCORD_WEBHOOK_URL'] ?? null;
    }

    public function send(string $message, array $extra = []): void
    {
        if (!$this->webhookUrl) {
            return;
        }

        $payload = [
            'content' => $message,
            'username' => 'KanjiReview Security',
        ];

        if (!empty($extra)) {
            $payload['embeds'] = [
                [
                    'title' => 'Bot Detection',
                    'color' => 0x8f0020,
                    'fields' => array_map(fn ($k, $v) => ['name' => $k, 'value' => $v, 'inline' => true], array_keys($extra), $extra),
                    'timestamp' => (new \DateTime())->format(\DateTimeInterface::ATOM),
                ],
            ];
        }

        try {
            $this->httpClient->request('POST', $this->webhookUrl, [
                'json' => $payload,
            ]);
        } catch (\Throwable) {
        }
    }
}
