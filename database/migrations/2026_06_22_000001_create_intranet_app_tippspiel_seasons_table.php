<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intranet_app_tippspiel_seasons', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('competition_code');
            $table->unsignedInteger('external_id')->nullable();
            $table->unsignedSmallInteger('season_year');
            $table->boolean('is_active')->default(false);
            $table->unsignedTinyInteger('points_exact_result')->default(3);
            $table->unsignedTinyInteger('points_correct_difference')->default(2);
            $table->unsignedTinyInteger('points_correct_tendency')->default(1);
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->timestamps();

            $table->unique(['competition_code', 'season_year'], 'tippspiel_seasons_code_year_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intranet_app_tippspiel_seasons');
    }
};
