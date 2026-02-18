# Contexte Cocoon

> **IMPORTANT : Ce fichier doit être mis à jour à chaque fois qu'une phase ou fonctionnalité majeure est terminée.**

## Qu'est-ce que Cocoon ?

App mobile de couple (Kevin + Lola) pour centraliser l'organisation quotidienne. Remplace Tricount et regroupe budget, courses, tâches, repas et notes. Interface 100% français, Android uniquement, distribuée par APK direct (pas de Play Store).

## Stack technique

- **Backend** : Laravel 12, PHP 8.4, SQLite (local, offline-first)
- **Frontend** : Vue 3 + Inertia v2 + Tailwind CSS v4
- **Mobile** : NativePHP Mobile v3 (runtime PHP natif sur le device)
- **Auth** : Laravel Fortify (login uniquement, pas de 2FA, pas de registration publique, pas de reset password)
- **Tests** : Pest 4
- **Routes TS** : Wayfinder
- **Sync** : Laravel Cloud API (Serverless Postgres) + Sanctum tokens
- **Biométrie** : NativePHP Mobile Biometrics + Secure Storage (Face ID / empreinte)

## Contraintes clés

- **2 utilisateurs seulement** : Kevin et Lola, whitelist d'emails dans `config/cocon.php`
- **Registration désactivée** : un écran de setup (`/setup`) permet de créer un compte au premier lancement si l'email est dans la whitelist
- **Pas de reset password** : app locale sans serveur mail
- **Offline-first** : SQLite local, sync cloud via API (Syncable trait + SyncLog + SyncService)
- **Dashboard sur `/`** : pas de redirect, Fortify home = `'/'`
- **Safe areas NativePHP** : `viewport-fit=cover` + classe `nativephp-safe-area` sur `<body>`, CSS variables `--inset-top/--inset-bottom` avec fallback `env(safe-area-inset-*)`

## Architecture

### Modèles (app/Models/)

| Modèle | Rôle |
|--------|------|
| User | Utilisateur (2 max) |
| Expense | Dépense avec split type |
| ExpenseCategory | Catégorie de dépense (seeder) |
| ShoppingList | Liste de courses |
| ShoppingItem | Article dans une liste (sans quantité ni favori depuis Phase 16) |
| TodoList | Liste de tâches (partagée ou personnelle) |
| Todo | Tâche dans une TodoList (title, is_done, completed_at) |
| Recipe | Recette complète (titre, description, url, image_path, temps, portions, tags) |
| RecipeIngredient | Ingrédient d'une recette (nom, quantité, unité, ordre) |
| RecipeStep | Étape d'une recette (instruction, ordre) |
| Note | Note partagée (titre, contenu, couleur, épinglage) |
| SweetMessage | Mot doux entre partenaires (1 par utilisateur) |
| Joke | Blague du jour (seeder 50 blagues) |
| Birthday | Anniversaire (nom, date, âge calculé) |
| SyncLog | Journal de sync (queue locale, pending/synced) |

### Enums (app/Enums/)

- `SplitType` : Equal, FullPayer, FullOther, Custom
- `ShoppingItemCategory` : catégories d'articles
- `MealTag` : Rapide, Vege, Comfort, Leger, Gourmand (tags repas)
- `NoteColor` : Default, Yellow, Green, Blue, Pink, Purple (couleurs notes)
- `SyncAction` : actions de sync

### Traits (app/Traits/)

- `Syncable` : trait appliqué sur les modèles synchronisables (écoute created/updated/deleted → SyncLog)

### Services (app/Services/)

- `BalanceCalculator` : calcul de balance budget entre les 2 utilisateurs
- `SyncService` : logique sync push/pull/full (last-write-wins, gestion recettes imbriquées)

### Middleware custom

- `RestrictToHousehold` : vérifie que l'email est dans la whitelist

### Controllers (app/Http/Controllers/)

- `DashboardController` : page d'accueil `/` avec widgets (mot doux, anniversaires du jour, blague)
- `SweetMessageController` : store (updateOrCreate) — mot doux
- `BirthdayController` : CRUD complet (index, store, update, destroy) — modal sur index
- `ExpenseController` : CRUD dépenses + settle + history (mensuel/annuel/total)
- `ShoppingListController` : CRUD + duplicate
- `ShoppingItemController` : store, update, toggleCheck, destroy (sans toggleFavorite)
- `TodoListController` : show, store, update, destroy — listes de tâches
- `TodoController` : store (dans une liste), toggle, update, destroy
- `RecipeController` : index, create, store, show, edit, update, destroy — avec image upload
- `NoteController` : index, show, store, update, togglePin, destroy — show = page dédiée
- `MoreController` : page "Plus" (Courses, Repas, Paramètres)
- `Settings/ProfileController` (nom uniquement, email non modifiable), `Settings/PasswordController`
- `Auth/SetupController` : premier lancement
- `Auth/BiometricController` : écran biométrie (show + verify token Sanctum)
- `Auth/ApiLoginController` : login API (Sanctum token)
- `Api/SyncController` : push/pull/full sync API endpoints
- `Api/AppVersionController` : check (version + signed URL) + download (stream APK)

### Routes principales (routes/web.php)

- `GET /` : Dashboard (auth, verified)
- `GET|POST /setup` : Setup premier lancement (guest)
- `GET|POST /biometric-login` : Écran biométrie + vérification token (guest)
- `/expenses` : resource (sauf show) + `POST settle` + `GET history`
- `/shopping-lists` : resource (sauf edit) + `POST {id}/duplicate` + items (store, update, toggleCheck, destroy)
- `/recipes` : resource complète (index, create, store, show, edit, update, destroy)
- `/notes` : index, show, store, update, togglePin, destroy
- `/todo-lists` : show, store, update, destroy
- `/todo-lists/{todo_list}/todos` : store
- `/todos/{todo}/toggle` : PATCH toggle
- `/todos/{todo}` : PATCH update, DELETE destroy
- `POST /sweet-messages` : store mot doux
- `/birthdays` : resource (index, store, update, destroy)
- `/more` : page "Plus" (Courses, Repas, Paramètres)
- Settings dans `routes/settings.php` : profil (nom uniquement), mot de passe
- API dans `routes/api.php` : sync (push/pull/full), `GET app/version` (auth:sanctum), `GET app/download` (signed URL)

### Navigation (BottomNav)

Accueil | Budget | Calendrier (Phase 20) | Notes | Plus

**"Plus" contient :** Courses | Repas | Paramètres

## Phases terminées

### Phase 1-4 : Fondations
- Setup projet Laravel 12 + NativePHP + Tailwind v4
- Modèles, migrations, factories, seeders pour tous les modules
- Auth Fortify : login, setup premier lancement, middleware household (pas de 2FA)
- Layout mobile avec AppLayout (h-dvh, header fixe, main scrollable) + BottomNav (5 onglets)
- Pages settings : profil (nom uniquement, email non modifiable), mot de passe
- Thème clair uniquement (pas de dark mode, pas de page apparence)
- Pas de suppression de compte

### Phase 5 : Module Budget (complet)
- CRUD dépenses avec catégories et split types
- `BalanceCalculator` : calcul de qui doit quoi
- Settle (règlement) : archive les dépenses non réglées
- Historique des règlements

### Phase 6 : Module Courses (complet)
- CRUD listes de courses avec templates et duplication
- Articles par liste : ajout, check/uncheck, suppression
- Groupement par catégorie (enum ShoppingItemCategory avec labels FR)

### Phase 7 : Module Tâches (complet, refactorisé en Phase 19)
- Voir Phase 19

### Phase 8 : Module Repas (complet, refactorisé en Phase 18)
- Recettes complètes avec image, index grid, tags, ingrédients, étapes

### Phase 9-10 : Notes + Bookmarks (Notes conservées, Bookmarks supprimés en Phase 15)

### Phase 11 : Dashboard + Anniversaires (complet)
- Widgets : mot doux, anniversaires du jour, blague du jour
- Anniversaires : Birthday CRUD complet via modal

### Phase 12 : Sync Offline-First (complet)
- Syncable trait + SyncLog + SyncService (push/pull/full)
- SyncClient JS dans AppLayout

### Phase 13 : Biométrie (complet)
- NativePHP Mobile Biometrics + Secure Storage
- BiometricController, biometric-auth.ts, LoginResponse/LogoutResponse custom

### Phase 14 : Auto-update APK (complet)
- AppVersionController (check + download via signed URL)
- Commande `app:publish-release`, update-checker.ts, UpdateDialog.vue

### Phase 15 : Cleanup (complet)
- Suppression complète de Bookmarks (modèle, controller, routes, tests, UI, sync)
- Suppression complète de MealIdeas (modèle, controller, routes, tests, UI, sync)
- FAB surélevé au-dessus de la BottomNav
- Logo sur l'écran de connexion

### Phase 16 : Shopping Refonte (complet)
- ShoppingItems : cards avec menu ⋮ (modifier/supprimer), dialog d'édition inline
- Catégories collapsibles
- Suppression de la quantité et des favoris
- localStorage mémorise la dernière liste consultée → redirect auto

### Phase 17 : Budget V2 (complet)
- Catégories renommées : Loyer → Charges, Santé → Cadeaux
- Historique : vue mensuelle par défaut + filtres (mensuel/annuel/total) + navigation par mois
- Lien "Voir l'historique" dans le BalanceBanner

### Phase 18 : Recipes V2 (complet)
- Page index grille 2 colonnes avec image/placeholder
- Upload d'image via `Storage::disk('public')` (input file, prévisualisation)
- Fix back buttons : `/meal-plans` → `/recipes`
- `destroy()` redirige vers `recipes.index` (au lieu de `more`)
- 199 tests passants

### Phase 19 : Notes Fusion (complet)
- Nouvelle structure TodoList + Todo (plus d'anciens todos)
- NoteController : ajout de `show()` (page dédiée plein écran avec auto-save)
- Notes/Index.vue : 2 onglets (Notes | Todos) via `?tab=`
- Notes/Show.vue : édition plein écran avec fond coloré, auto-save debounce
- TodoLists/Show.vue : style Shopping (formulaire sticky, toggle, section Terminés)
- BottomNav : "Tâches" → "Notes" (`/notes`)
- More.vue : Courses | Repas | Paramètres (sans Notes ni Anniversaires)
- SyncService : MODEL_MAP mis à jour (todo_lists + todos nouvelle structure)
- Dashboard : suppression widget "todos épinglés" (show_on_dashboard retiré)

## Phases à venir

| Phase | Module | Statut |
|-------|--------|--------|
| 20 | Calendrier (vue mensuelle, événements, anniversaires intégrés) | Non commencé |
| 21 | Dashboard V2 (widget événements du jour) | Après Phase 20 |
| 22 | Bugs (blagues + mot mignon qui ne s'affichent pas) | Non commencé |
| OPT-1 | Saisie vocale (Web Speech API dans AddItemForm) | Optionnel |

## Conventions de code

- **Tests** : `tests/Unit` sans Laravel, `tests/Feature` avec TestCase + RefreshDatabase
- **Routes custom AVANT les resource routes** pour éviter les conflits `{param}`
- **Plans** : fichiers `.md` à la racine, référencés dans `COCON_PLAN.md`
- **Pint** : `vendor/bin/pint --dirty --format agent` avant chaque finalisation
- **Wayfinder** : `php artisan wayfinder:generate` après modification de routes/controllers
- **Images** : `Storage::disk('public')`, symlink via `php artisan storage:link`, URL `/storage/{path}`
- **mobilePut / mobilePatch** : workaround Android WebView pour PUT/PATCH (POST + _method spoofing)

## Fichiers de référence

- `COCON_PLAN.md` : plan global du projet
- `REUNION_PLAN.md` : plan issu de la réunion Kevin + Lola (18/02/2026)
- `PHASE19_NOTES_FUSION.md` : plan détaillé phase 19
- `PHASE20_CALENDRIER.md` : plan détaillé phase 20
- `config/cocon.php` : whitelist emails autorisés
- `config/fortify.php` : features auth (pas de registration, pas de reset password)
