<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intranet_app_tippspiel_tips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_id')
                ->constrained('intranet_app_tippspiel_participants')
                ->cascadeOnDelete();
            $table->foreignId('match_id')
                ->constrained('intranet_app_tippspiel_matches')
                ->cascadeOnDelete();
            $table->unsignedTinyInteger('home_score_tip');
            $table->unsignedTinyInteger('away_score_tip');
            $table->unsignedTinyInteger('points_earned')->nullable();
            $table->timestamps();

            $table->unique(['participant_id', 'match_id']);
            $table->index('match_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intranet_app_tippspiel_tips');
    }
};
