<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTippspiel;

use Hwkdo\IntranetAppBase\Interfaces\IntranetAppInterface;
use Hwkdo\IntranetAppBase\Interfaces\ProvidesDashboardWidgetsInterface;
use Hwkdo\IntranetAppBase\Interfaces\ProvidesTasksInterface;
use Hwkdo\IntranetAppTippspiel\Dashboard\TippspielDashboardWidgetProvider;
use Hwkdo\IntranetAppTippspiel\Tasks\NaechstenSpieltippenTaskProvider;
use Illuminate\Support\Collection;

class IntranetAppTippspiel implements IntranetAppInterface, ProvidesTasksInterface, ProvidesDashboardWidgetsInterface
{
    public static function app_name(): string
    {
        return 'Tippspiel';
    }

    public static function app_icon(): string
    {
        return 'trophy';
    }

    public static function identifier(): string
    {
        return 'tippspiel';
    }

    public static function roles_admin(): Collection
    {
        return collect(config('intranet-app-tippspiel.roles.admin'));
    }

    public static function roles_user(): Collection
    {
        return collect(config('intranet-app-tippspiel.roles.user'));
    }

    public static function userSettingsClass(): ?string
    {
        return null;
    }

    public static function appSettingsClass(): ?string
    {
        return \Hwkdo\IntranetAppTippspiel\Data\AppSettings::class;
    }

    public static function mcpServers(): array
    {
        return [];
    }

    public static function taskProviders(): array
    {
        return [
            NaechstenSpieltippenTaskProvider::class,
        ];
    }

    public static function dashboardWidgetProviders(): array
    {
        return [
            TippspielDashboardWidgetProvider::class,
        ];
    }
}
