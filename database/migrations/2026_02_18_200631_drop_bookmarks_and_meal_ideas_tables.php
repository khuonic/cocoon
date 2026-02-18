<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('bookmarks');
        Schema::dropIfExists('meal_ideas');
    }

    public function down(): void
    {
        // Irreversible in dev context
    }
};
