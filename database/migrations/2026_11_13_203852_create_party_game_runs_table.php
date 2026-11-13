<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('party_game_runs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('party_player_id')->constrained('party_players')->cascadeOnDelete();
            $table->string('game_slug')->index();
            $table->json('answers');
            $table->unsignedInteger('score')->default(0);
            $table->unsignedInteger('max_score');
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamps();

            $table->index(['game_slug', 'score', 'duration_ms']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('party_game_runs');
    }
};
