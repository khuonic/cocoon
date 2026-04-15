<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notes', function (Blueprint $table) {
            $table->boolean('is_personal')->default(false)->after('is_pinned');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete()->after('is_personal');
        });
    }

    public function down(): void
    {
        Schema::table('notes', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['is_personal', 'user_id']);
        });
    }
};
