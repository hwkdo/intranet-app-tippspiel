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
        return static::orderBy('version', 'desc')->first();
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
