# Phase 18 — Recettes V2

## Objectif
Créer un index Recettes, ajouter le support image (NativePHP Camera), choix URL/saisie manuelle au moment de la création, lien URL cliquable dans le détail.

> Note : la suppression de MealIdeas est faite en Phase 15.

---

## 18.1 Base de données

### Migration : modifier `recipes`
- Ajouter colonne `image_path` (nullable string) : chemin local du fichier image sur le device
- La colonne `url` existe déjà — conserver

---

## 18.2 Backend

### `app/Models/Recipe.php`
- Ajouter `image_path` dans `$fillable`

### `app/Http/Controllers/RecipeController.php`
- Ajouter méthode `index()` : retourne toutes les recettes (titre, tags, image_path, url, durée)
- `store()` : si `image_path` fourni, déplacer le fichier avec `File::move()` (plugin NativePHP) vers stockage permanent ; sinon null
- `update()` : même logique pour le remplacement d'image
- `destroy()` : si `image_path` présent, supprimer le fichier local

### `app/Http/Requests/Recipe/StoreRecipeRequest.php`
- Ajouter `image_path` : `nullable|string`
- Ajouter `recipe_type` : `required|in:url,manual` (choix du mode de création)

### `app/Http/Requests/Recipe/UpdateRecipeRequest.php`
- Idem

### `routes/web.php`
- Remplacer `Route::resource('recipes', RecipeController::class)->except(['index'])` par une resource complète (sans `except`)

---

## 18.3 Frontend

### `resources/js/pages/Recipes/Index.vue` (nouvelle page)
- Liste de toutes les recettes en cards (2 colonnes ou 1 selon la largeur)
- Card : image (ou placeholder), titre, tags (pills), durée totale
- Click sur une card → `/recipes/{id}`
- FAB → `/recipes/create`

### `resources/js/pages/Recipes/Create.vue` (refonte)

**Étape 1 : Choix du mode**
- 2 grandes cards sélectionnables :
  - 🔗 **"Lien vers une recette"** : saisir l'URL, optionnel : ajouter une photo via Camera
  - 📝 **"Saisie manuelle"** : formulaire complet (comportement actuel)

**Mode "Lien" :**
- Champ URL (required)
- Titre (required)
- Tags (optionnel)
- Bouton "Ajouter une photo" → `camera.getPhoto()` via `#nativephp` → stocke le path dans `image_path`
- Photo choisie : preview de l'image + bouton "Supprimer"

**Mode "Saisie manuelle" :**
- Formulaire actuel complet (titre, description, temps, portions, tags, ingrédients, étapes)
- Bouton "Ajouter une photo" en haut (même logique Camera)

**Bouton Enregistrer :** plein largeur en bas, `w-full`

### `resources/js/pages/Recipes/Edit.vue` (modifié)
- Afficher l'image existante si présente (preview + "Remplacer" | "Supprimer")
- Même logique Camera pour le remplacement

### `resources/js/pages/Recipes/Show.vue` (modifié)
- Afficher l'image en plein largeur en haut si `image_path` présent
- Rendre l'URL cliquable : utiliser `browser.open()` de `#nativephp` (avec fallback `window.open()` sur web)
- Le lien affiché : tronqué à 40 caractères avec `...`

---

## 18.4 Sync

### `app/Services/SyncService.php`
- Ajouter `image_path` dans le payload Recipe lors du push
- **Note** : l'image elle-même (le fichier) n'est **pas** synchronisée entre devices dans cette phase — `image_path` pointe vers un fichier local, donc après sync il sera null ou invalide sur l'autre device. À documenter clairement, à améliorer dans une phase ultérieure si besoin.

---

## 18.5 Tests

### `tests/Feature/Recipe/RecipeTest.php`
- Ajouter test : `index` retourne les recettes
- Ajouter test : `store` avec `image_path` (chemin fictif) l'enregistre
- Ajouter test : `destroy` avec `image_path` (le test vérifie juste que la recette est supprimée)
- Conserver tous les tests existants

---

## 18.6 Bonus : Seeder depuis images (optionnel, phase ultérieure)
- Commande artisan `app:seed-recipes-from-images {directory}`
- Analyse les images via l'API Claude (Anthropic) → extrait titre, ingrédients, étapes
- Génère des inserts en base
- **À planifier séparément** si souhaité