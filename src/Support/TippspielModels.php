<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppTippspiel\Support;

use Illuminate\Database\Eloquent\Model;

class TippspielModels
{
    /**
     * @return class-string<Model>
     */
    public static function gvp(): string
    {
        return (string) config('intranet-app-tippspiel.gvp_model');
    }

    /**
     * @return class-string<Model>
     */
    public static function user(): string
    {
        return (string) config('intranet-app-tippspiel.user_model');
    }
}
