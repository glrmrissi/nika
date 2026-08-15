<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class DiscordNotifier
{
    private ?string $defaultWebhookUrl;

    public function __construct(
        private HttpClientInterface $httpClient,
        ?string $discordWebhookUrl = null,
    ) {
        $this->defaultWebhookUrl = $discordWebhookUrl;
    }

    public function send(string $message, array $extra = []): void
    {
        if (!empty($extra)) {
            $extra = [
                'title' => 'Bot Detection',
                'color' => 0x8f0020,
                'fields' => $extra,
            ];
        }

        $this->sendTo($this->defaultWebhookUrl, $message, $extra, 'nika Security', null);
    }

    public function sendReviewReminder(
        string $webhookUrl,
        int $dueCount,
        int $streak,
        int $reviewedToday,
        string $reviewUrl,
        bool $isTest = false,
    ): void {
        if (!$webhookUrl) {
            return;
        }

        $color = $isTest ? 0x34c759 : 0xff453a;
        $title = $isTest ? 'Webhook configured' : 'Time to review kanji';
        $description = $isTest
            ? 'This is a test reminder. You will receive a message like this every day from now on.'
            : sprintf(
                'Time to review. You have **%d** kanji to review today to maintain your **%d day%s** streak.',
                $dueCount,
                $streak,
                $streak === 1 ? '' : 's',
            );

        $fields = [
            ['name' => 'To review', 'value' => (string) $dueCount, 'inline' => true],
            ['name' => 'Streak', 'value' => $streak > 0 ? $streak . ' days' : '0', 'inline' => true],
            ['name' => 'Reviewed today', 'value' => (string) $reviewedToday, 'inline' => true],
        ];

        if ($isTest) {
            $fields = [
                ['name' => 'To review', 'value' => (string) $dueCount, 'inline' => true],
                ['name' => 'Streak', 'value' => $streak > 0 ? $streak . ' days' : '0', 'inline' => true],
            ];
        }

        $payload = [
            'username' => 'nika',
            'embeds' => [
                [
                    'title' => $title,
                    'description' => $description,
                    'url' => $reviewUrl,
                    'color' => $color,
                    'fields' => $fields,
                    'footer' => [
                        'text' => $isTest ? 'nika · webhook test' : 'nika · daily reminder',
                    ],
                    'timestamp' => (new \DateTime('now', new \DateTimeZone('UTC')))->format(\DateTimeInterface::ATOM),
                ],
            ],
        ];

        $this->post($webhookUrl, $payload);
    }

    public function sendTo(
        ?string $webhookUrl,
        string $message,
        array $extra = [],
        ?string $username = null,
        ?string $avatarUrl = null,
    ): void {
        if (!$webhookUrl) {
            return;
        }

        $payload = [
            'content' => $message,
        ];

        if ($username !== null) {
            $payload['username'] = $username;
        }

        if ($avatarUrl !== null) {
            $payload['avatar_url'] = $avatarUrl;
        }

        if (!empty($extra)) {
            $payload['embeds'] = [
                [
                    'title' => $extra['title'] ?? 'Details',
                    'color' => $extra['color'] ?? 0x0000f2,
                    'fields' => $this->buildFields($extra['fields'] ?? $extra),
                    'timestamp' => (new \DateTime('now', new \DateTimeZone('UTC')))->format(\DateTimeInterface::ATOM),
                ],
            ];
        }

        $this->post($webhookUrl, $payload);
    }

    private function post(string $webhookUrl, array $payload): void
    {
        try {
            $response = $this->httpClient->request('POST', $webhookUrl, [
                'json' => $payload,
                'timeout' => 10,
            ]);
            $response->getStatusCode();
        } catch (\Throwable) {
        }
    }

    private function buildFields(array $data): array
    {
        $fields = [];
        foreach ($data as $key => $value) {
            if (is_int($key)) {
                continue;
            }
            $fields[] = [
                'name' => (string) $key,
                'value' => is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_SLASHES),
                'inline' => true,
            ];
        }

        return $fields;
    }
}
