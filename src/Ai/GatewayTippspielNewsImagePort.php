<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTippspiel\Ai;

use Hwkdo\IntranetAppBase\Contracts\IntranetAiGatewayInterface;
use Hwkdo\IntranetAppBase\Data\AiImageOptions;
use Hwkdo\IntranetAppBase\Data\AiRequestContext;
use Hwkdo\IntranetAppBase\Enums\AiCapability;
use Hwkdo\IntranetAppTippspiel\Contracts\TippspielAiNewsImagePortInterface;
use Hwkdo\IntranetAppTippspiel\Support\MatchdayNewsCrestCompositor;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class GatewayTippspielNewsImagePort implements TippspielAiNewsImagePortInterface
{
    /** @var list<string> */
    private array $tempFiles = [];

    public function __construct(
        private readonly IntranetAiGatewayInterface $gateway,
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
        $referencePaths = $this->downloadReferenceImages($referenceImageUrls);

        try {
            $result = $this->gateway->image(
                $prompt,
                new AiImageOptions(
                    size: '3:2',
                    quality: 'medium',
                    timeout: 300,
                ),
                new AiRequestContext(
                    appIdentifier: 'tippspiel',
                    capability: AiCapability::Image,
                ),
            );

            $imagePath = $this->storeBinaryImage($result->binary, 'png');
        } catch (Throwable $e) {
            Log::error('Tippspiel: KI-Titelbild-Generierung über Gateway fehlgeschlagen.', [
                'error' => $e->getMessage(),
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

    private function storeBinaryImage(string $binary, string $extension): string
    {
        $path = tempnam(sys_get_temp_dir(), 'tippspiel-news-image-').'.'.$extension;
        file_put_contents($path, $binary);
        $this->tempFiles[] = $path;

        return $path;
    }
}
