# Phase 15 — Nettoyage & Ajustements visuels

## Objectif
Supprimer les modules inutilisés (Bookmarks, MealIdeas), ajuster le FAB et ajouter le logo login.

---

## 15.1 Suppression Bookmarks (complète)

### Backend à supprimer
- `app/Models/Bookmark.php`
- `app/Enums/BookmarkCategory.php`
- `app/Http/Controllers/BookmarkController.php`
- `app/Http/Requests/Bookmark/StoreBookmarkRequest.php`
- `app/Http/Requests/Bookmark/UpdateBookmarkRequest.php`
- Routes `bookmarks` et `bookmarks.toggle-favorite` dans `routes/web.php`

### Migration
- `drop_bookmarks_table` : `Schema::dropIfExists('bookmarks')`
- Inclure aussi la suppression de la colonne `show_on_dashboard` de `bookmarks` (déjà dans la migration existante, la table est droppée donc OK)

### Sync
- Retirer `Bookmark::class` du `MODEL_MAP` dans `app/Services/SyncService.php`

### Frontend à supprimer
- `resources/js/pages/Bookmarks/Index.vue`
- Retirer l'entrée "Bookmarks" de `resources/js/pages/More.vue`

### Tests à supprimer
- `tests/Feature/Bookmark/BookmarkTest.php`

### Tests à mettre à jour
- `tests/Feature/Sync/SyncableTest.php` : retirer les assertions sur Bookmark
- `tests/Feature/Sync/SyncApiTest.php` : retirer les références à Bookmark
- `tests/Feature/Dashboard/DashboardTest.php` : retirer assertions sur bookmarks épinglés

---

## 15.2 Suppression MealIdeas (complète)

### Backend à supprimer
- `app/Models/MealIdea.php`
- `app/Http/Controllers/MealIdeaController.php`
- `app/Http/Controllers/MealPlanController.php`
- `app/Http/Requests/MealIdea/StoreMealIdeaRequest.php`
- `app/Http/Requests/MealIdea/UpdateMealIdeaRequest.php`
- Routes `meal-plans` et `meal-ideas` dans `routes/web.php`
- Enum `MealTag.php` : **garder** (utilisé par Recipe)

### Migrations
- `drop_meal_ideas_table` : `Schema::dropIfExists('meal_ideas')`

### Sync
- Retirer `MealIdea::class` du `MODEL_MAP` dans `SyncService.php`

### Frontend à supprimer/modifier
- `resources/js/pages/Meals/Index.vue` : supprimer (remplacé par `Recipes/Index.vue` en Phase 18)
- Dans `resources/js/pages/More.vue` : changer le lien "Repas" de `/meal-plans` → `/recipes`
  (le controller restera `MoreController`, juste la href change)

### Tests à supprimer
- `tests/Feature/MealIdea/MealIdeaTest.php`

### Tests à mettre à jour
- `tests/Feature/Sync/SyncableTest.php` : retirer les assertions sur MealIdea
- `tests/Feature/Sync/SyncApiTest.php` : retirer les références à MealIdea

---

## 15.3 Ajustement FAB

### `resources/js/components/FloatingActionButton.vue`
- Modifier le `bottom` CSS : `72px` → `84px` pour le surélever légèrement au-dessus de la BottomNav

---

## 15.4 Logo écran de connexion

### `resources/js/pages/auth/Login.vue`
- Ajouter au-dessus du formulaire : nom "Cocoon" stylisé (texte + icône si pas de logo SVG fourni)
- Utiliser la couleur `text-primary` et une grande taille de police (`text-4xl font-bold`)
- Si un fichier `resources/js/assets/logo.svg` est fourni : l'utiliser à la place

---

## Tests Phase 15

Après suppression, le total de tests devrait passer de 213 à environ **181** (suppression de ~12 tests Bookmark + ~8 tests MealIdea + ajustements sync).

Commande de validation :
```bash
php artisan test --compact
```
