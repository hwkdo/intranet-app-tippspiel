<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTippspiel\Ai;

use Hwkdo\IntranetAppBase\Contracts\IntranetAiGatewayInterface;
use Hwkdo\IntranetAppBase\Data\AiRequestContext;
use Hwkdo\IntranetAppBase\Enums\AiCapability;
use Hwkdo\IntranetAppTippspiel\Contracts\TippspielAiNewsPortInterface;
use Illuminate\Support\Facades\Log;
use Throwable;

class GatewayTippspielNewsPort implements TippspielAiNewsPortInterface
{
    private const string SYSTEM_PROMPT = <<<'PROMPT'
Du schreibst kurze News-Artikel für ein internes Firmen-Fußball-Tippspiel.
Antworte auf Deutsch.
Format: erste Zeile = prägnante Überschrift, danach eine Leerzeile, danach der Artikeltext (2–4 Absätze).
Keine Markdown-Überschriften, keine Hashtags.
PROMPT;

    public function __construct(
        private readonly IntranetAiGatewayInterface $gateway,
    ) {}

    public function generateMatchdayNews(string $prompt): string
    {
        try {
            $content = $this->gateway->chat(
                [
                    ['role' => 'system', 'content' => self::SYSTEM_PROMPT],
                    ['role' => 'user', 'content' => $prompt],
                ],
                new AiRequestContext(
                    appIdentifier: 'tippspiel',
                    capability: AiCapability::Text,
                ),
                ['max_tokens' => 4096],
            )->content;

            return trim($content);
        } catch (Throwable $e) {
            Log::error('Tippspiel: KI-News-Textgenerierung über Gateway fehlgeschlagen.', [
                'error' => $e->getMessage(),
            ]);

            return '';
        }
    }
}
