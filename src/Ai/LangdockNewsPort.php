<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTippspiel\Ai;

use Hwkdo\IntranetAppTippspiel\Contracts\TippspielAiNewsPortInterface;
use Hwkdo\IntranetAppTippspiel\Models\TippspielSettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class LangdockNewsPort implements TippspielAiNewsPortInterface
{
    private const string DEFAULT_MODEL = 'gpt-4o';

    /** @var list<string> */
    private const array FALLBACK_MODELS = ['gpt-4o', 'gpt-4o-mini'];

    private const string SYSTEM_PROMPT = <<<'PROMPT'
        Du schreibst kurze News-Artikel für ein internes Firmen-Fußball-Tippspiel.
        Antworte auf Deutsch.
        Format: erste Zeile = prägnante Überschrift, danach eine Leerzeile, danach der Artikeltext (2–4 Absätze).
        Keine Markdown-Überschriften, keine Hashtags.
        PROMPT;

    public function generateMatchdayNews(string $prompt): string
    {
        $settings = TippspielSettings::resolvedAppSettings();
        $preferredModel = filled($settings->aiNewsModel)
            ? $settings->aiNewsModel
            : self::DEFAULT_MODEL;

        $modelsToTry = array_values(array_unique(array_filter([
            $preferredModel,
            ...array_values(array_filter(
                self::FALLBACK_MODELS,
                static fn (string $model): bool => $model !== $preferredModel,
            )),
        ])));

        foreach ($modelsToTry as $model) {
            try {
                $content = $this->requestArticle($prompt, $model);

                if (filled($content)) {
                    if ($model !== $preferredModel) {
                        Log::info('Tippspiel: KI-News mit Fallback-Modell erstellt.', [
                            'preferred_model' => $preferredModel,
                            'used_model' => $model,
                        ]);
                    }

                    return $content;
                }
            } catch (Throwable $e) {
                Log::warning('Tippspiel: Langdock KI-News-Versuch fehlgeschlagen.', [
                    'model' => $model,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::error('Tippspiel: Langdock KI-News-Textgenerierung fehlgeschlagen (alle Modelle).', [
            'models' => $modelsToTry,
        ]);

        return '';
    }

    private function requestArticle(string $prompt, string $model): string
    {
        $apiKey = (string) config('services.langdock.api_key', '');
        if (trim($apiKey) === '') {
            throw new \InvalidArgumentException('LANGDOCK_API_KEY fehlt (services.langdock.api_key).');
        }

        $url = rtrim($this->langdockBaseUri(), '/').'/chat/completions';

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->timeout(120)
            ->connectTimeout(10)
            ->post($url, $this->buildPayload($model, $prompt));

        if (! $response->successful()) {
            throw new \RuntimeException(
                'Langdock HTTP '.$response->status().': '.$response->body()
            );
        }

        /** @var array<string, mixed>|null $data */
        $data = $response->json();
        if (! is_array($data)) {
            throw new \RuntimeException('Langdock-Antwort ist kein JSON-Objekt.');
        }

        if (! isset($data['choices']) || ! is_array($data['choices'])) {
            $hint = isset($data['error'])
                ? json_encode($data['error'], JSON_UNESCAPED_UNICODE)
                : json_encode($data, JSON_UNESCAPED_UNICODE);

            throw new \RuntimeException('Langdock-Antwort ohne „choices“: '.$hint);
        }

        $content = $this->extractContent($data);

        if (! filled($content)) {
            $choice = $data['choices'][0] ?? [];

            Log::warning('Tippspiel: Langdock lieferte leeren Artikeltext.', [
                'model' => $model,
                'finish_reason' => is_array($choice) ? ($choice['finish_reason'] ?? null) : null,
                'usage' => $data['usage'] ?? null,
            ]);
        }

        return $content;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPayload(string $model, string $prompt): array
    {
        $payload = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => self::SYSTEM_PROMPT],
                ['role' => 'user', 'content' => $prompt],
            ],
            'max_tokens' => $this->isReasoningModel($model) ? 8192 : 4096,
        ];

        if ($this->isReasoningModel($model)) {
            $payload['reasoning_effort'] = 'none';
        }

        return $payload;
    }

    private function isReasoningModel(string $model): bool
    {
        return preg_match('/^(gpt-5|o[134])([-.]|$)/', $model) === 1;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function extractContent(array $data): string
    {
        $choice = $data['choices'][0] ?? null;
        if (! is_array($choice)) {
            return '';
        }

        $message = $choice['message'] ?? null;
        if (! is_array($message)) {
            return '';
        }

        $content = $message['content'] ?? '';

        if (is_string($content)) {
            return trim($content);
        }

        if (! is_array($content)) {
            return '';
        }

        $parts = [];
        foreach ($content as $part) {
            if (! is_array($part)) {
                continue;
            }

            if (($part['type'] ?? '') === 'text' && is_string($part['text'] ?? null)) {
                $parts[] = $part['text'];
            }
        }

        return trim(implode("\n", $parts));
    }

    private function langdockBaseUri(): string
    {
        $base = rtrim((string) config('services.langdock.base_api_url', 'https://api.langdock.com/'), '/');

        return str_ends_with($base, '/openai/eu/v1') ? $base : $base.'/openai/eu/v1';
    }
}
