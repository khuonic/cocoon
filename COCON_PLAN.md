# Cocon - Plan d'implémentation

## Contexte
App mobile personnelle pour un couple (développeur + enseignante), remplaçant Tricount et centralisant l'organisation quotidienne. Nouveau projet Laravel 12, Android uniquement, interface 100% français.

## Stack technique
- **Mobile** : NativePHP Mobile v2 (SQLite local, offline-first)
- **Backend sync** : Laravel 12 API sur Laravel Cloud (Serverless Postgres)
- **Frontend** : Vue 3 + Inertia v2 + Tailwind CSS v4
- **Auth** : Laravel Sanctum (token)
- **Notifications** : Firebase Cloud Messaging (plugin NativePHP Push Notifications)
- **Distribution** : APK direct (pas de Play Store)

---

## Phase 1 : Setup du projet

### 1.1 Création du projet Laravel
```bash
laravel new cocon
cd cocon
```
- Choisir : Vue + Inertia, Pest, SQLite (dev local)
- Configurer Herd pour le projet

### 1.2 Dépendances supplémentaires
```bash
composer require nativephp/mobile
composer require laravel/sanctum
php artisan native:install
```

### 1.3 Configuration NativePHP
- `config/nativephp.php` : APP_ID `com.cocon.app`
- `.env` : `NATIVEPHP_APP_ID`, `NATIVEPHP_APP_VERSION=1.0.0`, `NATIVEPHP_APP_VERSION_CODE=1`
- Installer les plugins premium nécessaires : Push Notifications, Biometrics, Secure Storage

### 1.4 Configuration Tailwind v4
- Vérifier que le setup par défaut de Laravel 12 utilise bien Tailwind v4
- Configurer les couleurs du thème Cocon (tons chauds, cocon/foyer)

---

## Phase 2 : Base de données & Modèles

### 2.1 Modèles et migrations

**User** (existe déjà, à adapter)
- `id`, `name`, `email`, `password`, `avatar`, `timestamps`

**ExpenseCategory**
- `id`, `name`, `icon`, `color`, `sort_order`, `timestamps`

**Expense**
- `id`, `amount` (decimal 10,2), `description`, `category_id` (FK), `paid_by` (FK user), `split_type` (enum: equal, full_payer, full_other, custom), `split_value` (nullable decimal), `date`, `is_recurring` (bool), `recurrence_type` (nullable enum: weekly, monthly, yearly), `settled_at` (nullable), `uuid` (pour la sync), `timestamps`

**ShoppingList**
- `id`, `name`, `is_template` (bool), `is_active` (bool), `uuid`, `timestamps`

**ShoppingItem**
- `id`, `shopping_list_id` (FK), `name`, `category` (enum: fruits_legumes, frais, epicerie, boissons, hygiene, maison, autre), `quantity` (nullable), `is_checked` (bool), `is_favorite` (bool), `added_by` (FK user), `uuid`, `timestamps`

**Todo**
- `id`, `title`, `description` (nullable), `is_personal` (bool), `assigned_to` (nullable FK user), `created_by` (FK user), `due_date` (nullable), `recurrence_type` (nullable enum: daily, weekly, monthly), `recurrence_day` (nullable), `is_done` (bool), `completed_at` (nullable), `uuid`, `timestamps`

**MealPlan**
- `id`, `date`, `meal_type` (enum: lunch, dinner), `description`, `cooked_by` (nullable FK user), `uuid`, `timestamps`

**MealIdea**
- `id`, `name`, `tags` (json: rapide, vege, comfort, etc.), `created_by` (FK user), `uuid`, `timestamps`

**Note**
- `id`, `title`, `content` (text), `is_pinned` (bool), `created_by` (FK user), `uuid`, `timestamps`

**SyncLog** (pour le mécanisme de sync)
- `id`, `syncable_type`, `syncable_uuid`, `action` (enum: created, updated, deleted), `payload` (json), `synced_at` (nullable), `timestamps`

### 2.2 Seeders
- `UserSeeder` : Créer les 2 comptes (Kevin + compagne)
- `ExpenseCategorySeeder` : Catégories par défaut (Courses, Restaurant, Loyer, Loisirs, Santé, Transport, Abonnements, Autre)
- `ShoppingItemCategorySeeder` : intégré dans l'enum directement

### 2.3 Factories
- Factory pour chaque modèle (utile pour les tests)

---

## Phase 3 : Authentification & Sécurité

### 3.1 Auth Sanctum
- Route `POST /api/login` : retourne un token Sanctum
- Pas de route `/register` (aucune inscription publique)
- Écran de login dans l'app avec email + mot de passe
- Stocker le token via le plugin Secure Storage de NativePHP

### 3.2 Écran de setup (premier lancement)
- Si aucun utilisateur n'existe en base → afficher un écran de création de compte au lieu du login
- L'utilisateur saisit son email + mot de passe
- Vérification que l'email fait partie de la whitelist `config/cocon.php` → `allowed_emails`
- Création du compte uniquement si l'email est autorisé
- Les lancements suivants → écran de login classique
- Changement de mot de passe possible depuis Paramètres (sans email, puisque l'app est locale)

### 3.3 Middleware de restriction
- `RestrictToHousehold` : vérifie que l'email de l'utilisateur est dans la whitelist (`config/cocon.php` → `allowed_emails`)
- Appliqué sur toutes les routes API

### 3.4 Rate limiting
- `throttle:60,1` sur les routes API
- `throttle:5,1` sur la route login

---

## Phase 4 : Architecture Sync (offline-first)

### 4.1 Principe
```
Téléphone (SQLite)  ←→  Laravel Cloud API (Postgres)  ←→  Autre téléphone (SQLite)
```

### 4.2 Mécanisme
- Chaque entité a un `uuid` (généré côté client)
- Chaque modification est enregistrée dans `SyncLog` localement
- Au sync : envoi des logs non synchronisés vers l'API
- L'API retourne les modifications de l'autre utilisateur depuis le dernier sync
- **Résolution de conflits** : last-write-wins basé sur `updated_at` (suffisant pour 2 users)

### 4.3 Routes API sync
- `POST /api/sync/push` : envoie les modifications locales
- `GET /api/sync/pull?since={timestamp}` : récupère les modifications distantes
- `GET /api/sync/full` : sync complète (premier lancement)

### 4.4 Sync automatique
- Au lancement de l'app
- À chaque modification (si connecté)
- Fallback : toutes les 5 minutes en arrière-plan
- Vérification de connectivité via le plugin Network de NativePHP

### 4.5 Stockage de fichiers
- **Local** : les fichiers (photos tickets, images Leboncoin) sont stockés dans le filesystem du device
- **Cloud** : disque S3 (stockage objet) configuré dans `config/filesystems.php` sur Laravel Cloud
- **Flow** : fichier ajouté sur le tel → stocké localement → à la sync, uploadé vers S3 via l'API → accessible depuis l'autre téléphone
- **Nettoyage** : les fichiers orphelins (liés à des entités supprimées) sont purgés périodiquement

### 4.6 Perte / vol de téléphone
- **Données sauvegardées** : toutes les données synchronisées sont sur Laravel Cloud (Postgres), le SQLite local n'est qu'un miroir
- **Fichiers sauvegardés** : tous les fichiers synchronisés sont sur S3, les fichiers locaux n'en sont qu'une copie
- **Récupération** : installer l'APK sur un nouveau téléphone → login → `sync/full` → toutes les données et fichiers sont restaurés
- **Sécurité** : l'app est protégée par Biometrics (empreinte/visage), le token est chiffré via Secure Storage
- **Révocation** : possibilité de révoquer le token de l'appareil perdu depuis l'autre téléphone ou l'API → l'ancien appareil ne peut plus se synchroniser ni accéder aux données

### 4.7 Backup personnel (optionnel)
- **Endpoint protégé** : `GET /api/backup/download` → génère un ZIP contenant un dump JSON de toute la DB + les fichiers S3
- **Commande artisan** : `php artisan cocon:backup` → même chose côté serveur, stocké dans un dossier configurable
- **Automatisation** : cron local (Raspberry Pi, PC, NAS) qui appelle l'endpoint de backup périodiquement (mensuel recommandé)
- **Restauration** : `POST /api/backup/restore` ou `php artisan cocon:restore {file}` pour réimporter un backup
- **Note** : la sync cloud est déjà un backup en soi. Ce mécanisme est un filet de sécurité supplémentaire pour stocker une copie chez soi

---

## Phase 5 : Pages Frontend (Vue 3 + Inertia)

### 5.1 Layout
- `resources/js/Layouts/AppLayout.vue` : layout principal avec bottom navigation bar
- Navigation bottom : Dashboard, Budget, Courses, Tâches, Plus (repas + notes)

### 5.2 Pages

**Auth**
- `resources/js/Pages/Auth/Login.vue`

**Dashboard**
- `resources/js/Pages/Dashboard.vue` : agrégation de tous les modules
  - Widget balance budget
  - Widget liste de courses active (nombre d'articles)
  - Widget tâches du jour
  - Widget repas du jour
  - Widget note épinglée
  - Bouton "Ajouter une dépense" rapide

**Budget**
- `resources/js/Pages/Budget/Index.vue` : liste des dépenses + balance
- `resources/js/Pages/Budget/Create.vue` : ajouter une dépense
- `resources/js/Pages/Budget/History.vue` : historique + graphiques par catégorie/mois
- `resources/js/Pages/Budget/Settle.vue` : règlement ("on est quittes")
- Composants : `ExpenseCard.vue`, `BalanceBanner.vue`, `CategoryPicker.vue`

**Courses**
- `resources/js/Pages/Shopping/Index.vue` : liste active, cochable
- `resources/js/Pages/Shopping/Templates.vue` : listes modèles
- `resources/js/Pages/Shopping/Favorites.vue` : articles favoris
- Composants : `ShoppingItemRow.vue`, `AisleCategoryGroup.vue`, `AddItemForm.vue`

**Tâches**
- `resources/js/Pages/Todos/Index.vue` : tâches du jour/semaine
- `resources/js/Pages/Todos/Create.vue` : créer une tâche
- Composants : `TodoItem.vue`, `RecurrencePicker.vue`

**Repas**
- `resources/js/Pages/Meals/Index.vue` : grille semaine midi/soir
- `resources/js/Pages/Meals/Ideas.vue` : banque d'idées de repas
- Composants : `MealSlot.vue`, `MealIdeaCard.vue`

**Notes**
- `resources/js/Pages/Notes/Index.vue` : liste des notes
- `resources/js/Pages/Notes/Show.vue` : détail/édition d'une note
- Composants : `NoteCard.vue`, `PinnedNote.vue`

**Paramètres**
- `resources/js/Pages/Settings/Index.vue` : profil, catégories, à propos, version + mise à jour

### 5.3 Composants partagés
- `resources/js/Components/BottomNav.vue` : barre de navigation
- `resources/js/Components/EmptyState.vue` : état vide avec illustration
- `resources/js/Components/SyncIndicator.vue` : indicateur de sync (en ligne/hors ligne)
- `resources/js/Components/QuickAction.vue` : bouton d'action rapide
- `resources/js/Components/UserAvatar.vue` : avatar utilisateur

---

## Phase 6 : Controllers & Routes

### 6.1 Controllers
```
app/Http/Controllers/
├── Auth/LoginController.php
├── DashboardController.php
├── ExpenseController.php
├── ShoppingListController.php
├── ShoppingItemController.php
├── TodoController.php
├── MealPlanController.php
├── MealIdeaController.php
├── NoteController.php
├── SyncController.php
└── AppVersionController.php
```

### 6.2 Routes Inertia (locales, dans l'app)
```php
// routes/web.php
Route::middleware('auth')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::resource('expenses', ExpenseController::class);
    Route::resource('shopping-lists', ShoppingListController::class);
    Route::resource('todos', TodoController::class);
    Route::resource('meal-plans', MealPlanController::class);
    Route::resource('meal-ideas', MealIdeaController::class);
    Route::resource('notes', NoteController::class);
    Route::post('expenses/settle', [ExpenseController::class, 'settle'])->name('expenses.settle');
    Route::post('shopping-lists/{list}/duplicate', [ShoppingListController::class, 'duplicate'])->name('shopping-lists.duplicate');
});
```

### 6.3 Routes API (sur Laravel Cloud, pour la sync)
```php
// routes/api.php
Route::post('login', [LoginController::class, 'login']);
Route::middleware(['auth:sanctum', 'household'])->group(function () {
    Route::post('sync/push', [SyncController::class, 'push']);
    Route::get('sync/pull', [SyncController::class, 'pull']);
    Route::get('sync/full', [SyncController::class, 'full']);
    Route::get('app/version', [AppVersionController::class, 'check']);
    Route::get('app/download', [AppVersionController::class, 'download']);
});
```

---

## Phase 7 : Logique métier clé

### 7.1 Calcul de balance (Budget)
- Service `App\Services\BalanceCalculator`
- Pour chaque dépense non réglée :
  - `split_type: equal` → chacun doit la moitié
  - `split_type: full_payer` → seul le payeur paie (dépense perso)
  - `split_type: full_other` → l'autre doit tout
  - `split_type: custom` → montant custom via `split_value`
- La balance = somme de ce que l'un doit à l'autre
- Le règlement archive toutes les dépenses non réglées

### 7.2 Récurrence (Tâches + Dépenses)
- Service `App\Services\RecurrenceGenerator`
- Commande artisan `app:generate-recurring` exécutée quotidiennement
- Crée automatiquement les tâches/dépenses récurrentes

### 7.3 Listes de courses intelligentes
- Tri automatique par rayon (catégorie)
- Duplication d'une liste modèle vers liste active
- Les articles cochés passent en bas de liste
- Articles favoris : marqués via toggle, accessibles rapidement

---

## Phase 8 : NativePHP Mobile & Plugins

### 8.1 Configuration Android
- `NATIVEPHP_APP_ID=com.cocon.app`
- Générer les credentials : `php artisan native:credentials android`
- Build : `php artisan native:package android`

### 8.2 Plugins utilisés
| Plugin | Usage |
|--------|-------|
| **Push Notifications** | Alertes quand l'autre ajoute une dépense, un article, etc. |
| **Biometrics** | Déverrouillage rapide de l'app (empreinte/visage) |
| **Secure Storage** | Stockage du token Sanctum |
| **Network** | Détecter la connectivité pour la sync |
| **Share** | Partager une liste de courses par message |
| **Camera** | Photo de ticket de caisse (futur) |

### 8.3 Auto-update
- `AppVersionController` : endpoint retournant `{ version, version_code, download_url }`
- Au lancement de l'app : comparer version locale vs serveur
- Si nouvelle version : modale "Mise à jour disponible" → téléchargement APK
- Commande artisan `app:release` pour uploader un nouvel APK et incrémenter la version

---

## Phase 9 : Design & UX

### 9.1 Thème visuel
- Palette chaude : tons beige/crème (#F5F0E8), accents terracotta (#C4704B), texte sombre (#2D2A26)
- Coins arrondis, ombres douces → ambiance "cocon"
- Dark mode : fond sombre (#1A1816), accents plus clairs
- Typographie : Inter (ou system font pour les perfs)

### 9.2 Navigation
- Bottom bar 5 onglets : Accueil, Budget, Courses, Tâches, Plus
- "Plus" ouvre un sous-menu : Repas, Notes, Paramètres
- Indicateur de sync discret en haut à droite (vert = connecté, gris = hors ligne)

---

## Phase 10 : Tests

### 10.1 Tests unitaires
- `BalanceCalculatorTest` : calculs de balance avec différents split types
- `RecurrenceGeneratorTest` : génération correcte des récurrences

### 10.2 Tests feature
- Auth : login, rejet sans compte, rejet email non autorisé
- Expenses : CRUD, calcul balance, règlement
- Shopping : CRUD listes et items, duplication, favoris
- Todos : CRUD, récurrence, assignation
- MealPlans : CRUD, grille semaine
- Notes : CRUD, épinglage
- Sync : push/pull, résolution de conflits

### 10.3 Tests browser (Pest v4)
- Navigation entre les modules
- Ajout d'une dépense et vérification de la balance
- Checklist de courses (cocher/décocher)
- Smoke test de toutes les pages

---

## Phase 11 : Déploiement

### 11.1 Laravel Cloud (API sync)
- Créer le projet sur Laravel Cloud
- Connecter le repo GitHub
- Configurer : Flex 1 vCPU, hibernation activée
- Serverless Postgres avec hibernation
- Variables d'environnement : APP_KEY, ALLOWED_EMAILS, FCM credentials
- Push-to-deploy activé

### 11.2 Build APK
```bash
php artisan native:credentials android
php artisan native:package android --keystore=... --keystore-password=... --key-alias=... --key-password=...
```
- Envoyer l'APK à la compagne
- Configurer l'auto-update pour les versions suivantes

---

## Plans détaillés par phase

| Phase | Fichier |
|-------|---------|
| Phase 3.2 - Écran de setup | [`SETUP_SCREEN.md`](SETUP_SCREEN.md) |
| Phase 5 - Budget | [`PHASE5_BUDGET.md`](PHASE5_BUDGET.md) |
| Phase 7 - Tâches | [`PHASE7_TODOS.md`](PHASE7_TODOS.md) |
| Phase 8 - Repas | [`PHASE8_MEALS.md`](PHASE8_MEALS.md) |
| Phase 9 - Notes | [`PHASE9_NOTES.md`](PHASE9_NOTES.md) |
| Phase 10 - Bookmarks | [`PHASE10_BOOKMARKS.md`](PHASE10_BOOKMARKS.md) |
| Phase 11 - Dashboard + Anniversaires | [`PHASE11_DASHBOARD.md`](PHASE11_DASHBOARD.md) |
| Phase 12 - Sync Offline-First | [`PHASE12_SYNC.md`](PHASE12_SYNC.md) |
| Phase 13 - Biométrie | [`PHASE13_BIOMETRIC.md`](PHASE13_BIOMETRIC.md) |
| Phase 14 - Auto-update APK | [`PHASE14_AUTOUPDATE.md`](PHASE14_AUTOUPDATE.md) |
| Réunion 18/02/2026 - Plan global | [`REUNION_PLAN.md`](REUNION_PLAN.md) |
| Phase 15 - Nettoyage | [`PHASE15_CLEANUP.md`](PHASE15_CLEANUP.md) |
| Phase 16 - Refonte Shopping | [`PHASE16_SHOPPING_REFONTE.md`](PHASE16_SHOPPING_REFONTE.md) |
| Phase 17 - Budget V2 | [`PHASE17_BUDGET_V2.md`](PHASE17_BUDGET_V2.md) |
| Phase 18 - Recettes V2 | [`PHASE18_RECIPES_V2.md`](PHASE18_RECIPES_V2.md) |
| Phase 19 - Notes + Todos Fusion | [`PHASE19_NOTES_FUSION.md`](PHASE19_NOTES_FUSION.md) |
| Phase 20 - Calendrier | [`PHASE20_CALENDRIER.md`](PHASE20_CALENDRIER.md) |
| Phase 21 - Dashboard V2 | [`PHASE21_DASHBOARD_V2.md`](PHASE21_DASHBOARD_V2.md) |
| Phase 22 - Bugs | [`PHASE22_BUGS.md`](PHASE22_BUGS.md) |
| Phase OPT-1 - Saisie vocale (optionnel) | [`PHASE_OPT1_VOICE_INPUT.md`](PHASE_OPT1_VOICE_INPUT.md) |

---

## Ordre d'implémentation

| Étape | Contenu | Dépendance |
|-------|---------|------------|
| 1 | Setup projet + NativePHP + Tailwind | - |
| 2 | Modèles, migrations, factories, seeders | 1 |
| 3 | Auth (login + middleware household + écran setup premier lancement) | 2 |
| 4 | Layout + navigation + dashboard vide | 3 |
| 5 | Module Budget (complet) | 4 |
| 6 | Module Courses (complet) | 4 |
| 7 | Module Tâches (complet) | 4 |
| 8 | Module Repas (complet) | 4 |
| 9 | Module Notes (complet) | 4 |
| 10 | Module Bookmarks (generiques) | 4 |
| 11 | Dashboard + Anniversaires (complet) | 5-10 |
| 12 | Mécanisme de sync + stockage fichiers S3 | 11 |
| 13 | Backup personnel (endpoint + commande artisan) | 12 |
| 14 | Push notifications | 12 |
| 15 | Auto-update | 11 |
| 16 | Tests | Continu |
| 17 | Déploiement Laravel Cloud + build APK | 16 |
