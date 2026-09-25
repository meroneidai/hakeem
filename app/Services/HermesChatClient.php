<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class HermesChatClient
{
    public function configured(): bool
    {
        return filled(config('services.hermes.chat_url'));
    }

    /**
     * @param  list<array{role: string, content: string}>  $messages
     * @param  array<string, mixed>  $context
     * @return array{
     *     reply: string,
     *     actions: list<array{type: string, label: string, url?: string}>,
     *     results: array<string, mixed>,
     *     forms: list<array<string, mixed>>
     * }
     */
    public function complete(array $messages, array $context = []): array
    {
        $url = $this->endpoint();
        $key = (string) config('services.hermes.chat_key');

        $request = Http::acceptJson()
            ->asJson()
            ->connectTimeout(3)
            ->timeout(45);

        if (filled($key)) {
            $request = $request
                ->withToken($key)
                ->withHeaders(['X-Hermes-Key' => $key]);
        }

        try {
            $response = $this->usesCompletions($url)
                ? $request->post($url, $this->completionsPayload($messages, $context))
                : $request->post($url, $this->webhookPayload($messages, $context));
        } catch (ConnectionException $exception) {
            Log::warning('hakeem.hermes.chat.failed', [
                'url' => $url,
                'reason' => 'connection',
            ]);

            throw new RuntimeException('Hermes chat request failed.', 0, $exception);
        }

        if ($response->failed()) {
            Log::warning('hakeem.hermes.chat.failed', [
                'url' => $url,
                'status' => $response->status(),
            ]);

            throw new RuntimeException('Hermes chat request failed.');
        }

        return $this->normalize($response->json(), (string) $response->body());
    }

    /**
     * @param  list<array{role: string, content: string}>  $messages
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function completionsPayload(array $messages, array $context): array
    {
        return [
            'model' => (string) config('services.hermes.chat_model'),
            'temperature' => 0.3,
            'messages' => array_merge([$this->systemMessage($context)], $messages),
            'hakeem' => $this->hakeemContext($context),
        ];
    }

    /**
     * @param  list<array{role: string, content: string}>  $messages
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function webhookPayload(array $messages, array $context): array
    {
        return [
            'message' => $messages[array_key_last($messages)]['content'] ?? '',
            'messages' => $messages,
            'conversation_id' => $context['conversation_id'] ?? null,
            'locale' => $context['locale'] ?? app()->getLocale(),
            'hakeem' => $this->hakeemContext($context),
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function hakeemContext(array $context): array
    {
        return [
            'app_url' => $context['app_url'] ?? url('/'),
            'tools_base' => url('/api/agent/v1'),
            'locale' => $context['locale'] ?? app()->getLocale(),
            'conversation_id' => $context['conversation_id'] ?? null,
            'signed_in' => (bool) ($context['signed_in'] ?? false),
            'visitor_name' => $context['visitor_name'] ?? null,
            'customer_token' => $context['customer_token'] ?? null,
            'voice_mode' => (bool) ($context['voice_mode'] ?? false),
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array{role: string, content: string}
     */
    private function systemMessage(array $context): array
    {
        $locale = $context['locale'] ?? app()->getLocale();
        $appUrl = $context['app_url'] ?? url('/');
        $signedIn = (bool) ($context['signed_in'] ?? false);

        $lines = [
            'You are Hermes, Hakeem’s patient assistant.',
            "Reply in {$locale} unless the visitor writes in another language.",
            'Use the Hakeem tools already configured at {HAKEEM_BASE_URL}/api/agent/v1.',
            "Public site origin: {$appUrl}.",
            'Search doctors, clinics, labs and offers. Share book_url and page URLs as markdown links so the chat can render them.',
            'When the visitor wants to book, include the booking link. Do not invent doctors, prices, or slots.',
            'Never call admin APIs or delete records.',
            'If request metadata includes hakeem.customer_token, send it as Authorization: Bearer for customer endpoints and do not ask for the password again.',
        ];

        $lines[] = $signedIn
            ? 'The visitor is signed in on the website. Visitor name: '.((string) ($context['visitor_name'] ?? ''))
            : 'The visitor is not signed in. Ask for phone/email and password only when they need their account.';

        if (! empty($context['voice_mode'])) {
            $lines[] = 'Voice call mode: reply in one or two short spoken sentences only. No lists, no markdown links, no bullet points. Write numbers as words. Ask at most one question.';
        }

        return [
            'role' => 'system',
            'content' => implode("\n", $lines),
        ];
    }

    /**
     * @param  array<string, mixed>|null  $json
     * @return array{
     *     reply: string,
     *     actions: list<array{type: string, label: string, url?: string}>,
     *     results: array<string, mixed>,
     *     forms: list<array<string, mixed>>
     * }
     */
    private function normalize(?array $json, string $raw): array
    {
        $reply = '';
        $actions = [];
        $results = [];
        $forms = [];

        if (is_array($json)) {
            $reply = $this->extractText($json);
            $actions = $this->normalizeActions(array_merge(
                Arr::wrap($json['actions'] ?? []),
                Arr::wrap($json['links'] ?? []),
            ));
            $results = is_array($json['results'] ?? null) ? $json['results'] : [];
            $forms = $this->normalizeForms(Arr::wrap($json['forms'] ?? []));
        }

        if ($reply === '' && $raw !== '' && ! is_array($json)) {
            $reply = $raw;
        }

        if (is_string($reply) && str_starts_with(ltrim($reply), '{')) {
            $decoded = json_decode($reply, true);

            if (is_array($decoded)) {
                $nestedReply = $this->extractText($decoded);
                $reply = $nestedReply !== '' ? $nestedReply : $reply;
                $actions = array_merge($actions, $this->normalizeActions(Arr::wrap($decoded['actions'] ?? [])));
                $results = is_array($decoded['results'] ?? null)
                    ? array_merge($results, $decoded['results'])
                    : $results;
                $forms = array_merge($forms, $this->normalizeForms(Arr::wrap($decoded['forms'] ?? [])));
            }
        }

        $reply = trim((string) $reply);
        // Extract form markers before cleaning so [[form:...]] is not lost.
        $forms = array_merge($forms, $this->formsFromText($reply));
        $actions = $this->uniqueActions(array_merge($actions, $this->actionsFromText($reply)));
        $reply = $this->cleanReplyText($reply);

        return [
            'reply' => $reply !== '' ? $reply : __('agent.empty'),
            'actions' => $actions,
            'results' => $results,
            'forms' => $forms,
        ];
    }

    /**
     * @param  list<mixed>  $items
     * @return list<array<string, mixed>>
     */
    private function normalizeForms(array $items): array
    {
        $forms = [];

        foreach ($items as $item) {
            if (is_string($item) && $item !== '') {
                $definition = config('agent.forms.'.$item);

                if (is_array($definition)) {
                    $forms[] = ['form' => $item] + $definition;
                }

                continue;
            }

            if (! is_array($item)) {
                continue;
            }

            $key = (string) ($item['form'] ?? $item['name'] ?? '');

            if ($key === '') {
                continue;
            }

            $definition = config('agent.forms.'.$key, []);
            $forms[] = array_merge(is_array($definition) ? $definition : [], $item, ['form' => $key]);
        }

        return $forms;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function formsFromText(string $text): array
    {
        if (! preg_match_all('/\[\[form:([a-z0-9_-]+)\]\]/iu', $text, $matches)) {
            return [];
        }

        $forms = [];

        foreach ($matches[1] as $key) {
            $definition = config('agent.forms.'.$key);

            if (! is_array($definition)) {
                continue;
            }

            $forms[] = ['form' => $key] + $definition;
        }

        return $forms;
    }

    private function cleanReplyText(string $text): string
    {
        $text = preg_replace('/\[\[form:[a-z0-9_-]+\]\]/iu', '', $text) ?? $text;
        $text = preg_replace('/\[\[(?!form:)([^\]\[]{1,60})\]\]/u', '$1', $text) ?? $text;

        return trim(preg_replace("/\n{3,}/", "\n\n", $text) ?? $text);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function extractText(array $payload): string
    {
        $content = data_get($payload, 'choices.0.message.content');

        if (is_array($content)) {
            $content = collect($content)
                ->map(fn (mixed $part): string => is_array($part)
                    ? (string) ($part['text'] ?? $part['content'] ?? '')
                    : (string) $part)
                ->filter()
                ->implode("\n");
        }

        if (is_string($content) && $content !== '') {
            return $content;
        }

        foreach (['reply', 'message', 'content', 'text'] as $key) {
            $value = $payload[$key] ?? null;

            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return '';
    }

    /**
     * @param  list<mixed>  $items
     * @return list<array{type: string, label: string, url?: string}>
     */
    private function normalizeActions(array $items): array
    {
        $actions = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $url = $this->safeUrl($item['url'] ?? $item['href'] ?? $item['book_url'] ?? null);

            if ($url === null) {
                continue;
            }

            $label = trim((string) ($item['label'] ?? $item['title'] ?? $item['name'] ?? $url));

            $actions[] = [
                'type' => (string) ($item['type'] ?? 'link'),
                'label' => $label !== '' ? $label : $url,
                'url' => $url,
            ];
        }

        return $actions;
    }

    /**
     * @return list<array{type: string, label: string, url?: string, value?: string}>
     */
    private function actionsFromText(string $text): array
    {
        $actions = [];

        if (preg_match_all('/\[([^\]]+)\]\((https?:\/\/[^)\s]+|\/[^)\s]+)\)/u', $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $url = $this->safeUrl($match[2]);

                if ($url === null) {
                    continue;
                }

                $actions[] = [
                    'type' => 'link',
                    'label' => $match[1],
                    'url' => $url,
                ];
            }
        }

        if (preg_match_all('/\[\[(?!form:)([^\]\[]{1,60})\]\]/u', $text, $matches)) {
            foreach ($matches[1] as $label) {
                $label = trim($label);

                if ($label === '') {
                    continue;
                }

                $actions[] = [
                    'type' => 'reply',
                    'label' => $label,
                    'value' => $label,
                ];
            }
        }

        if (preg_match_all('/(?<!\]\()(https?:\/\/[^\s<>"\']+)/u', $text, $matches)) {
            foreach ($matches[1] as $rawUrl) {
                $url = $this->safeUrl(rtrim($rawUrl, '.,)'));

                if ($url === null) {
                    continue;
                }

                $actions[] = [
                    'type' => 'link',
                    'label' => $url,
                    'url' => $url,
                ];
            }
        }

        return $actions;
    }

    /**
     * @param  list<array{type: string, label: string, url?: string}>  $actions
     * @return list<array{type: string, label: string, url?: string}>
     */
    private function uniqueActions(array $actions): array
    {
        $seen = [];
        $unique = [];

        foreach ($actions as $action) {
            $key = ($action['url'] ?? '').'|'.$action['label'];

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $unique[] = $action;
        }

        return $unique;
    }

    private function safeUrl(mixed $url): ?string
    {
        if (! is_string($url) || $url === '') {
            return null;
        }

        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return $url;
        }

        $parts = parse_url($url);

        if (! is_array($parts) || ! in_array($parts['scheme'] ?? '', ['http', 'https'], true)) {
            return null;
        }

        return $url;
    }

    private function endpoint(): string
    {
        $url = rtrim((string) config('services.hermes.chat_url'), '/');

        if (str_ends_with($url, '/chat/completions') || str_contains($url, '/chat/completions?')) {
            return $url;
        }

        if (str_ends_with($url, '/v1')) {
            return $url.'/chat/completions';
        }

        return $url;
    }

    private function usesCompletions(string $url): bool
    {
        return str_contains($url, '/chat/completions');
    }
}
