<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('meal_plans');
    }

    public function down(): void
    {
        Schema::create('meal_plans', function ($table) {
            $table->id();
            $table->date('date');
            $table->string('meal_type');
            $table->string('description');
            $table->foreignId('cooked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->uuid('uuid')->unique();
            $table->timestamps();
        });
    }
};
