<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('url')->nullable();
            $table->unsignedSmallInteger('prep_time')->nullable();
            $table->unsignedSmallInteger('cook_time')->nullable();
            $table->unsignedSmallInteger('servings')->nullable();
            $table->json('tags')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipes');
    }
};
