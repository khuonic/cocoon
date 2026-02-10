# Contexte Cocoon

> **IMPORTANT : Ce fichier doit être mis à jour à chaque fois qu'une phase ou fonctionnalité majeure est terminée.**

## Qu'est-ce que Cocoon ?

App mobile de couple (Kevin + Lola) pour centraliser l'organisation quotidienne. Remplace Tricount et regroupe budget, courses, tâches, repas, notes et bookmarks. Interface 100% français, Android uniquement, distribuée par APK direct (pas de Play Store).

## Stack technique

- **Backend** : Laravel 12, PHP 8.4, SQLite (local, offline-first)
- **Frontend** : Vue 3 + Inertia v2 + Tailwind CSS v4
- **Mobile** : NativePHP Mobile v3 (runtime PHP natif sur le device)
- **Auth** : Laravel Fortify (login, 2FA, pas de registration publique, pas de reset password)
- **Tests** : Pest 4
- **Routes TS** : Wayfinder
- **Sync future** : Laravel Cloud API (Serverless Postgres) + Sanctum tokens

## Contraintes clés

- **2 utilisateurs seulement** : Kevin et Lola, whitelist d'emails dans `config/cocon.php`
- **Registration désactivée** : un écran de setup (`/setup`) permet de créer un compte au premier lancement si l'email est dans la whitelist
- **Pas de reset password** : app locale sans serveur mail
- **Offline-first** : SQLite local, sync cloud prévue mais pas encore implémentée
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
| ShoppingItem | Article dans une liste |
| Todo | Tâche |
| MealPlan | Planning repas |
| MealIdea | Idée de repas |
| Note | Note libre |
| Bookmark | Marque-page |
| SyncLog | Journal de sync (futur) |

### Enums (app/Enums/)

- `SplitType` : Equal, FullPayer, FullOther, Custom
- `RecurrenceType` : types de récurrence
- `ShoppingItemCategory` : catégories d'articles
- `MealType` : types de repas
- `SyncAction` : actions de sync

### Services (app/Services/)

- `BalanceCalculator` : calcul de balance budget entre les 2 utilisateurs

### Middleware custom

- `RestrictToHousehold` : vérifie que l'email est dans la whitelist
- `HandleAppearance` : gestion du dark mode

### Controllers (app/Http/Controllers/)

- `DashboardController` : page d'accueil `/`
- `ExpenseController` : CRUD dépenses + settle + history
- `ShoppingListController` : CRUD + duplicate (complet)
- `ShoppingItemController` : store, toggleCheck, toggleFavorite, destroy
- `TodoController` : CRUD complet (index, store, update, toggle, destroy) — modal sur index
- `MealPlanController`, `NoteController`, `BookmarkController` : index seulement (stubs)
- `MoreController` : page "Plus"
- `Settings/ProfileController`, `Settings/PasswordController`, `Settings/TwoFactorAuthenticationController`
- `Auth/SetupController` : premier lancement
- `Auth/ApiLoginController` : login API (futur sync)

### Routes principales (routes/web.php)

- `GET /` : Dashboard (auth, verified)
- `GET|POST /setup` : Setup premier lancement (guest)
- `/expenses` : resource (sauf show) + `POST settle` + `GET history`
- `/shopping-lists` : resource (sauf edit) + `POST {id}/duplicate` + items (store, toggleCheck, toggleFavorite, destroy)
- `/todos` : resource (sauf create, show, edit) + `PATCH {id}/toggle`
- `/meal-plans`, `/notes`, `/bookmarks` : index seulement
- `/more` : page "Plus"
- Settings dans `routes/settings.php` : profil, mot de passe, 2FA, apparence

## Phases terminées

### Phase 1-4 : Fondations
- Setup projet Laravel 12 + NativePHP + Tailwind v4
- Modèles, migrations, factories, seeders pour tous les modules
- Auth Fortify : login, setup premier lancement, middleware household, 2FA
- Layout mobile avec AppLayout + BottomNav (5 onglets)
- Pages settings : profil, mot de passe, 2FA, apparence

### Phase 5 : Module Budget (complet)
- CRUD dépenses avec catégories et split types
- `BalanceCalculator` : calcul de qui doit quoi
- Settle (règlement) : archive les dépenses non réglées
- Historique des règlements
- 83 tests Pest passants (Feature + Unit)

### Phase 6 : Module Courses (complet)
- CRUD listes de courses avec templates et duplication
- Articles par liste : ajout, check/uncheck, favoris, suppression
- Groupement par catégorie (enum ShoppingItemCategory avec labels FR)
- Vue Show avec formulaire inline sticky, groupes par catégorie, section cochés pliable
- 20 tests Pest (ShoppingListTest + ShoppingItemTest)

### Phase 7 : Module Tâches (complet)
- CRUD complet via modal (store, update, toggle, destroy)
- Groupement : tâches partagées, tâches personnelles, tâches terminées (collapsible)
- Assignation à un utilisateur, date d'échéance optionnelle
- Toggle done/undone avec completed_at
- 15 tests Pest (TodoTest)

### Traduction FR + NativePHP safe areas
- Toutes les pages settings et auth traduites en français
- Safe areas NativePHP configurées (viewport-fit, CSS variables)
- Suppression de "mot de passe oublié" (inutile en local)

## Phases à venir

| Phase | Module | Statut |
|-------|--------|--------|
| 6 | Courses (shopping lists) | **Complet** |
| 7 | Tâches (todos) | **Complet** |
| 8 | Repas (meal plans + ideas) | Stub index |
| 9 | Notes | Stub index |
| 10 | Bookmarks | Stub index |
| 11 | Dashboard (widgets agrégés) | Page vide |
| 12 | Sync offline-first | Non commencé |
| 13 | Push notifications | Non commencé |
| 14 | Auto-update APK | Non commencé |

## Conventions de code

- **Tests** : `tests/Unit` sans Laravel, `tests/Feature` avec TestCase + RefreshDatabase
- **Routes custom AVANT les resource routes** pour éviter les conflits `{param}`
- **Plans** : fichiers `.md` à la racine, référencés dans `COCON_PLAN.md`
- **Pint** : `vendor/bin/pint --dirty --format agent` avant chaque finalisation
- **Wayfinder** : `php artisan wayfinder:generate` après modification de routes/controllers

## Fichiers de référence

- `COCON_PLAN.md` : plan global du projet
- `PHASE5_BUDGET.md` : plan détaillé phase 5
- `PHASE7_TODOS.md` : plan détaillé phase 7
- `SETUP_SCREEN.md` : plan écran de setup
- `config/cocon.php` : whitelist emails autorisés
- `config/fortify.php` : features auth (pas de registration, pas de reset password)
