<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTippspiel\Ai;

use Hwkdo\IntranetAppTippspiel\Contracts\TippspielAiNewsImagePortInterface;
use Hwkdo\IntranetAppTippspiel\Models\TippspielSettings;
use Hwkdo\IntranetAppTippspiel\Support\MatchdayNewsCrestCompositor;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class LangdockNewsImagePort implements TippspielAiNewsImagePortInterface
{
    /** @var list<string> */
    private array $tempFiles = [];

    public function __construct(
        private readonly MatchdayNewsCrestCompositor $crestCompositor,
    ) {}

    public function __destruct()
    {
        foreach ($this->tempFiles as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }

    public function generateTitleImage(string $prompt, array $referenceImageUrls): ?string
    {
        $settings = TippspielSettings::resolvedAppSettings();
        $referencePaths = $this->downloadReferenceImages($referenceImageUrls);
        $preferredModel = filled($settings->aiNewsImageModel)
            ? $settings->aiNewsImageModel
            : 'dall-e-3';

        $modelsToTry = array_values(array_unique(array_filter([
            $preferredModel,
            $preferredModel !== 'dall-e-3' ? 'dall-e-3' : null,
        ])));

        $imagePath = null;

        foreach ($modelsToTry as $model) {
            try {
                $imagePath = $this->generateFromPrompt($prompt, $model);

                if ($imagePath !== null) {
                    break;
                }
            } catch (Throwable $e) {
                Log::warning('Tippspiel: KI-Titelbild-Versuch fehlgeschlagen.', [
                    'model' => $model,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($imagePath === null) {
            Log::error('Tippspiel: KI-Titelbild-Generierung fehlgeschlagen.', [
                'models' => $modelsToTry,
            ]);

            return null;
        }

        if ($referencePaths === []) {
            return $imagePath;
        }

        $composedPath = $this->crestCompositor->overlayCrests($imagePath, $referencePaths);

        if ($composedPath !== $imagePath) {
            $this->tempFiles[] = $composedPath;
        }

        return $composedPath;
    }

    private function generateFromPrompt(string $prompt, string $model): ?string
    {
        $parameters = $this->buildGenerationParameters($prompt, $model);

        $response = Http::timeout(120)
            ->withHeaders([
                'Authorization' => 'Bearer '.(string) config('services.langdock.api_key'),
                'Content-Type' => 'application/json',
            ])
            ->post($this->langdockBaseUri().'/images/generations', $parameters);

        $body = $response->json();

        if (! $response->successful()) {
            Log::warning('Tippspiel: Langdock images/generations HTTP-Fehler.', [
                'model' => $model,
                'status' => $response->status(),
                'body' => $body,
            ]);

            return null;
        }

        if (! is_array($body) || ! isset($body['data'][0]) || ! is_array($body['data'][0])) {
            Log::warning('Tippspiel: Langdock images/generations unerwartete Antwort.', [
                'model' => $model,
                'body' => $body,
            ]);

            return null;
        }

        $image = $body['data'][0];

        return $this->resolveImagePathFromPayload(
            url: isset($image['url']) ? (string) $image['url'] : null,
            base64: isset($image['b64_json']) ? (string) $image['b64_json'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function buildGenerationParameters(string $prompt, string $model): array
    {
        if ($model === 'dall-e-3') {
            return [
                'model' => 'dall-e-3',
                'prompt' => $prompt,
                'n' => 1,
                'size' => '1792x1024',
                'quality' => 'hd',
                'style' => 'vivid',
                'response_format' => 'b64_json',
            ];
        }

        return [
            'model' => $model,
            'prompt' => $prompt,
            'n' => 1,
            'size' => '1536x1024',
            'quality' => 'high',
            'output_format' => 'jpeg',
        ];
    }

    private function resolveImagePathFromPayload(?string $url, ?string $base64): ?string
    {
        if (filled($base64)) {
            return $this->storeBase64Image($base64, 'jpg');
        }

        if (filled($url)) {
            return $this->downloadToTemp($url);
        }

        return null;
    }

    private function langdockBaseUri(): string
    {
        $base = rtrim((string) config('services.langdock.base_api_url', 'https://api.langdock.com/'), '/');

        return str_ends_with($base, '/openai/eu/v1') ? $base : $base.'/openai/eu/v1';
    }

    /**
     * @param  list<string>  $referenceImageUrls
     * @return list<string>
     */
    private function downloadReferenceImages(array $referenceImageUrls): array
    {
        $paths = [];

        foreach ($referenceImageUrls as $url) {
            $localPath = $this->downloadToTemp($url);

            if ($localPath !== null) {
                $paths[] = $localPath;
            }
        }

        return $paths;
    }

    private function downloadToTemp(string $url): ?string
    {
        try {
            $response = Http::timeout(20)->get($url);

            if (! $response->successful()) {
                return null;
            }

            $extension = str_contains((string) $response->header('Content-Type'), 'png') ? 'png' : 'jpg';
            $path = tempnam(sys_get_temp_dir(), 'tippspiel-crest-').'.'.$extension;
            file_put_contents($path, $response->body());
            $this->tempFiles[] = $path;

            return $path;
        } catch (Throwable $e) {
            Log::warning('Tippspiel: Wappen konnte nicht geladen werden.', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function storeBase64Image(string $base64, string $extension): string
    {
        $path = tempnam(sys_get_temp_dir(), 'tippspiel-news-image-').'.'.$extension;
        file_put_contents($path, base64_decode($base64));
        $this->tempFiles[] = $path;

        return $path;
    }
}
