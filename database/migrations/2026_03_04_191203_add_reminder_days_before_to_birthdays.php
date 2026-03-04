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
        Schema::table('birthdays', function (Blueprint $table) {
            $table->integer('reminder_days_before')->nullable()->after('date');
        });
    }

    public function down(): void
    {
        Schema::table('birthdays', function (Blueprint $table) {
            $table->dropColumn('reminder_days_before');
        });
    }
};
