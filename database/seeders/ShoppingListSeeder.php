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
                'Citron',
                'Citron vet',
                'Oignon',
                'Courgettes',
                'Aubergines',
                'Poivrons',
                'Ail',
                'Courge',
                'Carotte',
                'Poireaux',
                'Grenade',
                'Champignons',
                'Echalotte',
                'Coriandre',
                'Persil',
                'Aneth',
                'Menthe',
                'Olives',
                'Salade',
            ],
            ShoppingItemCategory::Frais->value => [
                'Yaourts grecs',
                'Beurre',
                'Féta',
                'Épinards surgelés',
                'Fromage râpé',
                'Crème fraîche',
                'Crème entière',
                'Fromage à tartiner',
                'Pâte feuilletée',
                'Pâte brisée',
                'Parmesan',
                'Mozza',
                'Fromages',
                'Gyozas',
                'Nems',
                'Sriracha',
            ],
            ShoppingItemCategory::EpicerieSalee->value => [
                'Pâtes',
                'Riz',
                'Blé',
                'Lentille corail',
                'Pesto',
                'Huile d\'olive',
                'Huile neutre',
                'Huile sésame',
                'Vinaigre',
                'Ravioles',
                'Sauce tomate',
                'Soja sucrée',
                'Soja salée',
                'Steaks végésiens',
                'Lardons',
                'Chips',
                'Saucisson',
                'Soupe',
                'Oeufs',
            ],
            ShoppingItemCategory::EpicerieSucree->value => [
                'Chocolat dessert',
                'Gourmandises',
                'Brioche',
                'Biscuit sachet',
                'Céréales',
                'Farine',
                'Sucre roux',
                'Sucre blanc',
            ],
            ShoppingItemCategory::Boissons->value => [
                'Lait',
                'Jus tomate',
                'Jus matin',
                'Bières',
                'Soda',
                'Vin blanc',
                'Vin rouge',
                'Café',
                'Thé',
                'Tisane',
            ],
            ShoppingItemCategory::Hygiene->value => [
                'Shampooing',
                'Gel douche',
                'Dentifrice',
                'Déo',
                'Crème hydratante',
                'Tampons',
                'Serviettes H',
            ],
            ShoppingItemCategory::Maison->value => [
                'Vinaigre blanc',
                'Bicar\'',
                'Lessive',
                'Adoucissant',
                'Produit vaisselle',
                'Éponge',
                'Lingettes lessive',
                'Papier toilette',
                'Sopalin',
            ],
            ShoppingItemCategory::Autre->value => [
                'Piles',
                'Scotch',
                'Papier cadeau',
                'Recharge sodastream',
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
