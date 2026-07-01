<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTippspiel\Livewire\Apps\Tippspiel\Admin;

use App\Models\Kategorie;
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
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Tippspiel Einstellungen')]
class Einstellungen extends Component
{
    public string $aiNewsProvider = 'langdock';

    public string $aiNewsModel = '';

    public bool $aiNewsAutoCreateAfterMatchday = false;

    public bool $aiNewsAutoPublish = false;

    public string $aiNewsPrompt = '';

    public ?int $aiNewsKategorieId = null;

    public ?int $aiNewsPublisherId = null;

    public bool $aiNewsImageAutoGenerate = false;

    public string $aiNewsImageModel = '';

    public string $aiNewsImagePrompt = '';

    public int $defaultPointsExactResult = 3;

    public int $defaultPointsCorrectDifference = 2;

    public int $defaultPointsCorrectTendency = 1;

    public bool $showDefaultPrompt = false;

    public bool $showDefaultImagePrompt = false;

    public bool $showPromptPreviewModal = false;

    public ?int $promptPreviewSeasonId = null;

    public ?int $promptPreviewMatchday = null;

    public string $promptPreviewText = '';

    public string $promptPreviewError = '';

    public function mount(): void
    {
        $settings = TippspielSettings::resolvedAppSettings();

        $this->aiNewsProvider = $settings->aiNewsProvider;
        $this->aiNewsModel = $settings->aiNewsModel;
        $this->aiNewsAutoCreateAfterMatchday = $settings->aiNewsAutoCreateAfterMatchday;
        $this->aiNewsAutoPublish = $settings->aiNewsAutoPublish;
        $this->aiNewsPrompt = $settings->aiNewsPrompt;
        $this->aiNewsKategorieId = $settings->aiNewsKategorieId > 0 ? $settings->aiNewsKategorieId : null;
        $this->aiNewsPublisherId = $settings->aiNewsPublisherId > 0 ? $settings->aiNewsPublisherId : null;
        $this->aiNewsImageAutoGenerate = $settings->aiNewsImageAutoGenerate;
        $this->aiNewsImageModel = $settings->aiNewsImageModel;
        $this->aiNewsImagePrompt = $settings->aiNewsImagePrompt;
        $this->defaultPointsExactResult = $settings->defaultPointsExactResult;
        $this->defaultPointsCorrectDifference = $settings->defaultPointsCorrectDifference;
        $this->defaultPointsCorrectTendency = $settings->defaultPointsCorrectTendency;
    }

    public function save(): void
    {
        $this->validate([
            'aiNewsProvider' => 'required|in:langdock,openwebui',
            'aiNewsModel' => 'nullable|string|max:100',
            'aiNewsAutoCreateAfterMatchday' => 'boolean',
            'aiNewsAutoPublish' => 'boolean',
            'aiNewsPrompt' => 'nullable|string|max:10000',
            'aiNewsKategorieId' => 'nullable|integer|exists:kategories,id',
            'aiNewsPublisherId' => 'nullable|integer|exists:users,id',
            'aiNewsImageAutoGenerate' => 'boolean',
            'aiNewsImageModel' => 'nullable|string|max:100',
            'aiNewsImagePrompt' => 'nullable|string|max:10000',
            'defaultPointsExactResult' => 'required|integer|min:0|max:10',
            'defaultPointsCorrectDifference' => 'required|integer|min:0|max:10',
            'defaultPointsCorrectTendency' => 'required|integer|min:0|max:10',
        ]);

        $settings = new AppSettings(
            aiNewsProvider: $this->aiNewsProvider,
            aiNewsModel: $this->aiNewsModel,
            aiNewsAutoCreateAfterMatchday: $this->aiNewsAutoCreateAfterMatchday,
            aiNewsAutoPublish: $this->aiNewsAutoPublish,
            aiNewsPrompt: $this->aiNewsPrompt,
            aiNewsKategorieId: $this->aiNewsKategorieId ?? 0,
            aiNewsPublisherId: $this->aiNewsPublisherId ?? 0,
            aiNewsImageAutoGenerate: $this->aiNewsImageAutoGenerate,
            aiNewsImageModel: $this->aiNewsImageModel,
            aiNewsImagePrompt: $this->aiNewsImagePrompt,
            defaultPointsExactResult: $this->defaultPointsExactResult,
            defaultPointsCorrectDifference: $this->defaultPointsCorrectDifference,
            defaultPointsCorrectTendency: $this->defaultPointsCorrectTendency,
        );

        TippspielSettings::persistAppSettings($settings);

        $this->dispatch('flash', type: 'success', message: 'Einstellungen gespeichert.');
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
        $this->promptPreviewMatchday = $this->defaultPreviewMatchday($season);
        $this->promptPreviewText = '';
        $this->promptPreviewError = '';
        $this->showPromptPreviewModal = true;
    }

    public function updatedPromptPreviewSeasonId(): void
    {
        $season = $this->promptPreviewSeasonId !== null
            ? Season::find($this->promptPreviewSeasonId)
            : null;

        $this->promptPreviewMatchday = $this->defaultPreviewMatchday($season);
        $this->promptPreviewText = '';
        $this->promptPreviewError = '';
    }

    public function generatePromptPreview(MatchdayNewsService $newsService): void
    {
        $this->validate([
            'promptPreviewSeasonId' => 'required|integer|exists:intranet_app_tippspiel_seasons,id',
            'promptPreviewMatchday' => 'required|integer|min:1',
        ]);

        $season = Season::findOrFail($this->promptPreviewSeasonId);
        $template = filled($this->aiNewsPrompt) ? $this->aiNewsPrompt : null;

        $preview = $newsService->buildPromptPreview($season, $this->promptPreviewMatchday, $template);

        if ($preview === null) {
            $this->promptPreviewText = '';
            $this->promptPreviewError = 'Für diesen Spieltag liegen keine abgeschlossenen Spiele vor.';

            return;
        }

        $this->promptPreviewError = '';
        $this->promptPreviewText = $preview;
    }

    public function generateImagePromptPreview(MatchdayNewsImageService $imageService): void
    {
        $this->validate([
            'promptPreviewSeasonId' => 'required|integer|exists:intranet_app_tippspiel_seasons,id',
            'promptPreviewMatchday' => 'required|integer|min:1',
        ]);

        $season = Season::findOrFail($this->promptPreviewSeasonId);
        $template = filled($this->aiNewsImagePrompt) ? $this->aiNewsImagePrompt : null;

        $preview = $imageService->buildPromptPreview($season, $this->promptPreviewMatchday, $template);

        if ($preview === null) {
            $this->promptPreviewText = '';
            $this->promptPreviewError = 'Für diesen Spieltag liegen keine abgeschlossenen Spiele vor.';

            return;
        }

        $this->promptPreviewError = '';
        $this->promptPreviewText = $preview;
    }

    public function render(TipEvaluationService $evaluationService): View
    {
        $userModel = TippspielModels::user();
        $previewSeason = $this->promptPreviewSeasonId !== null
            ? Season::find($this->promptPreviewSeasonId)
            : null;

        return view('intranet-app-tippspiel::livewire.apps.tippspiel.admin.einstellungen', [
            'kategorien' => Kategorie::query()->orderBy('name')->pluck('name', 'id'),
            'publishers' => $userModel::query()
                ->orderBy('vorname')
                ->orderBy('nachname')
                ->get()
                ->mapWithKeys(fn ($user) => [$user->id => $user->name]),
            'defaultPrompt' => MatchdayNewsPromptBuilder::DEFAULT_PROMPT,
            'defaultImagePrompt' => MatchdayNewsImagePromptBuilder::DEFAULT_PROMPT,
            'seasons' => Season::query()->orderByDesc('is_active')->orderByDesc('id')->get(),
            'previewMatchdays' => $previewSeason !== null
                ? $this->completedMatchdays($previewSeason, $evaluationService)
                : [],
            'usesCustomPrompt' => filled($this->aiNewsPrompt),
            'usesCustomImagePrompt' => filled($this->aiNewsImagePrompt),
        ]);
    }

    /**
     * @return list<int>
     */
    private function completedMatchdays(Season $season, TipEvaluationService $evaluationService): array
    {
        return $season->matches()
            ->whereNotNull('matchday')
            ->distinct()
            ->orderBy('matchday')
            ->pluck('matchday')
            ->filter(fn ($matchday) => $evaluationService->isMatchdayComplete($season, (int) $matchday))
            ->map(fn ($matchday) => (int) $matchday)
            ->values()
            ->all();
    }

    private function defaultPreviewMatchday(?Season $season): ?int
    {
        if ($season === null) {
            return null;
        }

        $evaluationService = app(TipEvaluationService::class);
        $matchdays = $this->completedMatchdays($season, $evaluationService);

        if ($matchdays === []) {
            return null;
        }

        return $matchdays[array_key_last($matchdays)];
    }
}
