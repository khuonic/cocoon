<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shopping_lists', function (Blueprint $table): void {
            $table->dropColumn('is_template');
        });
    }

    public function down(): void
    {
        Schema::table('shopping_lists', function (Blueprint $table): void {
            $table->boolean('is_template')->default(false)->after('name');
        });
    }
};
