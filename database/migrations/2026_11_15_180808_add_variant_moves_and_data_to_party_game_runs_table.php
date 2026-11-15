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
        Schema::table('party_game_runs', function (Blueprint $table) {
            $table->string('variant', 30)->nullable()->after('game_slug');
            $table->unsignedInteger('moves')->nullable()->after('max_score');
            $table->json('data')->nullable()->after('answers');

            $table->index(['game_slug', 'variant']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('party_game_runs', function (Blueprint $table) {
            $table->dropIndex(['game_slug', 'variant']);
            $table->dropColumn(['variant', 'moves', 'data']);
        });
    }
};
