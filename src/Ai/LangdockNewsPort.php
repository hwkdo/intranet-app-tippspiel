<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTippspiel\Ai;

use GuzzleHttp\Client as GuzzleClient;
use Hwkdo\IntranetAppTippspiel\Contracts\TippspielAiNewsPortInterface;
use Hwkdo\IntranetAppTippspiel\Models\TippspielSettings;
use Illuminate\Support\Facades\Log;
use OpenAI;
use Throwable;

class LangdockNewsPort implements TippspielAiNewsPortInterface
{
    public function generateMatchdayNews(string $prompt): string
    {
        $settings = TippspielSettings::resolvedAppSettings();
        $model = filled($settings->aiNewsModel)
            ? $settings->aiNewsModel
            : 'gpt-4o';

        try {
            $client = OpenAI::factory()
                ->withApiKey((string) config('services.langdock.api_key'))
                ->withHttpClient(new GuzzleClient(['timeout' => 60, 'connect_timeout' => 10]))
                ->withBaseUri($this->langdockBaseUri())
                ->make();

            $result = $client->chat()->create([
                'model' => $model,
                'messages' => [['role' => 'user', 'content' => $prompt]],
                'max_tokens' => 1000,
            ]);

            return $result->choices[0]->message->content ?? '';
        } catch (Throwable $e) {
            Log::error('Tippspiel: Langdock KI-News-Textgenerierung fehlgeschlagen.', [
                'model' => $model,
                'error' => $e->getMessage(),
            ]);

            return '';
        }
    }

    private function langdockBaseUri(): string
    {
        $base = rtrim((string) config('services.langdock.base_api_url', 'https://api.langdock.com/'), '/');

        return str_ends_with($base, '/openai/eu/v1') ? $base : $base.'/openai/eu/v1';
    }
}
