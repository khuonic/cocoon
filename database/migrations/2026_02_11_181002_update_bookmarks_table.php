<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookmarks', function (Blueprint $table) {
            $table->dropColumn(['price', 'image_url']);
            $table->renameColumn('notes', 'description');
        });

        Schema::table('bookmarks', function (Blueprint $table) {
            $table->string('category')->nullable()->after('description');
            $table->boolean('is_favorite')->default(false)->after('category');
        });
    }

    public function down(): void
    {
        Schema::table('bookmarks', function (Blueprint $table) {
            $table->dropColumn(['category', 'is_favorite']);
            $table->renameColumn('description', 'notes');
        });

        Schema::table('bookmarks', function (Blueprint $table) {
            $table->decimal('price', 10, 2)->nullable()->after('title');
            $table->string('image_url')->nullable()->after('price');
        });
    }
};
