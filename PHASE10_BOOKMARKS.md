# Phase 10 — Module Bookmarks

## Contexte

Transformer le module Bookmarks (initialement prevu pour Leboncoin) en bookmarks generiques. Le modele, factory, controller (stub) et page Vue (empty state) existent deja mais sont orientes Leboncoin (champs `price`, `image_url`). On les adapte pour un usage generique avec categories et favoris.

- **Bookmarks generiques** : URL + titre + description
- **CRUD via modal** : meme pattern que Notes/Todos
- **Categories via enum** : Resto, Voyage, Shopping, Loisirs, Maison, Autre
- **Favoris** : `is_favorite` boolean, affiches en premier
- **Filtrage par categorie** cote client (boutons en haut de la page)

## Etape 1 : Migration — adapter la table `bookmarks`

```
php artisan make:migration update_bookmarks_table --table=bookmarks
```

- Supprimer `price`, `image_url`
- Renommer `notes` -> `description` (text, nullable)
- Ajouter `category` : string, nullable, after `description`
- Ajouter `is_favorite` : boolean, default false, after `category`

## Etape 2 : Enum BookmarkCategory

**Creer `app/Enums/BookmarkCategory.php`**

| Case | Valeur | Label FR |
|------|--------|----------|
| Resto | `resto` | Resto |
| Voyage | `voyage` | Voyage |
| Shopping | `shopping` | Shopping |
| Loisirs | `loisirs` | Loisirs |
| Maison | `maison` | Maison |
| Autre | `autre` | Autre |

- Methode `label(): string` -> labels FR

## Etape 3 : Modele + Factory

**Modifier `app/Models/Bookmark.php`**
- Remplacer fillable : `url`, `title`, `description`, `category`, `is_favorite`, `added_by`, `uuid`
- Casts : supprimer `price`, ajouter `category` -> `BookmarkCategory::class`, `is_favorite` -> `boolean`

**Modifier `database/factories/BookmarkFactory.php`**
- Supprimer `price`, `image_url`
- Renommer `notes` -> `description`
- Ajouter `category` : `fake()->optional(0.5)->randomElement(BookmarkCategory::cases())`
- Ajouter `is_favorite` : `false`
- State `favorite()` : `is_favorite` -> true

## Etape 4 : Form Requests

**Creer `app/Http/Requests/Bookmark/StoreBookmarkRequest.php`**
- `url` : required, url, max:2048
- `title` : required, string, max:255
- `description` : nullable, string, max:1000
- `category` : nullable, string, Rule::in(BookmarkCategory values)
- `is_favorite` : required, boolean
- Messages FR

**Creer `app/Http/Requests/Bookmark/UpdateBookmarkRequest.php`**
- Memes regles

## Etape 5 : Controller

**Modifier `app/Http/Controllers/BookmarkController.php`**

- `index()` : passe `bookmarks` (favoris en premier, puis latest), `categories` (BookmarkCategory labels pour le filtre), avec `addedBy`
- `store(StoreBookmarkRequest)` : cree avec `uuid` + `added_by`, redirect bookmarks.index
- `update(UpdateBookmarkRequest, Bookmark)` : met a jour, redirect bookmarks.index
- `toggleFavorite(Bookmark)` : inverse `is_favorite`, redirect bookmarks.index
- `destroy(Bookmark)` : supprime, redirect bookmarks.index

## Etape 6 : Routes

**Modifier `routes/web.php`**

```php
Route::patch('bookmarks/{bookmark}/toggle-favorite', [BookmarkController::class, 'toggleFavorite'])->name('bookmarks.toggle-favorite');
Route::resource('bookmarks', BookmarkController::class)->only(['index', 'store', 'update', 'destroy']);
```

## Etape 7 : Types TypeScript

**Creer `resources/js/types/bookmark.ts`**

```ts
export type BookmarkCategory = 'resto' | 'voyage' | 'shopping' | 'loisirs' | 'maison' | 'autre';

export type BookmarkCategoryOption = {
    value: BookmarkCategory;
    label: string;
};

export type Bookmark = {
    id: number;
    url: string;
    title: string;
    description: string | null;
    category: BookmarkCategory | null;
    is_favorite: boolean;
    added_by: number;
    uuid: string;
    created_at: string;
    updated_at: string;
    added_by_user?: { id: number; name: string };
};
```

**Modifier `resources/js/types/index.ts`** — ajouter `export * from './bookmark'`

## Etape 8 : Composants Vue (2 fichiers)

**`resources/js/components/bookmarks/BookmarkCard.vue`**
- Props : `bookmark: Bookmark`
- Evenements : `@edit`
- Affiche : titre (font-semibold), URL tronquee (text-xs muted lien externe), description (line-clamp-2), badge categorie si presente
- Icone etoile pour toggle favori (mobilePatch), bouton supprimer (router.delete)
- Click sur la carte -> emit `edit`

**`resources/js/components/bookmarks/BookmarkFormDialog.vue`**
- Pattern identique a NoteFormDialog
- Champs : url (Input type url), title (Input), description (Textarea 2 lignes), category (boutons toggle comme MealTag), is_favorite (Switch)
- Mode create / edit

## Etape 9 : Page Vue

**Reecrire `resources/js/pages/Bookmarks/Index.vue`**
- Props : `bookmarks: Bookmark[]`, `categories: BookmarkCategoryOption[]`
- Header : titre "Bookmarks" + bouton "+"
- Filtrage par categorie (boutons scrollables horizontalement, "Tous" par defaut) — computed cote client
- Si aucun bookmark (apres filtre) : EmptyState avec icone Bookmark
- Sinon : liste verticale de BookmarkCard (gap-3 p-4)
- BookmarkFormDialog pour create/edit

## Etape 10 : Tests Pest (~12 tests)

**Creer `tests/Feature/Bookmark/BookmarkTest.php`**

1. Guests rediriges vers login
2. Index retourne les bookmarks et categories
3. Favoris apparaissent en premier
4. Store cree un bookmark avec uuid et added_by
5. Store valide url requise
6. Store valide title requis
7. Store valide url format
8. Store valide category parmi les valeurs de l'enum
9. Store cree un bookmark avec categorie
10. Update modifie un bookmark
11. Toggle favorite inverse is_favorite
12. Destroy supprime un bookmark

## Etape 11 : Finalisation

- `vendor/bin/pint --dirty --format agent`
- `php artisan wayfinder:generate`
- `npm run build`
- `php artisan test --compact`
- Mettre a jour `.ai/guidelines/contexte.md`, `MEMORY.md`

## Fichiers crees/modifies

| Action | Fichier |
|--------|---------|
| Creer | 1 migration (update_bookmarks_table) |
| Creer | `app/Enums/BookmarkCategory.php` |
| Modifier | `app/Models/Bookmark.php` |
| Modifier | `database/factories/BookmarkFactory.php` |
| Creer | `app/Http/Requests/Bookmark/StoreBookmarkRequest.php` |
| Creer | `app/Http/Requests/Bookmark/UpdateBookmarkRequest.php` |
| Modifier | `app/Http/Controllers/BookmarkController.php` |
| Modifier | `routes/web.php` |
| Creer | `resources/js/types/bookmark.ts` |
| Modifier | `resources/js/types/index.ts` |
| Creer | `resources/js/components/bookmarks/BookmarkCard.vue` |
| Creer | `resources/js/components/bookmarks/BookmarkFormDialog.vue` |
| Modifier | `resources/js/pages/Bookmarks/Index.vue` |
| Creer | `tests/Feature/Bookmark/BookmarkTest.php` |
| Modifier | `.ai/guidelines/contexte.md`, `MEMORY.md` |

## Verification

1. `php artisan migrate` -> sans erreur
2. `php artisan test --compact --filter=Bookmark` -> tous les tests passent
3. `php artisan test --compact` -> aucune regression (143+ tests)
4. `npm run build` -> sans erreur
