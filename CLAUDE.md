<laravel-boost-guidelines>
=== .ai/contexte rules ===

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
| SyncLog | Journal de sync (queue locale, pending/synced — `synced_at` null = non encore pushé au Cloud) |

### Enums (app/Enums/)

- `SplitType` : Equal, FullPayer, FullOther, Custom
- `ShoppingItemCategory` : catégories d'articles
- `MealTag` : Rapide, Vege, Comfort, Leger, Gourmand
- `NoteColor` : Default, Yellow, Green, Blue, Pink, Purple
- `EventCategory` : Conges, Pro, Loisir, Rdv (avec label() et color() hex)
- `SyncAction` : actions de sync (Created, Updated, Deleted)

### Services (app/Services/)

- `BalanceCalculator` : calcul de balance budget entre les 2 utilisateurs
- `SyncService` : logique sync push/pull/full/pending/acknowledge (last-write-wins, gestion recettes imbriquées)
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
- `Api/SyncController` : push / pull / full / pending / acknowledge
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

## Architecture de sync

### Deux tokens distincts

| Token | Clé localStorage | Usage |
|-------|-----------------|-------|
| `cocoon_auth_token` | local Sanctum | Authentifie les appels API **locaux** (`/api/sync/pending`, `/api/sync/acknowledge`) |
| `cocoon_sync_token` | Cloud Sanctum | Authentifie les appels API **Cloud** (`push`, `pull`, `full`, `app/version`) |

- `cocoon_auth_token` est flashé en session (`api_token`) au login et au setup, puis sauvé dans localStorage
- `cocoon_sync_token` est obtenu via `POST {syncApiUrl}/api/login` au login/setup, puis sauvé dans localStorage

### Flux push/pull

```
[Syncable trait] → SyncLog (synced_at = null)
                         ↓
[sync-client.ts::sync()]
  ├─ fullSync() : GET /api/sync/pending (local) → POST {cloud}/api/sync/full → POST /api/sync/acknowledge (local)
  └─ pushLocalChanges() + pull() : GET /api/sync/pending → POST {cloud}/api/sync/push → acknowledge → GET {cloud}/api/sync/pull
```

- `isSyncing = true` sur le modèle → skip `queueSync()` pour éviter les boucles infinies
- Last-write-wins via `updated_at` (le plus récent gagne)
- Recipes : ingrédients et étapes inclus dans le payload (delete+recreate à chaque sync)

### Routes API locales (auth:sanctum local)

- `GET /api/sync/pending` → retourne `{ changes: SyncChange[], ids: int[] }` (SyncLog non synced)
- `POST /api/sync/acknowledge` → marque les SyncLog `ids` comme synced (`synced_at = now()`)

### Routes API Cloud (auth:sanctum Cloud)

- `POST /api/sync/push` → applique des changements entrants
- `GET /api/sync/pull?since=` → retourne les changements depuis un timestamp
- `POST /api/sync/full` → push + retourne toutes les données Cloud
- `GET /api/app/version` → vérifie si une mise à jour APK est disponible

## Déploiement production

- **Backend Cloud** : Laravel Cloud (Serverless, Postgres, bucket S3 privé)
- **APK** : signé avec keystore dans `credentials/`, distribué via `app:publish-release`
- **Auto-update** : `latest.json` sur S3 consulté au lancement, `UpdateDialog` propose le téléchargement
- **Build commands Cloud** : `composer config http-basic` + `composer install` sans `--prefer-dist` ni `npm run build`
- **Windows SSL** : `AWS_VERIFY_SSL=false` dans `.env` local + `'http' => ['verify' => env('AWS_VERIFY_SSL', true)]` dans `config/filesystems.php` disk s3

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
- **Mise en prod** : Laravel Cloud déployé, APK signé, push sync implémenté (SyncLog → Cloud), biométrie opérationnelle
- **231 tests passants**

## Conventions de code

- **Tests** : `tests/Unit` sans Laravel, `tests/Feature` avec TestCase + RefreshDatabase
- **Routes custom AVANT les resource routes** pour éviter les conflits `{param}`
- **Pint** : `vendor/bin/pint --dirty --format agent` avant chaque finalisation
- **Wayfinder** : `php artisan wayfinder:generate` après modification de routes/controllers
- **Images** : `Storage::disk('public')`, symlink via `php artisan storage:link`, URL `/storage/{path}`
- **mobilePut / mobilePatchForm** : workaround Android WebView pour PUT/PATCH (POST + _method spoofing) — utiliser ces helpers, jamais `form.put()` ou `form.patch()` directement
- **$fillable** : toujours ajouter les nouvelles colonnes dans `$fillable` du modèle sinon silently ignored
- **API routes sans statefulApi()** : `bootstrap/app.php` n'utilise pas `$middleware->statefulApi()` — les routes API sont purement Bearer token (pas de CSRF)

## Fichiers de référence

- `config/cocon.php` : whitelist emails autorisés
- `config/fortify.php` : features auth
- `packages/cocoon/local-notifications/` : plugin rappels Android
- `MISE_EN_PROD.md` : guide complet déploiement Laravel Cloud + build APK

=== foundation rules ===

# Laravel Boost Guidelines

## Foundational Context

This application is a Laravel application running on PHP 8.4. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If a frontend change doesn't show in the UI or you get a "Unable to locate file in Vite manifest" error, run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists, including path-scoped framework guidelines under `.ai/rules/boost`. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.
- Activate the `deploying-to-cloud` skill whenever deploying to Laravel Cloud, configuring Cloud environments or resources, using the Cloud CLI, or troubleshooting Cloud deployments.

=== herd rules ===

# Laravel Herd

- The application is served by Laravel Herd at `https?://[kebab-case-project-dir].test`. Use the `get-absolute-url` tool to generate valid URLs. Never run commands to serve the site. It is always available.
- Use the `herd` CLI to manage services, PHP versions, and sites (e.g. `herd sites`, `herd services:start <service>`, `herd php:list`). Run `herd list` to discover all available commands.

=== tests rules ===

# Test Enforcement

- Add or update tests for behavior and logic changes when a test provides meaningful regression coverage.
- Pure copy, styling, and layout-only changes do not require new or updated tests.
- When test coverage applies, run the affected tests and ensure they pass.
- Test the changed behavior and its important failure modes, but do not add tests beyond them.
- Read the `testing-best-practices` skill before writing tests.

=== inertia-laravel/core rules ===

# Inertia

- Inertia creates fully client-side rendered SPAs without modern SPA complexity, leveraging existing server-side patterns.
- Components live in `resources/js/pages` (unless specified in `vite.config.js`). Use `Inertia::render()` for server-side routing instead of Blade views.
- ALWAYS use `search-docs` tool for version-specific Inertia documentation and updated code examples.
- IMPORTANT: Activate `inertia-vue-development` when working with Inertia Vue client-side patterns.

# Inertia v2

- Use all Inertia features from v1 and v2. Check the documentation before making changes to ensure the correct approach.
- New features: deferred props, infinite scroll, merging props, polling, prefetching, once props, flash data.
- When using deferred props, add an empty state with a pulsing or animated skeleton.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

=== wayfinder/core rules ===

# Laravel Wayfinder

Use Wayfinder to generate TypeScript functions for Laravel routes. Import from `@/actions/` (controllers) or `@/routes/` (named routes).

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

# Pest

- This project uses Pest. Create tests with `php artisan make:test --pest {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.
- Do not delete tests or test files without approval. They are part of the application.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/pest` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.

=== inertia-vue/core rules ===

# Inertia + Vue

Vue components must have a single root element.
- IMPORTANT: Activate `inertia-vue-development` when working with Inertia Vue client-side patterns.

=== nativephp/mobile/core rules ===

## NativePHP Mobile

- NativePHP Mobile is a Laravel package for building native iOS and Android apps using PHP and native UI components. It runs a full PHP runtime directly on the device with SQLite — no web server required.
- Documentation: `https://nativephp.com/docs/mobile/3/**`
- IMPORTANT: Always activate the `nativephp-mobile` skill every time you work on any NativePHP functionality.

### Build Commands — Tell the User, Never Run

**CRITICAL: Never execute any of these commands yourself. Always instruct the user to run them manually in their terminal.**

| Command | Purpose |
|---|---|
| `npm run build -- --mode=ios` | Build frontend assets for iOS |
| `npm run build -- --mode=android` | Build frontend assets for Android |
| `php artisan native:run ios` | Compile and run on iOS simulator/device |
| `php artisan native:run android` | Compile and run on Android emulator/device |
| `php artisan native:run ios --watch` | Build, deploy, then start hot reload — all in one |
| `php artisan native:watch` | Hot reload (watch for file changes) |
| `php artisan native:open` | Open project in Xcode or Android Studio |

**Always ask which platform before giving any build or run command.** If the user hasn't specified iOS or Android, ask: "Which platform do you want to build/test on — iOS or Android?" Never assume a platform.

When the platform is confirmed, give the relevant command(s) above and tell the user to run it in their terminal. Do not run it yourself.
</laravel-boost-guidelines>

=== nativephp/mobile-biometrics/core rules ===

## nativephp/biometrics

Biometric authentication plugin for NativePHP Mobile (Face ID, Touch ID, Fingerprint).

### PHP Usage (Livewire/Blade)

Use the `Biometrics` facade:

<code-snippet name="Using Biometrics Facade" lang="php">
use Native\Mobile\Facades\Biometrics;

// Prompt for biometric authentication
Biometrics::prompt();
</code-snippet>

### JavaScript Usage (Vue/React/Inertia)

<code-snippet name="Using Biometrics in JavaScript" lang="javascript">
import { biometric } from '#nativephp';

// Basic usage
await biometric.prompt();

// With identifier for tracking
await biometric.prompt().id('secure-action-auth');
</code-snippet>

### Handling Biometric Events

#### PHP

<code-snippet name="Listening for Biometric Events in PHP" lang="php">
use Native\Mobile\Attributes\OnNative;
use Native\Mobile\Events\Biometric\Completed;

#[OnNative(Completed::class)]
public function handleBiometric(bool $success)
{
    if ($success) {
        $this->unlockSecureFeature();
    } else {
        $this->showErrorMessage();
    }
}
</code-snippet>

#### Vue

<code-snippet name="Listening for Biometric Events in Vue" lang="javascript">
import { biometric, on, off, Events } from '#nativephp';
import { ref, onMounted, onUnmounted } from 'vue';

const handleBiometricComplete = (payload) => {
    if (payload.success) {
        isAuthenticated.value = true;
    } else {
        showErrorMessage();
    }
};

onMounted(() => {
    on(Events.Biometric.Completed, handleBiometricComplete);
});

onUnmounted(() => {
    off(Events.Biometric.Completed, handleBiometricComplete);
});
</code-snippet>

### Platform Support

- **iOS**: Face ID, Touch ID
- **Android**: Fingerprint, face unlock, other biometric methods
- **Fallback**: System authentication (PIN, password, pattern)

=== nativephp/mobile-camera/core rules ===

## nativephp/camera

Camera plugin for NativePHP Mobile providing photo capture, video recording, and gallery picker functionality.

### PHP Usage (Livewire/Blade)

<code-snippet name="Taking Photos" lang="php">
use Native\Mobile\Facades\Camera;

// Take a photo
Camera::getPhoto();
</code-snippet>

<code-snippet name="Recording Videos" lang="php">
use Native\Mobile\Facades\Camera;

// Using fluent API
Camera::recordVideo()
    ->maxDuration(60)
    ->id('my-video-123')
    ->start();
</code-snippet>

<code-snippet name="Picking Media from Gallery" lang="php">
use Native\Mobile\Facades\Camera;

// Pick multiple images
Camera::pickImages('images', true);

// Pick any media type
Camera::pickImages('all', true);
</code-snippet>

### JavaScript Usage (Vue/React/Inertia)

<code-snippet name="Camera in JavaScript" lang="javascript">
import { camera } from '#nativephp';

// Take a photo with identifier
await camera.getPhoto().id('profile-pic');

// Record video with max duration
await camera.recordVideo()
    .maxDuration(30)
    .id('my-video-123');

// Pick multiple images from gallery
await camera.pickImages()
    .images()
    .multiple()
    .maxItems(5);
</code-snippet>

### Handling Camera Events

#### PHP

<code-snippet name="Photo Events" lang="php">
use Native\Mobile\Attributes\OnNative;
use Native\Mobile\Events\Camera\PhotoTaken;

#[OnNative(PhotoTaken::class)]
public function handlePhotoTaken(string $path)
{
    $this->processPhoto($path);
}
</code-snippet>

<code-snippet name="Video Events" lang="php">
use Native\Mobile\Attributes\OnNative;
use Native\Mobile\Events\Camera\VideoRecorded;

#[OnNative(VideoRecorded::class)]
public function handleVideoRecorded(string $path, string $mimeType, ?string $id = null)
{
    $this->processVideo($path);
}
</code-snippet>

<code-snippet name="Gallery Events" lang="php">
use Native\Mobile\Attributes\OnNative;
use Native\Mobile\Events\Gallery\MediaSelected;

#[OnNative(MediaSelected::class)]
public function handleMediaSelected(array $media)
{
    foreach ($media as $file) {
        $this->processMedia($file);
    }
}
</code-snippet>

### Events

- `Native\Mobile\Events\Camera\PhotoTaken` - Photo captured (payload: `string $path`)
- `Native\Mobile\Events\Camera\VideoRecorded` - Video recorded (payload: `string $path`, `string $mimeType`, `?string $id`)
- `Native\Mobile\Events\Camera\VideoCancelled` - Recording cancelled
- `Native\Mobile\Events\Gallery\MediaSelected` - Media selected (payload: `array $media`)

### Capture Date and Location

`PhotoTaken` and each file in `MediaSelected` may additionally carry `takenAt` (ISO-8601 UTC string), `latitude` and `longitude` (decimal degrees). Keys are omitted when unavailable. `takenAt` is read from EXIF and needs no permissions. Pass `includeLocation: true` to actively recover GPS coordinates; this prompts for location / photo library access (iOS) or `ACCESS_MEDIA_LOCATION` (Android). Default is `false`, so no new prompts are shown.

<code-snippet name="Opting into location" lang="php">
use Native\Mobile\Facades\Camera;
use Native\Mobile\PendingMediaPicker;

Camera::getPhoto(['includeLocation' => true]);

(new PendingMediaPicker(['includeLocation' => true]))->images()->start();
</code-snippet>

### Storage Locations

- **Camera (Android):** `{noBackupFilesDir}/Camera/`
- **Camera (iOS):** `~/Library/Application Support/Camera/`
- **Gallery (Android):** `{noBackupFilesDir}/Gallery/`
- **Gallery (iOS):** `~/Library/Application Support/Gallery/`

These folders are readable by PHP, persistent and excluded from device backups. The plugin never deletes them, so copy files you want to keep into app storage in the event handler and `unlink()` the original.

=== nativephp/mobile-secure-storage/core rules ===

## nativephp/secure-storage

Encrypted data persistence using iOS Keychain and Android Keystore.

### PHP Usage (Livewire/Blade)

<code-snippet name="Secure Storage Operations" lang="php">
use Native\Mobile\Facades\SecureStorage;

// Store encrypted data
SecureStorage::set('api_token', 'abc123xyz');

// Retrieve encrypted data
$token = SecureStorage::get('api_token');

// Delete stored data
SecureStorage::delete('api_token');
</code-snippet>

### JavaScript Usage (Vue/React/Inertia)

<code-snippet name="Secure Storage in JavaScript" lang="javascript">
import { secureStorage } from '#nativephp';

// Store encrypted data
const result = await secureStorage.set('api_token', 'abc123xyz');

// Retrieve encrypted data
const result = await secureStorage.get('api_token');
const token = result.value;

// Delete stored data
const result = await secureStorage.delete('api_token');
</code-snippet>

### Methods

- `SecureStorage::set(string $key, string|null $value)` - Store encrypted data
- `SecureStorage::get(string $key)` - Retrieve encrypted data (returns `string|null`)
- `SecureStorage::delete(string $key)` - Remove stored data

### Platform Implementation

**iOS**: Uses iOS Keychain Services API with encryption tied to app bundle ID.

**Android**: Uses Android Keystore system with hardware-backed encryption when available.

### Recommended Storage

API tokens, refresh tokens, encryption keys, authentication secrets, sensitive user preferences.

### Not Recommended

Large datasets, non-sensitive information, temporary data, cached content.

</laravel-boost-guidelines>

=== nativephp/mobile-biometrics/core rules ===

## nativephp/biometrics

Biometric authentication plugin for NativePHP Mobile (Face ID, Touch ID, Fingerprint).

### PHP Usage (Livewire/Blade)

Use the `Biometrics` facade:

<code-snippet name="Using Biometrics Facade" lang="php">
use Native\Mobile\Facades\Biometrics;

// Prompt for biometric authentication
Biometrics::prompt();
</code-snippet>

### JavaScript Usage (Vue/React/Inertia)

<code-snippet name="Using Biometrics in JavaScript" lang="javascript">
import { biometric } from '#nativephp';

// Basic usage
await biometric.prompt();

// With identifier for tracking
await biometric.prompt().id('secure-action-auth');
</code-snippet>

### Handling Biometric Events

#### PHP

<code-snippet name="Listening for Biometric Events in PHP" lang="php">
use Native\Mobile\Attributes\OnNative;
use Native\Mobile\Events\Biometric\Completed;

#[OnNative(Completed::class)]
public function handleBiometric(bool $success)
{
    if ($success) {
        $this->unlockSecureFeature();
    } else {
        $this->showErrorMessage();
    }
}
</code-snippet>

#### Vue

<code-snippet name="Listening for Biometric Events in Vue" lang="javascript">
import { biometric, on, off, Events } from '#nativephp';
import { ref, onMounted, onUnmounted } from 'vue';

const handleBiometricComplete = (payload) => {
    if (payload.success) {
        isAuthenticated.value = true;
    } else {
        showErrorMessage();
    }
};

onMounted(() => {
    on(Events.Biometric.Completed, handleBiometricComplete);
});

onUnmounted(() => {
    off(Events.Biometric.Completed, handleBiometricComplete);
});
</code-snippet>

### Platform Support

- **iOS**: Face ID, Touch ID
- **Android**: Fingerprint, face unlock, other biometric methods
- **Fallback**: System authentication (PIN, password, pattern)

=== nativephp/mobile-camera/core rules ===

## nativephp/camera

Camera plugin for NativePHP Mobile providing photo capture, video recording, and gallery picker functionality.

### PHP Usage (Livewire/Blade)

<code-snippet name="Taking Photos" lang="php">
use Native\Mobile\Facades\Camera;

// Take a photo
Camera::getPhoto();
</code-snippet>

<code-snippet name="Recording Videos" lang="php">
use Native\Mobile\Facades\Camera;

// Using fluent API
Camera::recordVideo()
    ->maxDuration(60)
    ->id('my-video-123')
    ->start();
</code-snippet>

<code-snippet name="Picking Media from Gallery" lang="php">
use Native\Mobile\Facades\Camera;

// Pick multiple images
Camera::pickImages('images', true);

// Pick any media type
Camera::pickImages('all', true);
</code-snippet>

### JavaScript Usage (Vue/React/Inertia)

<code-snippet name="Camera in JavaScript" lang="javascript">
import { camera } from '#nativephp';

// Take a photo with identifier
await camera.getPhoto().id('profile-pic');

// Record video with max duration
await camera.recordVideo()
    .maxDuration(30)
    .id('my-video-123');

// Pick multiple images from gallery
await camera.pickImages()
    .images()
    .multiple()
    .maxItems(5);
</code-snippet>

### Handling Camera Events

#### PHP

<code-snippet name="Photo Events" lang="php">
use Native\Mobile\Attributes\OnNative;
use Native\Mobile\Events\Camera\PhotoTaken;

#[OnNative(PhotoTaken::class)]
public function handlePhotoTaken(string $path)
{
    $this->processPhoto($path);
}
</code-snippet>

<code-snippet name="Video Events" lang="php">
use Native\Mobile\Attributes\OnNative;
use Native\Mobile\Events\Camera\VideoRecorded;

#[OnNative(VideoRecorded::class)]
public function handleVideoRecorded(string $path, string $mimeType, ?string $id = null)
{
    $this->processVideo($path);
}
</code-snippet>

<code-snippet name="Gallery Events" lang="php">
use Native\Mobile\Attributes\OnNative;
use Native\Mobile\Events\Gallery\MediaSelected;

#[OnNative(MediaSelected::class)]
public function handleMediaSelected(array $media)
{
    foreach ($media as $file) {
        $this->processMedia($file);
    }
}
</code-snippet>

### Events

- `Native\Mobile\Events\Camera\PhotoTaken` - Photo captured (payload: `string $path`)
- `Native\Mobile\Events\Camera\VideoRecorded` - Video recorded (payload: `string $path`, `string $mimeType`, `?string $id`)
- `Native\Mobile\Events\Camera\VideoCancelled` - Recording cancelled
- `Native\Mobile\Events\Gallery\MediaSelected` - Media selected (payload: `array $media`)

### Capture Date and Location

`PhotoTaken` and each file in `MediaSelected` may additionally carry `takenAt` (ISO-8601 UTC string), `latitude` and `longitude` (decimal degrees). Keys are omitted when unavailable. `takenAt` is read from EXIF and needs no permissions. Pass `includeLocation: true` to actively recover GPS coordinates; this prompts for location / photo library access (iOS) or `ACCESS_MEDIA_LOCATION` (Android). Default is `false`, so no new prompts are shown.

<code-snippet name="Opting into location" lang="php">
use Native\Mobile\Facades\Camera;
use Native\Mobile\PendingMediaPicker;

Camera::getPhoto(['includeLocation' => true]);

(new PendingMediaPicker(['includeLocation' => true]))->images()->start();
</code-snippet>

### Storage Locations

- **Camera (Android):** `{noBackupFilesDir}/Camera/`
- **Camera (iOS):** `~/Library/Application Support/Camera/`
- **Gallery (Android):** `{noBackupFilesDir}/Gallery/`
- **Gallery (iOS):** `~/Library/Application Support/Gallery/`

These folders are readable by PHP, persistent and excluded from device backups. The plugin never deletes them, so copy files you want to keep into app storage in the event handler and `unlink()` the original.

=== nativephp/mobile-secure-storage/core rules ===

## nativephp/secure-storage

Encrypted data persistence using iOS Keychain and Android Keystore.

### PHP Usage (Livewire/Blade)

<code-snippet name="Secure Storage Operations" lang="php">
use Native\Mobile\Facades\SecureStorage;

// Store encrypted data
SecureStorage::set('api_token', 'abc123xyz');

// Retrieve encrypted data
$token = SecureStorage::get('api_token');

// Delete stored data
SecureStorage::delete('api_token');
</code-snippet>

### JavaScript Usage (Vue/React/Inertia)

<code-snippet name="Secure Storage in JavaScript" lang="javascript">
import { secureStorage } from '#nativephp';

// Store encrypted data
const result = await secureStorage.set('api_token', 'abc123xyz');

// Retrieve encrypted data
const result = await secureStorage.get('api_token');
const token = result.value;

// Delete stored data
const result = await secureStorage.delete('api_token');
</code-snippet>

### Methods

- `SecureStorage::set(string $key, string|null $value)` - Store encrypted data
- `SecureStorage::get(string $key)` - Retrieve encrypted data (returns `string|null`)
- `SecureStorage::delete(string $key)` - Remove stored data

### Platform Implementation

**iOS**: Uses iOS Keychain Services API with encryption tied to app bundle ID.

**Android**: Uses Android Keystore system with hardware-backed encryption when available.

### Recommended Storage

API tokens, refresh tokens, encryption keys, authentication secrets, sensitive user preferences.

### Not Recommended

Large datasets, non-sensitive information, temporary data, cached content.

</laravel-boost-guidelines>

=== nativephp/mobile-biometrics/core rules ===

## nativephp/biometrics

Biometric authentication plugin for NativePHP Mobile (Face ID, Touch ID, Fingerprint).

### PHP Usage (Livewire/Blade)

Use the `Biometrics` facade:

<code-snippet name="Using Biometrics Facade" lang="php">
use Native\Mobile\Facades\Biometrics;

// Prompt for biometric authentication
Biometrics::prompt();
</code-snippet>

### JavaScript Usage (Vue/React/Inertia)

<code-snippet name="Using Biometrics in JavaScript" lang="javascript">
import { biometric } from '#nativephp';

// Basic usage
await biometric.prompt();

// With identifier for tracking
await biometric.prompt().id('secure-action-auth');
</code-snippet>

### Handling Biometric Events

#### PHP

<code-snippet name="Listening for Biometric Events in PHP" lang="php">
use Native\Mobile\Attributes\OnNative;
use Native\Mobile\Events\Biometric\Completed;

#[OnNative(Completed::class)]
public function handleBiometric(bool $success)
{
    if ($success) {
        $this->unlockSecureFeature();
    } else {
        $this->showErrorMessage();
    }
}
</code-snippet>

#### Vue

<code-snippet name="Listening for Biometric Events in Vue" lang="javascript">
import { biometric, on, off, Events } from '#nativephp';
import { ref, onMounted, onUnmounted } from 'vue';

const handleBiometricComplete = (payload) => {
    if (payload.success) {
        isAuthenticated.value = true;
    } else {
        showErrorMessage();
    }
};

onMounted(() => {
    on(Events.Biometric.Completed, handleBiometricComplete);
});

onUnmounted(() => {
    off(Events.Biometric.Completed, handleBiometricComplete);
});
</code-snippet>

### Platform Support

- **iOS**: Face ID, Touch ID
- **Android**: Fingerprint, face unlock, other biometric methods
- **Fallback**: System authentication (PIN, password, pattern)

</laravel-boost-guidelines>

=== nativephp/mobile-biometrics rules ===

## nativephp/biometrics

Biometric authentication plugin for NativePHP Mobile (Face ID, Touch ID, Fingerprint).

### PHP Usage (Livewire/Blade)

Use the `Biometrics` facade:

<code-snippet name="Using Biometrics Facade" lang="php">
use Native\Mobile\Facades\Biometrics;

// Prompt for biometric authentication
Biometrics::prompt();
</code-snippet>

### JavaScript Usage (Vue/React/Inertia)

<code-snippet name="Using Biometrics in JavaScript" lang="javascript">
import { biometric } from '#nativephp';

// Basic usage
await biometric.prompt();

// With identifier for tracking
await biometric.prompt().id('secure-action-auth');
</code-snippet>

### Handling Biometric Events

#### PHP

<code-snippet name="Listening for Biometric Events in PHP" lang="php">
use Native\Mobile\Attributes\OnNative;
use Native\Mobile\Events\Biometric\Completed;

#[OnNative(Completed::class)]
public function handleBiometric(bool $success)
{
    if ($success) {
        $this->unlockSecureFeature();
    } else {
        $this->showErrorMessage();
    }
}
</code-snippet>

#### Vue

<code-snippet name="Listening for Biometric Events in Vue" lang="javascript">
import { biometric, on, off, Events } from '#nativephp';
import { ref, onMounted, onUnmounted } from 'vue';

const handleBiometricComplete = (payload) => {
    if (payload.success) {
        isAuthenticated.value = true;
    } else {
        showErrorMessage();
    }
};

onMounted(() => {
    on(Events.Biometric.Completed, handleBiometricComplete);
});

onUnmounted(() => {
    off(Events.Biometric.Completed, handleBiometricComplete);
});
</code-snippet>

### Platform Support

- **iOS**: Face ID, Touch ID
- **Android**: Fingerprint, face unlock, other biometric methods
- **Fallback**: System authentication (PIN, password, pattern)

</laravel-boost-guidelines>
