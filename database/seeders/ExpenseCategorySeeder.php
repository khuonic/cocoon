<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    /**
     * @var array<int, array{name: string, icon: string, color: string}>
     */
    private const CATEGORIES = [
        ['name' => 'Courses', 'icon' => 'shopping-cart', 'color' => '#4CAF50'],
        ['name' => 'Restaurant', 'icon' => 'utensils', 'color' => '#FF9800'],
        ['name' => 'Loyer', 'icon' => 'home', 'color' => '#2196F3'],
        ['name' => 'Loisirs', 'icon' => 'gamepad', 'color' => '#9C27B0'],
        ['name' => 'Santé', 'icon' => 'heart-pulse', 'color' => '#F44336'],
        ['name' => 'Transport', 'icon' => 'car', 'color' => '#607D8B'],
        ['name' => 'Abonnements', 'icon' => 'repeat', 'color' => '#00BCD4'],
        ['name' => 'Autre', 'icon' => 'ellipsis', 'color' => '#795548'],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (self::CATEGORIES as $index => $category) {
            ExpenseCategory::create([
                ...$category,
                'sort_order' => $index,
            ]);
        }
    }
}
