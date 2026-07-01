<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTippspiel\Ai;

use Hwkdo\IntranetAppTippspiel\Contracts\TippspielAiNewsPortInterface;
use Hwkdo\IntranetAppTippspiel\Models\TippspielSettings;
use OpenAI;

class OpenWebUiNewsPort implements TippspielAiNewsPortInterface
{
    public function generateMatchdayNews(string $prompt): string
    {
        $settings = TippspielSettings::resolvedAppSettings();
        $model = filled($settings->aiNewsModel)
            ? $settings->aiNewsModel
            : (string) config('openwebui-api-laravel.default_model', 'gpt-oss:20b');

        $client = OpenAI::factory()
            ->withApiKey((string) config('openwebui-api-laravel.api_key'))
            ->withBaseUri((string) config('openwebui-api-laravel.base_api_url'))
            ->make();

        $result = $client->chat()->create([
            'model' => $model,
            'messages' => [['role' => 'user', 'content' => $prompt]],
            'max_tokens' => 1000,
        ]);

        return $result->choices[0]->message->content ?? '';
    }
}
