# Phase 6 : Module Courses (Shopping Lists)

## Contexte

Phase 6 du plan Cocoon. Le module Courses permet de gérer des listes de courses avec articles, catégories par rayon, favoris et modèles. La base de données est prête (models, migrations, factories, enum). Seuls un controller stub (index) et une page Vue vide existent. On suit les patterns établis par le module Budget.

## Fichiers impactés

| Action | Fichier |
|--------|---------|
| Modifier | `app/Enums/ShoppingItemCategory.php` — ajouter `label()` |
| Modifier | `app/Http/Controllers/ShoppingListController.php` — CRUD complet + duplicate |
| Créer | `app/Http/Controllers/ShoppingItemController.php` — store, toggleCheck, toggleFavorite, destroy |
| Créer | `app/Http/Requests/ShoppingList/StoreShoppingListRequest.php` |
| Créer | `app/Http/Requests/ShoppingList/UpdateShoppingListRequest.php` |
| Créer | `app/Http/Requests/ShoppingItem/StoreShoppingItemRequest.php` |
| Modifier | `routes/web.php` — routes resource + items + duplicate |
| Créer | `resources/js/types/shopping.ts` |
| Modifier | `resources/js/types/index.ts` — export shopping |
| Modifier | `resources/js/pages/Shopping/Index.vue` — liste des listes |
| Créer | `resources/js/pages/Shopping/Create.vue` — créer une liste |
| Créer | `resources/js/pages/Shopping/Show.vue` — vue d'une liste avec articles |
| Créer | `resources/js/components/shopping/ShoppingItemRow.vue` |
| Créer | `resources/js/components/shopping/CategoryGroup.vue` |
| Créer | `resources/js/components/shopping/AddItemForm.vue` |
| Créer | `tests/Feature/ShoppingList/ShoppingListTest.php` |
| Créer | `tests/Feature/ShoppingItem/ShoppingItemTest.php` |
| Modifier | `.claude/guidelines/contexte.md` — màj phase 6 |

## Plan d'implémentation

### 1. Enum — ajouter `label()` à `ShoppingItemCategory`

`app/Enums/ShoppingItemCategory.php` — méthode `label(): string` avec les noms FR :
- FruitsLegumes → "Fruits & Légumes"
- Frais → "Frais", Epicerie → "Épicerie", Boissons → "Boissons"
- Hygiene → "Hygiène", Maison → "Maison", Autre → "Autre"

### 2. Form Requests

**`StoreShoppingListRequest`** :
- `name` : required, string, max:255
- `is_template` : boolean
- Messages FR

**`UpdateShoppingListRequest`** : mêmes règles

**`StoreShoppingItemRequest`** :
- `name` : required, string, max:255
- `category` : required, Rule::enum(ShoppingItemCategory)
- `quantity` : nullable, string, max:50
- Messages FR

### 3. Routes (`routes/web.php`)

Remplacer la ligne `only(['index'])` par :

```php
// Custom routes AVANT resource (convention projet)
Route::post('shopping-lists/{shopping_list}/duplicate', [ShoppingListController::class, 'duplicate'])
    ->name('shopping-lists.duplicate');
Route::resource('shopping-lists', ShoppingListController::class)->except(['edit']);

// Items (flat routes sauf store qui est nested)
Route::post('shopping-lists/{shopping_list}/items', [ShoppingItemController::class, 'store'])
    ->name('shopping-items.store');
Route::patch('shopping-items/{shopping_item}/toggle-check', [ShoppingItemController::class, 'toggleCheck'])
    ->name('shopping-items.toggle-check');
Route::patch('shopping-items/{shopping_item}/toggle-favorite', [ShoppingItemController::class, 'toggleFavorite'])
    ->name('shopping-items.toggle-favorite');
Route::delete('shopping-items/{shopping_item}', [ShoppingItemController::class, 'destroy'])
    ->name('shopping-items.destroy');
```

Pas de page `edit` pour les listes — le rename se fait via update depuis la page Show.

### 4. ShoppingListController (complet)

Étendre `app/Http/Controllers/ShoppingListController.php` :

- **index** : `ShoppingList::withCount(['items', 'uncheckedItems', 'checkedItems'])` triés par `is_active` desc puis `latest()`. Rend `Shopping/Index` avec `shoppingLists`.
- **create** : rend `Shopping/Create`
- **store** : crée la liste avec `Str::uuid()`, `is_active = !is_template`. Redirige vers `show`.
- **show** : charge `items.addedBy`. Sépare unchecked (groupés par catégorie, triés selon l'ordre de l'enum) et checked. Passe aussi les `categories` (valeurs de l'enum avec labels FR). Rend `Shopping/Show`.
- **update** : met à jour le nom. Redirige vers `show`.
- **destroy** : supprime (cascade items via FK). Redirige vers `index`.
- **duplicate** : copie la liste et tous ses items (unchecked, nouveaux uuids). Redirige vers la nouvelle liste.

### 5. ShoppingItemController (nouveau)

`app/Http/Controllers/ShoppingItemController.php` :

- **store(Request, ShoppingList)** : crée l'item avec `added_by = auth user`, `Str::uuid()`. Redirige vers `show` avec `preserveScroll`.
- **toggleCheck(ShoppingItem)** : inverse `is_checked`. Redirige vers `show`.
- **toggleFavorite(ShoppingItem)** : inverse `is_favorite`. Redirige vers `show`.
- **destroy(ShoppingItem)** : supprime. Redirige vers `show`.

### 6. Types TypeScript

`resources/js/types/shopping.ts` :

```ts
export type ShoppingItemCategory = 'fruits_legumes' | 'frais' | 'epicerie' | 'boissons' | 'hygiene' | 'maison' | 'autre';

export type ShoppingItem = {
    id: number;
    shopping_list_id: number;
    name: string;
    category: ShoppingItemCategory;
    quantity: string | null;
    is_checked: boolean;
    is_favorite: boolean;
    added_by: number;
    uuid: string;
    created_at: string;
    updated_at: string;
};

export type ShoppingList = {
    id: number;
    name: string;
    is_template: boolean;
    is_active: boolean;
    uuid: string;
    created_at: string;
    updated_at: string;
    items?: ShoppingItem[];
    unchecked_items_count?: number;
    checked_items_count?: number;
    items_count?: number;
};
```

Ajouter `export * from './shopping'` dans `resources/js/types/index.ts`.

### 7. Pages Vue

#### 7a. `Shopping/Index.vue` (modifier)

- `AppLayout` title="Courses", `#header-right` : bouton `+` vers create
- Si aucune liste : `EmptyState` avec icône ShoppingCart et bouton "Créer une liste"
- Sinon : cartes `Link` vers show, affichant nom + "X/Y articles cochés"
- Séparer visuellement les listes actives (en haut) des modèles (badge "Modèle")
- Sur les modèles : bouton "Dupliquer" (POST via router)

#### 7b. `Shopping/Create.vue` (créer)

- `AppLayout` title="Nouvelle liste"
- `useForm({ name: '', is_template: false })`
- Input nom + Switch "Liste modèle" + bouton "Créer la liste"
- POST vers store

#### 7c. `Shopping/Show.vue` (créer) — page principale

Structure :
```
AppLayout title={list.name}
  #header-right: menu (supprimer la liste / dupliquer si modèle)

  AddItemForm (en haut, sticky)

  Pour chaque catégorie avec des articles non cochés :
    CategoryGroup label={catégorie}
      ShoppingItemRow v-for item

  Section pliable "Articles cochés (N)" si checkedItems > 0
    ShoppingItemRow v-for item (style barré/grisé)
```

Props : `shoppingList`, `uncheckedItemsByCategory` (Record<string, ShoppingItem[]>), `checkedItems` (ShoppingItem[]), `categories`

Toutes les actions (check, favorite, delete) utilisent `router.patch/delete` avec `preserveScroll: true`.

#### 7d. Composants

**`ShoppingItemRow.vue`** — ligne d'article :
- Checkbox à gauche (toggleCheck)
- Nom + quantité au centre
- Étoile (toggleFavorite) à droite
- Bouton supprimer (icône Trash2)
- Si `is_checked` : texte barré + couleurs atténuées

**`CategoryGroup.vue`** — en-tête de catégorie + slot pour items :
- Titre en `text-xs uppercase text-muted-foreground`
- Slot pour les items

**`AddItemForm.vue`** — formulaire inline :
- `useForm({ name: '', category: 'autre', quantity: '' })`
- Input nom (autofocus) + select catégorie + input quantité optionnel
- Submit sur Enter ou bouton "+"
- Reset après succès

### 8. Tests Pest

**`tests/Feature/ShoppingList/ShoppingListTest.php`** (~15 tests) :
- Guests redirigés vers login
- Index affiche les listes avec counts
- Create form accessible
- Store crée une liste avec uuid
- Store valide les champs requis
- Store avec is_template crée une liste inactive
- Show affiche la liste avec items groupés
- Update modifie le nom
- Destroy supprime la liste (cascade items)
- Duplicate copie la liste et ses items
- Duplicate crée des nouveaux uuids
- Duplicate met les items en unchecked

**`tests/Feature/ShoppingItem/ShoppingItemTest.php`** (~10 tests) :
- Store ajoute un item à la liste
- Store valide name et category
- Store valide l'enum category
- Store assigne added_by à l'utilisateur courant
- toggleCheck inverse is_checked
- toggleFavorite inverse is_favorite
- Destroy supprime l'item

### 9. Finalisation

1. `vendor/bin/pint --dirty --format agent`
2. `php artisan wayfinder:generate`
3. `npm run build`
4. `php artisan test --compact`
5. Mettre à jour `.claude/guidelines/contexte.md`

## Vérification

1. `php artisan test --compact` → tous les tests passent (existants + nouveaux)
2. `npm run build` → compile sans erreur
3. `/shopping-lists` affiche la liste des listes (ou empty state)
4. Créer une liste → redirige vers la page Show
5. Ajouter des articles → apparaissent groupés par catégorie
6. Cocher un article → passe dans la section "cochés" en bas
7. Marquer favori → étoile remplie
8. Supprimer un article → disparaît
9. Dupliquer un modèle → nouvelle liste active avec tous les items décochés
10. Supprimer une liste → retour à l'index
