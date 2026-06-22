<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intranet_app_tippspiel_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('season_id')
                ->constrained('intranet_app_tippspiel_seasons')
                ->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('total_points')->default(0);
            $table->timestamp('registered_at')->useCurrent();
            $table->timestamps();

            $table->unique(['season_id', 'user_id']);
            $table->index(['season_id', 'total_points']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intranet_app_tippspiel_participants');
    }
};
