<?php

namespace Database\Seeders;

use App\Enums\ShoppingItemCategory;
use App\Models\ShoppingList;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ShoppingListSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();

        if (! $user) {
            return;
        }

        $list = ShoppingList::create([
            'name' => 'Course appartement',
            'is_template' => true,
            'is_active' => false,
            'uuid' => Str::uuid(),
        ]);

        $items = [
            ['name' => 'Tomates', 'category' => ShoppingItemCategory::FruitsLegumes],
            ['name' => 'Lait', 'category' => ShoppingItemCategory::Frais],
            ['name' => 'Pâtes', 'category' => ShoppingItemCategory::EpicerieSalee],
            ['name' => 'Chocolat noir', 'category' => ShoppingItemCategory::EpicerieSucree],
            ['name' => 'Eau (pack)', 'category' => ShoppingItemCategory::Boissons],
            ['name' => 'Shampooing', 'category' => ShoppingItemCategory::Hygiene],
            ['name' => 'Papier toilette', 'category' => ShoppingItemCategory::Maison],
        ];

        foreach ($items as $item) {
            $list->items()->create([
                'name' => $item['name'],
                'category' => $item['category'],
                'is_checked' => false,
                'added_by' => $user->id,
                'uuid' => Str::uuid(),
            ]);
        }
    }
}
