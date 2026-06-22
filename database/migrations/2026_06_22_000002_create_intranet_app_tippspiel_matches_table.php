<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intranet_app_tippspiel_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('season_id')
                ->constrained('intranet_app_tippspiel_seasons')
                ->cascadeOnDelete();
            $table->unsignedInteger('external_id')->unique();
            $table->unsignedSmallInteger('matchday')->nullable();
            $table->string('stage')->default('REGULAR_SEASON');
            $table->string('group')->nullable();
            $table->string('home_team_name');
            $table->string('away_team_name');
            $table->string('home_team_crest_url')->nullable();
            $table->string('away_team_crest_url')->nullable();
            $table->dateTime('kickoff_at')->nullable();
            $table->string('status')->default('SCHEDULED');
            $table->unsignedTinyInteger('home_score')->nullable();
            $table->unsignedTinyInteger('away_score')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->index(['season_id', 'matchday']);
            $table->index(['season_id', 'status']);
            $table->index('kickoff_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intranet_app_tippspiel_matches');
    }
};
