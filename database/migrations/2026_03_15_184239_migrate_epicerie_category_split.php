<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('shopping_items')
            ->where('category', 'epicerie')
            ->update(['category' => 'epicerie_salee']);
    }

    public function down(): void
    {
        DB::table('shopping_items')
            ->whereIn('category', ['epicerie_salee', 'epicerie_sucree'])
            ->update(['category' => 'epicerie']);
    }
};
