# Contexte Cocoon

> **IMPORTANT : Ce fichier doit être mis à jour à chaque fois qu'une phase ou fonctionnalité majeure est terminée.**

## Qu'est-ce que Cocoon ?

App mobile de couple (Kevin + Lola) pour centraliser l'organisation quotidienne. Budget, courses, tâches, repas, notes, calendrier. Interface 100% français, Android uniquement, distribuée par APK direct (pas de Play Store).

## Stack technique

- **Backend** : Laravel 12, PHP 8.4, SQLite (local, offline-first)
- **Frontend** : Vue 3 + Inertia v2 + Tailwind CSS v4
- **Mobile** : NativePHP Mobile v3
- **Auth** : Laravel Fortify (login uniquement, pas de 2FA, pas de registration, pas de reset password)
- **Tests** : Pest 4
- **Routes TS** : Wayfinder
- **Sync** : Laravel Cloud API (Serverless Postgres) + Sanctum tokens
- **Biométrie** : NativePHP Mobile Biometrics + Secure Storage
- **Rappels** : `cocoon/local-notifications` (path package, AlarmManager Android)

## Contraintes clés

- **2 utilisateurs seulement** : Kevin et Lola, whitelist d'emails dans `config/cocon.php`
- **Registration désactivée** : écran de setup (`/setup`) au premier lancement si email dans la whitelist
- **Pas de reset password** : app locale sans serveur mail
- **Offline-first** : SQLite local, sync cloud via API (Syncable trait + SyncLog + SyncService)
- **Dashboard sur `/`** : pas de redirect, Fortify home = `'/'`
- **Safe areas NativePHP** : `viewport-fit=cover` + classe `nativephp-safe-area` sur `<body>`

## Architecture

### Modèles (app/Models/)

| Modèle | Rôle |
|--------|------|
| User | Utilisateur (2 max) |
| Expense | Dépense avec split type |
| ExpenseCategory | Catégorie de dépense (seeder) |
| ShoppingList | Liste de courses |
| ShoppingItem | Article dans une liste (category nullable — articles vocaux sans catégorie) |
| TodoList | Liste de tâches (partagée ou personnelle) |
| Todo | Tâche dans une TodoList (title, is_done, completed_at) |
| Recipe | Recette (titre, description, url, image_path, temps, portions, tags) |
| RecipeIngredient | Ingrédient d'une recette (nom, quantité, unité, ordre) |
| RecipeStep | Étape d'une recette (instruction, ordre) |
| Note | Note partagée (titre, contenu, couleur, épinglage) |
| SweetMessage | Mot doux entre partenaires (1 par utilisateur) |
| Joke | Blague du jour (seeder 50 blagues) |
| Birthday | Anniversaire (nom, date, âge calculé, reminder_days_before) |
| CalendarEvent | Événement calendrier (titre, catégorie colorée, dates, lieu, rappel en minutes, personnel) |
| SyncLog | Journal de sync (queue locale, pending/synced) |

### Enums (app/Enums/)

- `SplitType` : Equal, FullPayer, FullOther, Custom
- `ShoppingItemCategory` : catégories d'articles
- `MealTag` : Rapide, Vege, Comfort, Leger, Gourmand
- `NoteColor` : Default, Yellow, Green, Blue, Pink, Purple
- `EventCategory` : Conges, Pro, Loisir, Rdv (avec label() et color() hex)
- `SyncAction` : actions de sync

### Services (app/Services/)

- `BalanceCalculator` : calcul de balance budget entre les 2 utilisateurs
- `SyncService` : logique sync push/pull/full (last-write-wins, gestion recettes imbriquées)
- `ReminderService` : schedule/cancel rappels pour CalendarEvent et Birthday via LocalNotification facade

### Plugin local notifications (packages/cocoon/local-notifications)

- PHP : `LocalNotificationManager` + `Facades/LocalNotification` (no-op si `nativephp_call` absent)
- Kotlin : `LocalNotificationFunctions` (Schedule/Cancel/CancelAll) + `NotificationAlarmReceiver`
- Bridge calls : `LocalNotification.Schedule`, `LocalNotification.Cancel`, `LocalNotification.CancelAll`

### Controllers (app/Http/Controllers/)

- `DashboardController` : page d'accueil `/` avec widgets (mot doux, anniversaires du jour, blague, today widget)
- `SweetMessageController` : store (updateOrCreate)
- `BirthdayController` : CRUD complet — modal sur index, intègre ReminderService
- `ExpenseController` : CRUD dépenses + settle + history (mensuel/annuel/total)
- `ShoppingListController` : CRUD + duplicate
- `ShoppingItemController` : store, update, toggleCheck, destroy
- `TodoListController` : show, store, update, destroy
- `TodoController` : store (dans une liste), toggle, update, destroy
- `RecipeController` : resource complète avec image upload
- `NoteController` : index (retourne `items` = mélange notes+todoLists trié, épinglées en premier), show, store, update, togglePin, destroy
- `CalendarController` : index (?month=YYYY-MM, événements chevauchant le mois, anniversaires), store/update/destroy — intègre ReminderService
- `MoreController` : page "Plus"
- `Settings/ProfileController`, `Settings/PasswordController`
- `Auth/SetupController`, `Auth/BiometricController`, `Auth/ApiLoginController`
- `Api/SyncController` : push/pull/full
- `Api/AppVersionController` : check (version + signed URL) + download (stream APK)

### Navigation (BottomNav)

Accueil | Budget | Calendrier | Notes | Plus

**"Plus" contient :** Courses | Repas | Anniversaires | Paramètres

### Composants clés (resources/js/components/)

- `calendar/CalendarWeekRow.vue` : ligne de semaine avec barres multi-jours (max 2 lanes) + dots single-day
- `calendar/MonthYearPicker.vue` : popover grille 4×3 mois + navigation année
- `calendar/EventFormDialog.vue` : formulaire événement (all_day → date seule, multi-jours avec date fin)
- `dashboard/TodayWidget.vue` : widget "Aujourd'hui" cliquable (`<Link href="/calendar">`)
- `shopping/AddItemForm.vue` : formulaire ajout article (sans bouton micro — micro déplacé sur Show.vue)

## Phases terminées

- **Phase 1-4** : Setup, auth, layout, settings
- **Phase 5** : Budget (BalanceCalculator, CRUD, settle, historique)
- **Phase 6** : Courses (listes, articles, catégories)
- **Phase 7→19** : Tâches refactorisées (TodoList + Todo)
- **Phase 8→18** : Recettes (index grid, image upload)
- **Phase 9** : Notes (couleurs, épinglage)
- **Phase 11** : Dashboard + Anniversaires
- **Phase 12** : Sync offline-first (Syncable, SyncService, SyncClient JS)
- **Phase 13** : Biométrie (Face ID/empreinte, SecureStorage, BiometricController)
- **Phase 14** : Auto-update APK (AppVersionController, app:publish-release, UpdateDialog)
- **Phase 15** : Cleanup (suppression Bookmarks + MealIdeas, FAB, logo login)
- **Phase 16** : Shopping Refonte (cards ⋮, catégories collapsibles, localStorage last list)
- **Phase 17** : Budget V2 (catégories, historique mensuel/annuel/total, nav mois)
- **Phase 19** : Notes Fusion (TodoList, Notes/Show plein écran, BottomNav Tâches→Notes)
- **Phase 20** : Calendrier (CalendarEvent, EventCategory, grille mensuelle, Day Modal, plugin rappels)
- **Refonte UX** :
  - Courses : FAB vocal flottant centré (ajout automatique sans catégorie, groupe "Sans catégorie")
  - Calendrier : barres multi-jours (CalendarWeekRow), sélecteur mois/année (MonthYearPicker), date fin pour all_day
  - Dashboard : TodayWidget entièrement cliquable → `/calendar`
  - Notes : grille Google Keep (2 col, mixte notes+listes), FAB speed dial Note/Liste
- **212 tests passants**

## Phases à venir

| Phase | Module | Statut |
|-------|--------|--------|
| 21 | Dashboard V2 (widget événements du jour) | Non commencé |
| 22 | Bugs (blagues + mot mignon qui ne s'affichent pas) | Non commencé |

## Conventions de code

- **Tests** : `tests/Unit` sans Laravel, `tests/Feature` avec TestCase + RefreshDatabase
- **Routes custom AVANT les resource routes** pour éviter les conflits `{param}`
- **Pint** : `vendor/bin/pint --dirty --format agent` avant chaque finalisation
- **Wayfinder** : `php artisan wayfinder:generate` après modification de routes/controllers
- **Images** : `Storage::disk('public')`, symlink via `php artisan storage:link`, URL `/storage/{path}`
- **mobilePut / mobilePatch** : workaround Android WebView pour PUT/PATCH (POST + _method spoofing)

## Fichiers de référence

- `config/cocon.php` : whitelist emails autorisés
- `config/fortify.php` : features auth
- `packages/cocoon/local-notifications/` : plugin rappels Android
