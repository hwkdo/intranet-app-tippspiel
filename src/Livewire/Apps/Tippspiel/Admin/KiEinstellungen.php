<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTippspiel\Livewire\Apps\Tippspiel\Admin;

use App\Models\Kategorie;
use Flux\Flux;
use Hwkdo\IntranetAppBase\Contracts\AiConfigResolverInterface;
use Hwkdo\IntranetAppBase\Contracts\IntranetBaseAiConfigSourceInterface;
use Hwkdo\IntranetAppBase\Enums\AiCapability;
use Hwkdo\IntranetAppBase\Enums\AiProvider;
use Hwkdo\IntranetAppTippspiel\Data\AppSettings;
use Hwkdo\IntranetAppTippspiel\Models\Season;
use Hwkdo\IntranetAppTippspiel\Models\TippspielSettings;
use Hwkdo\IntranetAppTippspiel\Services\MatchdayNewsImageService;
use Hwkdo\IntranetAppTippspiel\Services\MatchdayNewsService;
use Hwkdo\IntranetAppTippspiel\Services\TipEvaluationService;
use Hwkdo\IntranetAppTippspiel\Support\MatchdayNewsImagePromptBuilder;
use Hwkdo\IntranetAppTippspiel\Support\MatchdayNewsPromptBuilder;
use Hwkdo\IntranetAppTippspiel\Support\TippspielModels;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;

class KiEinstellungen extends Component
{
    public string $aiTextProviderOverride = '';

    public string $aiTextModelOverride = '';

    public string $aiImageProviderOverride = '';

    public string $aiImageModelOverride = '';

    public bool $aiNewsAutoCreateAfterMatchday = false;

    public bool $aiNewsAutoPublish = false;

    public string $aiNewsPrompt = '';

    public ?int $aiNewsKategorieId = null;

    public ?int $aiNewsPublisherId = null;

    public bool $aiNewsImageAutoGenerate = false;

    public string $aiNewsImagePrompt = '';

    public bool $showDefaultPrompt = false;

    public bool $showDefaultImagePrompt = false;

    public bool $showPromptPreviewModal = false;

    public ?int $promptPreviewSeasonId = null;

    public ?string $promptPreviewRoundKey = null;

    public string $promptPreviewText = '';

    public string $promptPreviewError = '';

    public function mount(): void
    {
        $settings = TippspielSettings::resolvedAppSettings();

        $this->aiTextProviderOverride = $settings->aiTextProviderOverride?->value
            ?? $settings->legacyTextProvider()?->value
            ?? '';
        $this->aiTextModelOverride = $settings->textModelOverride()
            ?? $settings->legacyTextModel()
            ?? '';
        $this->aiImageProviderOverride = $settings->aiImageProviderOverride?->value ?? '';
        $this->aiImageModelOverride = $settings->imageModelOverride()
            ?? $settings->legacyImageModel()
            ?? '';
        $this->aiNewsAutoCreateAfterMatchday = $settings->aiNewsAutoCreateAfterMatchday;
        $this->aiNewsAutoPublish = $settings->aiNewsAutoPublish;
        $this->aiNewsPrompt = $settings->aiNewsPrompt;
        $this->aiNewsKategorieId = $settings->aiNewsKategorieId > 0 ? $settings->aiNewsKategorieId : null;
        $this->aiNewsPublisherId = $settings->aiNewsPublisherId > 0 ? $settings->aiNewsPublisherId : null;
        $this->aiNewsImageAutoGenerate = $settings->aiNewsImageAutoGenerate;
        $this->aiNewsImagePrompt = $settings->aiNewsImagePrompt;
    }

    public function save(): void
    {
        $this->validate([
            'aiTextProviderOverride' => ['nullable', 'string', Rule::enum(AiProvider::class)],
            'aiTextModelOverride' => 'nullable|string|max:100',
            'aiImageProviderOverride' => ['nullable', 'string', Rule::enum(AiProvider::class)],
            'aiImageModelOverride' => 'nullable|string|max:100',
            'aiNewsAutoCreateAfterMatchday' => 'boolean',
            'aiNewsAutoPublish' => 'boolean',
            'aiNewsPrompt' => 'nullable|string|max:10000',
            'aiNewsKategorieId' => 'nullable|integer|exists:kategories,id',
            'aiNewsPublisherId' => 'nullable|integer|exists:users,id',
            'aiNewsImageAutoGenerate' => 'boolean',
            'aiNewsImagePrompt' => 'nullable|string|max:10000',
        ]);

        $current = TippspielSettings::resolvedAppSettings();

        $settings = AppSettings::from(array_merge($current->toArray(), [
            'aiNewsProvider' => '',
            'aiNewsModel' => '',
            'aiNewsAutoCreateAfterMatchday' => $this->aiNewsAutoCreateAfterMatchday,
            'aiNewsAutoPublish' => $this->aiNewsAutoPublish,
            'aiNewsPrompt' => $this->aiNewsPrompt,
            'aiNewsKategorieId' => $this->aiNewsKategorieId ?? 0,
            'aiNewsPublisherId' => $this->aiNewsPublisherId ?? 0,
            'aiNewsImageAutoGenerate' => $this->aiNewsImageAutoGenerate,
            'aiNewsImageModel' => '',
            'aiNewsImagePrompt' => $this->aiNewsImagePrompt,
            'aiTextProviderOverride' => $this->parseProviderOverride($this->aiTextProviderOverride),
            'aiTextModelOverride' => $this->blankToNull($this->aiTextModelOverride),
            'aiImageProviderOverride' => $this->parseProviderOverride($this->aiImageProviderOverride),
            'aiImageModelOverride' => $this->blankToNull($this->aiImageModelOverride),
        ]));

        TippspielSettings::persistAppSettings($settings);

        Flux::toast(
            heading: 'Gespeichert',
            text: 'KI-Einstellungen wurden gespeichert.',
            variant: 'success',
        );
    }

    public function resetPrompt(): void
    {
        $this->aiNewsPrompt = '';
    }

    public function resetImagePrompt(): void
    {
        $this->aiNewsImagePrompt = '';
    }

    public function toggleDefaultPrompt(): void
    {
        $this->showDefaultPrompt = ! $this->showDefaultPrompt;
    }

    public function toggleDefaultImagePrompt(): void
    {
        $this->showDefaultImagePrompt = ! $this->showDefaultImagePrompt;
    }

    public function openPromptPreviewModal(): void
    {
        $season = Season::query()
            ->orderByDesc('is_active')
            ->orderByDesc('id')
            ->first();

        $this->promptPreviewSeasonId = $season?->id;
        $this->promptPreviewRoundKey = $this->defaultPreviewRoundKey($season);
        $this->promptPreviewText = '';
        $this->promptPreviewError = '';
        $this->showPromptPreviewModal = true;
    }

    public function updatedPromptPreviewSeasonId(): void
    {
        $season = $this->promptPreviewSeasonId !== null
            ? Season::find($this->promptPreviewSeasonId)
            : null;

        $this->promptPreviewRoundKey = $this->defaultPreviewRoundKey($season);
        $this->promptPreviewText = '';
        $this->promptPreviewError = '';
    }

    public function generatePromptPreview(MatchdayNewsService $newsService): void
    {
        $this->validate([
            'promptPreviewSeasonId' => 'required|integer|exists:intranet_app_tippspiel_seasons,id',
            'promptPreviewRoundKey' => 'required|string|max:100',
        ]);

        $season = Season::findOrFail($this->promptPreviewSeasonId);
        $template = filled($this->aiNewsPrompt) ? $this->aiNewsPrompt : null;

        $preview = $newsService->buildPromptPreview($season, $this->promptPreviewRoundKey, $template);

        if ($preview === null) {
            $this->promptPreviewText = '';
            $this->promptPreviewError = 'Für diese Runde liegen keine abgeschlossenen Spiele vor.';

            return;
        }

        $this->promptPreviewError = '';
        $this->promptPreviewText = $preview;
    }

    public function generateImagePromptPreview(MatchdayNewsImageService $imageService): void
    {
        $this->validate([
            'promptPreviewSeasonId' => 'required|integer|exists:intranet_app_tippspiel_seasons,id',
            'promptPreviewRoundKey' => 'required|string|max:100',
        ]);

        $season = Season::findOrFail($this->promptPreviewSeasonId);
        $template = filled($this->aiNewsImagePrompt) ? $this->aiNewsImagePrompt : null;

        $preview = $imageService->buildPromptPreview($season, $this->promptPreviewRoundKey, $template);

        if ($preview === null) {
            $this->promptPreviewText = '';
            $this->promptPreviewError = 'Für diese Runde liegen keine abgeschlossenen Spiele vor.';

            return;
        }

        $this->promptPreviewError = '';
        $this->promptPreviewText = $preview;
    }

    public function render(
        TipEvaluationService $evaluationService,
        IntranetBaseAiConfigSourceInterface $baseAiConfig,
        AiConfigResolverInterface $aiConfigResolver,
    ): View {
        $userModel = TippspielModels::user();
        $previewSeason = $this->promptPreviewSeasonId !== null
            ? Season::find($this->promptPreviewSeasonId)
            : null;

        $baseTextModel = $baseAiConfig->textModel() ?? 'Provider-Standard';
        $baseImageModel = $baseAiConfig->imageModel() ?? 'Provider-Standard';
        $effectiveText = $aiConfigResolver->resolve('tippspiel', AiCapability::Text);
        $effectiveImage = $aiConfigResolver->resolve('tippspiel', AiCapability::Image);

        return view('intranet-app-tippspiel::livewire.apps.tippspiel.admin.ki-einstellungen', [
            'kategorien' => Kategorie::query()->orderBy('name')->pluck('name', 'id'),
            'publishers' => $userModel::query()
                ->orderBy('vorname')
                ->orderBy('nachname')
                ->get()
                ->mapWithKeys(fn ($user) => [$user->id => $user->name]),
            'defaultPrompt' => MatchdayNewsPromptBuilder::DEFAULT_PROMPT,
            'defaultImagePrompt' => MatchdayNewsImagePromptBuilder::DEFAULT_PROMPT,
            'seasons' => Season::query()->orderByDesc('is_active')->orderByDesc('id')->get(),
            'previewRounds' => $previewSeason !== null
                ? $this->completedRounds($previewSeason, $evaluationService)
                : [],
            'usesCustomPrompt' => filled($this->aiNewsPrompt),
            'usesCustomImagePrompt' => filled($this->aiNewsImagePrompt),
            'baseAiTextSummary' => $baseAiConfig->textProvider()->label().' / '.$baseTextModel,
            'baseAiImageSummary' => $baseAiConfig->imageProvider()->label().' / '.$baseImageModel,
            'effectiveAiTextSummary' => $effectiveText->provider->label().' / '.($effectiveText->model ?? 'Provider-Standard'),
            'effectiveAiImageSummary' => $effectiveImage->provider->label().' / '.($effectiveImage->model ?? 'Provider-Standard'),
        ]);
    }

    /**
     * @return list<array{key: string, label: string}>
     */
    private function completedRounds(Season $season, TipEvaluationService $evaluationService): array
    {
        return $season->availableRounds(tippableOnly: false)
            ->filter(fn ($round) => $evaluationService->isRoundComplete($season, $round->key))
            ->map(fn ($round) => ['key' => $round->key, 'label' => $round->label])
            ->values()
            ->all();
    }

    private function defaultPreviewRoundKey(?Season $season): ?string
    {
        if ($season === null) {
            return null;
        }

        $evaluationService = app(TipEvaluationService::class);
        $rounds = $this->completedRounds($season, $evaluationService);

        if ($rounds === []) {
            return null;
        }

        return $rounds[array_key_last($rounds)]['key'];
    }

    private function parseProviderOverride(string $value): ?AiProvider
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            return null;
        }

        return AiProvider::from($trimmed);
    }

    private function blankToNull(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
