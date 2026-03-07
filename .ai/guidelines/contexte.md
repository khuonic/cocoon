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
| Todo | Tâche dans une TodoList (title, position, is_done, completed_at) |
| Recipe | Recette (titre, description, url, image_path, temps, portions, tags) |
| RecipeIngredient | Ingrédient d'une recette (nom, quantité, unité, ordre) |
| RecipeStep | Étape d'une recette (instruction, ordre) |
| Note | Note partagée (titre, contenu, couleur, épinglage) |
| SweetMessage | Mot doux entre partenaires (1 par utilisateur, updateOrCreate) |
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

- `DashboardController` : page d'accueil `/` — salutation personnalisée, mot doux partenaire, TodayWidget, blague
- `SweetMessageController` : store (updateOrCreate — 1 message par user)
- `BirthdayController` : CRUD complet — modal sur index, intègre ReminderService
- `ExpenseController` : CRUD dépenses + settle + history (mensuel/annuel/total) + changelog (activity log via spatie/laravel-activitylog)
- `ShoppingListController` : CRUD + duplicate
- `ShoppingItemController` : store, update, toggleCheck, destroy
- `TodoListController` : show, store, update, destroy
- `TodoController` : store, toggle, update, reorder, destroy
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

- `calendar/CalendarWeekRow.vue` : ligne de semaine style Google Agenda — barres multi-jours (max 2 lanes) + badges événements single-day avec titre
- `calendar/MonthYearPicker.vue` : popover grille 4×3 mois + navigation année — props `navigateTo` + `navigateParams` pour réutilisation (Budget/History)
- `calendar/EventFormDialog.vue` : formulaire événement — bouton Enregistrer dans le header, Switch pour all_day/is_personal, date fin pour all_day
- `dashboard/SweetMessageWidget.vue` : salutation personnalisée (heure) + message du partenaire en italic
- `dashboard/TodayWidget.vue` : widget "Aujourd'hui" entièrement cliquable (`<Link href="/calendar">`)
- `shopping/AddItemForm.vue` : formulaire ajout article (sans bouton micro — micro déplacé sur Show.vue)
- `ui/dialog/DialogContent.vue` : positionné en `top-4` par défaut (évite que le clavier couvre le modal) — prop `position: 'top'|'center'` ; tap sur fond blur le focus (dismiss clavier)

## Fonctionnement SweetMessage

- 1 enregistrement par utilisateur en base (`updateOrCreate` sur `user_id`)
- Dashboard affiche le message de **l'autre** utilisateur (lecture seule)
- **FAB cœur** (bas droite) : rose plein si message existant, rose pâle sinon → ouvre dialog d'édition
- Le message reste permanent jusqu'à réécriture

## Fonctionnement Drag & Drop Todos

- Colonne `position` sur `todos` (migration `2026_03_06_...`)
- Endpoint `POST /todo-lists/{todo_list}/todos/reorder` → `TodoController@reorder`
- Frontend : drag natif HTML5 (`draggable`, `@dragstart/enter/end`) avec handle GripVertical
- Réordonnancement local immédiat pour feedback visuel + sauvegarde serveur

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
- **Refonte UX multi-modules** :
  - Courses : FAB vocal flottant centré, category nullable, groupe "Sans catégorie", cards 3 colonnes, fond différent cochés
  - Calendrier : style Google Agenda (bordures, cellules hautes, badges titres), barres multi-jours, sélecteur mois/année, swipe navigation, Switch toggles, bouton save dans header, FAB speed dial Événement/Anniversaire, BirthdayFormDialog inline, date pré-remplie depuis clic sur jour
  - Budget : description en premier, date+montant même ligne, "Vous êtes quittes" redesign, MonthYearPicker dans historique, changelog activité via spatie/laravel-activitylog, édition dépenses réglées (reset settled_at)
  - Notes : grille Google Keep (2 col, mixte), FAB speed dial Note/Liste, line-clamp-8, TodoList Enter=nouvelle tâche, drag & drop reorder
  - Dashboard : salutation personnalisée (heure), mot doux partenaire en bannière, FAB cœur pour édition
  - Global : DialogContent top-4 par défaut, dismiss clavier au tap, boutons redesign (rounded-lg, font-semibold, h-10)
  - Login : logo agrandi (size-36), suppression texte superflu
- **225 tests passants**

## Conventions de code

- **Tests** : `tests/Unit` sans Laravel, `tests/Feature` avec TestCase + RefreshDatabase
- **Routes custom AVANT les resource routes** pour éviter les conflits `{param}`
- **Pint** : `vendor/bin/pint --dirty --format agent` avant chaque finalisation
- **Wayfinder** : `php artisan wayfinder:generate` après modification de routes/controllers
- **Images** : `Storage::disk('public')`, symlink via `php artisan storage:link`, URL `/storage/{path}`
- **mobilePut / mobilePatchForm** : workaround Android WebView pour PUT/PATCH (POST + _method spoofing) — utiliser ces helpers, jamais `form.put()` ou `form.patch()` directement
- **$fillable** : toujours ajouter les nouvelles colonnes dans `$fillable` du modèle sinon silently ignored

## Fichiers de référence

- `config/cocon.php` : whitelist emails autorisés
- `config/fortify.php` : features auth
- `packages/cocoon/local-notifications/` : plugin rappels Android
