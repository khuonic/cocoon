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
            'is_active' => true,
            'uuid' => Str::uuid(),
        ]);

        $itemsByCategory = [
            ShoppingItemCategory::FruitsLegumes->value => [
                'Tomates',
            ],
            ShoppingItemCategory::Frais->value => [
                'Lait',
            ],
            ShoppingItemCategory::EpicerieSalee->value => [
                'Pâtes',
            ],
            ShoppingItemCategory::EpicerieSucree->value => [
                'Chocolat noir',
            ],
            ShoppingItemCategory::Boissons->value => [
                'Eau (pack)',
            ],
            ShoppingItemCategory::Hygiene->value => [
                'Shampooing',
            ],
            ShoppingItemCategory::Maison->value => [
                'Papier toilette',
            ],
            ShoppingItemCategory::Autre->value => [
                // ...
            ],
        ];

        foreach ($itemsByCategory as $categoryValue => $names) {
            foreach ($names as $name) {
                $list->items()->create([
                    'name' => $name,
                    'category' => $categoryValue,
                    'is_checked' => false,
                    'added_by' => $user->id,
                    'uuid' => Str::uuid(),
                ]);
            }
        }
    }
}
