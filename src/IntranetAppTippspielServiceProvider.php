<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTippspiel;

use Hwkdo\IntranetAppTippspiel\Contracts\FootballDataProviderInterface;
use Hwkdo\IntranetAppTippspiel\Contracts\TippspielAiNewsImagePortInterface;
use Hwkdo\IntranetAppTippspiel\Contracts\TippspielAiNewsPortInterface;
use Hwkdo\IntranetAppTippspiel\Enums\AiNewsProvider;
use Hwkdo\IntranetAppTippspiel\Models\TippspielSettings;
use Hwkdo\IntranetAppTippspiel\Providers\FootballDataOrgProvider;
use Illuminate\Console\Scheduling\Schedule;
use Livewire\Livewire;
use Livewire\Volt\Volt;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class IntranetAppTippspielServiceProvider extends PackageServiceProvider
{
    public function register(): void
    {
        parent::register();

        $this->app->singleton(FootballDataProviderInterface::class, FootballDataOrgProvider::class);

        $this->app->bind(TippspielAiNewsPortInterface::class, function ($app): TippspielAiNewsPortInterface {
            $settings = TippspielSettings::resolvedAppSettings();
            $provider = AiNewsProvider::tryFrom($settings->aiNewsProvider) ?? AiNewsProvider::Langdock;

            return match ($provider) {
                AiNewsProvider::OpenWebUi => $app->make(\Hwkdo\IntranetAppTippspiel\Ai\OpenWebUiNewsPort::class),
                default => $app->make(\Hwkdo\IntranetAppTippspiel\Ai\LangdockNewsPort::class),
            };
        });

        $this->app->bind(TippspielAiNewsImagePortInterface::class, function ($app): TippspielAiNewsImagePortInterface {
            $settings = TippspielSettings::resolvedAppSettings();
            $provider = AiNewsProvider::tryFrom($settings->aiNewsProvider) ?? AiNewsProvider::Langdock;

            return match ($provider) {
                AiNewsProvider::OpenWebUi => $app->make(\Hwkdo\IntranetAppTippspiel\Ai\UnsupportedNewsImagePort::class),
                default => $app->make(\Hwkdo\IntranetAppTippspiel\Ai\LangdockNewsImagePort::class),
            };
        });
    }

    public function configurePackage(Package $package): void
    {
        $package
            ->name('intranet-app-tippspiel')
            ->hasConfigFile()
            ->hasViews()
            ->discoversMigrations()
            ->hasCommands([
                \Hwkdo\IntranetAppTippspiel\Commands\SyncMatchesCommand::class,
                \Hwkdo\IntranetAppTippspiel\Commands\EvaluateTipsCommand::class,
                \Hwkdo\IntranetAppTippspiel\Commands\GenerateMatchdayNewsCommand::class,
            ]);
    }

    public function boot(): void
    {
        parent::boot();

        Livewire::addNamespace(
            namespace: 'intranet-app-tippspiel',
            viewPath: __DIR__.'/../resources/views/livewire',
            classNamespace: 'Hwkdo\IntranetAppTippspiel\Livewire',
            classPath: __DIR__.'/../src/Livewire',
            classViewPath: __DIR__.'/../resources/views/livewire',
        );

        // Volt-basierte Widget-Komponenten registrieren
        Livewire::addComponent(
            name: 'intranet-app-tippspiel::apps.tippspiel.widgets.rangliste',
            viewPath: __DIR__.'/../resources/views/livewire/apps/tippspiel/widgets/rangliste.blade.php'
        );
        Livewire::addComponent(
            name: 'intranet-app-tippspiel::apps.tippspiel.widgets.naechste-spiele',
            viewPath: __DIR__.'/../resources/views/livewire/apps/tippspiel/widgets/naechste-spiele.blade.php'
        );

        $this->app->booted(function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
            Volt::mount(__DIR__.'/../resources/views/livewire');
        });

        $this->app->resolving(Schedule::class, function (): void {
            require __DIR__.'/../routes/console.php';
        });
    }
}
