<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTippspiel\Models;

use Hwkdo\IntranetAppTippspiel\Data\AppSettings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class TippspielSettings extends Model
{
    protected $table = 'intranet_app_tippspiel_settings';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'settings' => AppSettings::class.':default',
        ];
    }

    public static function current(): ?TippspielSettings
    {
        return static::query()
            ->orderByDesc('version')
            ->orderByDesc('id')
            ->first();
    }

    public static function persistAppSettings(AppSettings $settings): TippspielSettings
    {
        $current = static::current();

        if ($current !== null) {
            $current->update(['settings' => $settings]);

            return $current->refresh();
        }

        return static::create([
            'version' => 1,
            'settings' => $settings,
        ]);
    }

    public static function resolvedAppSettings(): AppSettings
    {
        if (! Schema::hasTable((new static)->getTable())) {
            return new AppSettings;
        }

        $row = static::current();

        return $row?->settings instanceof AppSettings ? $row->settings : new AppSettings;
    }
}
