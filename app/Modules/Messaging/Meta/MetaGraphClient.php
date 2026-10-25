<?php

namespace App\Modules\Messaging\Meta;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

/**
 * WhatsApp Cloud API + Marketing Messages API as a Tech Provider.
 *
 * [افتراض] Paths and payloads follow developers.facebook.com (2026-10-21) and
 * must be confirmed in the P0b WhatsApp spike (which also records the App
 * Review videos).
 */
class MetaGraphClient
{
    public function __construct(
        private readonly string $graphBase,
        private readonly string $appId,
        private readonly string $appSecret,
    ) {}

    /** Embedded Signup v4: swap the short-lived code for a business token. */
    /**
     * Meta takes the app secret in the query string here. A timeout or an HTTP
     * error would carry that URL into the exception and from there into logs
     * and error trackers, so nothing from the underlying exception is kept
     * except its class and Meta's error code (review H2).
     */
    public function exchangeCode(string $code): string
    {
        try {
            $response = Http::baseUrl($this->graphBase)->timeout(20)->get('oauth/access_token', [
                'client_id' => $this->appId,
                'client_secret' => $this->appSecret,
                'code' => $code,
            ]);

            return (string) $this->decode($response)['access_token'];
        } catch (Throwable $exception) {
            $metaCode = $exception instanceof RequestException ? data_get($exception->response->json(), 'error.code') : null;

            throw new RuntimeException(sprintf('Meta code exchange failed (%s%s).', $exception::class, $metaCode === null ? '' : ", code {$metaCode}"));
        }
    }

    public function subscribeApp(string $token, string $wabaId): void
    {
        $this->decode($this->request($token)->post("{$wabaId}/subscribed_apps"));
    }

    public function registerNumber(string $token, string $phoneNumberId, string $pin, string $region): void
    {
        $this->decode($this->request($token)->post("{$phoneNumberId}/register", [
            'messaging_product' => 'whatsapp',
            'pin' => $pin,
            'data_localization_region' => $region,
        ]));
    }

    /**
     * @return array<string, mixed>
     */
    public function number(string $token, string $phoneNumberId): array
    {
        return $this->decode($this->request($token)->get($phoneNumberId, [
            'fields' => 'display_phone_number,quality_rating,verified_name',
        ]));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function templates(string $token, string $wabaId): array
    {
        $body = $this->decode($this->request($token)->get("{$wabaId}/message_templates", [
            'fields' => 'id,name,language,category,status,components',
            'limit' => 200,
        ]));

        return array_values(array_filter((array) ($body['data'] ?? []), 'is_array'));
    }

    /**
     * @param  array<string, mixed>  $template
     * @return array<string, mixed>
     */
    public function createTemplate(string $token, string $wabaId, array $template): array
    {
        return $this->decode($this->request($token)->post("{$wabaId}/message_templates", $template));
    }

    /**
     * Marketing goes through the MM API from day one: Cloud API marketing stops
     * for new accounts on 2027-04-13 and for everyone on 2027-10-13.
     *
     * @param  array<string, mixed>  $template
     */
    public function sendMarketingTemplate(string $token, string $phoneNumberId, string $to, array $template): Response
    {
        return $this->request($token)->post("{$phoneNumberId}/marketing_messages", [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => ltrim($to, '+'),
            'type' => 'template',
            'template' => $template,
        ]);
    }

    /** Free-form reply inside the 24h customer service window (e.g. opt-out confirmation). */
    public function sendText(string $token, string $phoneNumberId, string $to, string $text): Response
    {
        return $this->request($token)->post("{$phoneNumberId}/messages", [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => ltrim($to, '+'),
            'type' => 'text',
            'text' => ['body' => $text],
        ]);
    }

    public function verifySignature(string $body, string $header): bool
    {
        if ($this->appSecret === '' || ! str_starts_with($header, 'sha256=')) {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $body, $this->appSecret), substr($header, 7));
    }

    private function request(string $token): PendingRequest
    {
        return Http::baseUrl($this->graphBase)->withToken($token)->acceptJson()->timeout(20)
            ->retry(1, 300, fn ($exception) => $exception instanceof ConnectionException, throw: false);
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(Response $response): array
    {
        $response->throw();

        return (array) $response->json();
    }
}
