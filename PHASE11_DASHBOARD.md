# Phase 11 — Dashboard + Anniversaires

## Contexte

Le dashboard (`/`) est actuellement un empty state. On le transforme en page d'accueil personnalisee avec des widgets agreges. On ajoute aussi un module Anniversaires (CRUD dans "Plus") et un systeme de blague du jour.

## Widgets du dashboard (dans l'ordre)

1. **Mot doux** — chacun ecrit un message pour l'autre, l'autre le voit
2. **Anniversaires du jour** — anniversaires qui tombent aujourd'hui (nom + age)
3. **Blague du jour** — une blague differente chaque jour (seeder local)
4. **Todos epinglees** — todos avec `show_on_dashboard` non terminees
5. **Bookmarks epingles** — bookmarks avec `show_on_dashboard`

## Etape 1 : Migrations

### 1a. Creer la table `sweet_messages`

```
php artisan make:migration create_sweet_messages_table
```

- `id`
- `user_id` : foreignId, constrained, cascadeOnDelete (l'auteur du message)
- `content` : string(500)
- `timestamps`

Un seul message actif par user (on update, pas d'historique).

### 1b. Creer la table `jokes`

```
php artisan make:migration create_jokes_table
```

- `id`
- `content` : text
- `timestamps`

### 1c. Creer la table `birthdays`

```
php artisan make:migration create_birthdays_table
```

- `id`
- `name` : string
- `date` : date (date complete avec annee, pour calculer l'age)
- `added_by` : foreignId, constrained('users'), cascadeOnDelete
- `uuid` : uuid, unique
- `timestamps`

### 1d. Ajouter `show_on_dashboard` sur `todos`

```
php artisan make:migration add_show_on_dashboard_to_todos_table --table=todos
```

- `show_on_dashboard` : boolean, default false

### 1e. Ajouter `show_on_dashboard` sur `bookmarks`

```
php artisan make:migration add_show_on_dashboard_to_bookmarks_table --table=bookmarks
```

- `show_on_dashboard` : boolean, default false

## Etape 2 : Modeles + Factories

### SweetMessage

- `fillable` : user_id, content
- Relation : `user()` -> BelongsTo User
- Factory : content = fake()->sentence()

### Joke

- `fillable` : content
- Pas de factory (seeder uniquement)

### Birthday

- `fillable` : name, date, added_by, uuid
- Casts : `date` -> `date`
- Relation : `addedBy()` -> BelongsTo User
- Factory : name = fake()->name(), date = fake()->date(), added_by = User::factory()
- Methode ou accessor : `age()` -> calcul de l'age a partir de la date

### Modifier Todo

- Ajouter `show_on_dashboard` aux fillable
- Cast : `show_on_dashboard` -> boolean
- Mettre a jour la factory : `show_on_dashboard` -> false

### Modifier Bookmark

- Ajouter `show_on_dashboard` aux fillable
- Cast : `show_on_dashboard` -> boolean
- Mettre a jour la factory : `show_on_dashboard` -> false

## Etape 3 : Seeder JokeSeeder

Creer `database/seeders/JokeSeeder.php` avec ~50 blagues FR (on pourra en ajouter plus tard).
Format simple : tableau de strings, insert en batch.

Selection de la blague du jour : `Joke::find((now()->dayOfYear % Joke::count()) + 1)` ou equivalent.

## Etape 4 : Form Requests

### StoreSweetMessageRequest

- `content` : required, string, max:500
- Messages FR

### StoreBirthdayRequest / UpdateBirthdayRequest

- `name` : required, string, max:255
- `date` : required, date, before_or_equal:today
- Messages FR

### Modifier StoreTodoRequest / UpdateTodoRequest

- Ajouter `show_on_dashboard` : required, boolean

### Modifier StoreBookmarkRequest / UpdateBookmarkRequest

- Ajouter `show_on_dashboard` : required, boolean

## Etape 5 : Controllers

### DashboardController (modifier)

Passer au frontend :
- `sweetMessage` : le message de l'autre user (pas le sien)
- `mySweetMessage` : son propre message (pour le formulaire d'edition)
- `todayBirthdays` : anniversaires du jour (filtre mois+jour = aujourd'hui), avec age calcule
- `joke` : la blague du jour
- `pinnedTodos` : todos avec show_on_dashboard=true et non terminees
- `pinnedBookmarks` : bookmarks avec show_on_dashboard=true

### SweetMessageController (creer)

- `store(StoreSweetMessageRequest)` : cree ou met a jour le message du user connecte (updateOrCreate sur user_id)
- Redirect dashboard

### BirthdayController (creer)

- `index()` : liste tous les anniversaires, tries par date (mois/jour)
- `store(StoreBirthdayRequest)` : cree avec uuid + added_by
- `update(UpdateBirthdayRequest, Birthday)` : met a jour
- `destroy(Birthday)` : supprime

### Modifier TodoController

- Ajouter `show_on_dashboard` dans store/update

### Modifier BookmarkController

- Ajouter `show_on_dashboard` dans store/update (deja gere via validated())

## Etape 6 : Routes

```php
// Dashboard (existe deja)
Route::get('/', DashboardController::class)->name('dashboard');

// Sweet messages
Route::post('sweet-messages', [SweetMessageController::class, 'store'])->name('sweet-messages.store');

// Birthdays
Route::resource('birthdays', BirthdayController::class)->only(['index', 'store', 'update', 'destroy']);
```

## Etape 7 : Types TypeScript

### sweet-message.ts

```ts
export type SweetMessage = {
    id: number;
    content: string;
    user_id: number;
    created_at: string;
    updated_at: string;
};
```

### birthday.ts

```ts
export type Birthday = {
    id: number;
    name: string;
    date: string;
    age: number;
    added_by: number;
    uuid: string;
    created_at: string;
    updated_at: string;
};

export type TodayBirthday = {
    id: number;
    name: string;
    age: number;
};
```

### Modifier types/index.ts

Ajouter exports.

## Etape 8 : Composants Vue

### Dashboard widgets (resources/js/components/dashboard/)

- `SweetMessageWidget.vue` — affiche le message de l'autre + formulaire inline pour ecrire/modifier le sien
- `TodayBirthdaysWidget.vue` — liste des anniversaires du jour (nom + age + icone gateau)
- `JokeWidget.vue` — la blague du jour (carte avec icone)
- `PinnedTodosWidget.vue` — liste des todos epinglees (titre, checkbox toggle)
- `PinnedBookmarksWidget.vue` — liste des bookmarks epingles (titre, lien externe)

### Birthdays (resources/js/components/birthdays/)

- `BirthdayCard.vue` — affiche nom, date formatee, age
- `BirthdayFormDialog.vue` — modal create/edit (nom, date)

## Etape 9 : Pages Vue

### Reecrire Dashboard.vue

- Props : sweetMessage, mySweetMessage, todayBirthdays, joke, pinnedTodos, pinnedBookmarks
- Layout : liste verticale de widgets (gap-4 p-4)
- Chaque widget n'apparait que s'il a du contenu (sauf mot doux qui affiche toujours le formulaire)

### Creer Birthdays/Index.vue

- CRUD via modal (meme pattern que Notes)
- Liste verticale triee par mois/jour
- Header "Anniversaires" + bouton "+"
- Chaque carte : nom, date formatee (ex: "15 mars 1995"), age

### Modifier More.vue

- Ajouter entree "Anniversaires" dans le menu (icone Cake)

## Etape 10 : Modifier modals existantes

### TodoFormDialog (resources/js/components/todos/)

- Ajouter un Switch "Afficher sur l'accueil" pour `show_on_dashboard`

### BookmarkFormDialog (resources/js/components/bookmarks/)

- Ajouter un Switch "Afficher sur l'accueil" pour `show_on_dashboard`

## Etape 11 : Tests Pest

### tests/Feature/Dashboard/DashboardTest.php (~6 tests)

1. Guests rediriges vers login
2. Dashboard affiche le mot doux de l'autre user
3. Dashboard affiche les anniversaires du jour
4. Dashboard affiche la blague du jour
5. Dashboard affiche les todos epinglees
6. Dashboard affiche les bookmarks epingles

### tests/Feature/SweetMessage/SweetMessageTest.php (~3 tests)

1. Store cree un message
2. Store met a jour le message existant (updateOrCreate)
3. Store valide content requis

### tests/Feature/Birthday/BirthdayTest.php (~8 tests)

1. Guests rediriges vers login
2. Index retourne les anniversaires
3. Store cree un anniversaire avec uuid et added_by
4. Store valide name requis
5. Store valide date requise
6. Store valide date pas dans le futur
7. Update modifie un anniversaire
8. Destroy supprime un anniversaire

### Modifier TodoTest + BookmarkTest

- Ajouter test pour show_on_dashboard dans store/update

## Etape 12 : Finalisation

- `vendor/bin/pint --dirty --format agent`
- `php artisan wayfinder:generate`
- `npm run build`
- `php artisan test --compact`
- Mettre a jour `.ai/guidelines/contexte.md`, `MEMORY.md`

## Fichiers crees/modifies

| Action | Fichier |
|--------|---------|
| Creer | 5 migrations |
| Creer | `app/Models/SweetMessage.php` |
| Creer | `app/Models/Joke.php` |
| Creer | `app/Models/Birthday.php` |
| Modifier | `app/Models/Todo.php` |
| Modifier | `app/Models/Bookmark.php` |
| Creer | `database/factories/SweetMessageFactory.php` |
| Creer | `database/factories/BirthdayFactory.php` |
| Creer | `database/seeders/JokeSeeder.php` |
| Creer | `app/Http/Requests/SweetMessage/StoreSweetMessageRequest.php` |
| Creer | `app/Http/Requests/Birthday/StoreBirthdayRequest.php` |
| Creer | `app/Http/Requests/Birthday/UpdateBirthdayRequest.php` |
| Modifier | `app/Http/Requests/Todo/StoreTodoRequest.php` |
| Modifier | `app/Http/Requests/Todo/UpdateTodoRequest.php` |
| Modifier | `app/Http/Requests/Bookmark/StoreBookmarkRequest.php` |
| Modifier | `app/Http/Requests/Bookmark/UpdateBookmarkRequest.php` |
| Modifier | `app/Http/Controllers/DashboardController.php` |
| Creer | `app/Http/Controllers/SweetMessageController.php` |
| Creer | `app/Http/Controllers/BirthdayController.php` |
| Modifier | `routes/web.php` |
| Creer | `resources/js/types/sweet-message.ts` |
| Creer | `resources/js/types/birthday.ts` |
| Modifier | `resources/js/types/index.ts` |
| Creer | 5 composants dashboard widgets |
| Creer | 2 composants birthdays (card + form dialog) |
| Modifier | `resources/js/pages/Dashboard.vue` |
| Creer | `resources/js/pages/Birthdays/Index.vue` |
| Modifier | `resources/js/pages/More.vue` |
| Modifier | `resources/js/components/todos/TodoFormDialog.vue` |
| Modifier | `resources/js/components/bookmarks/BookmarkFormDialog.vue` |
| Creer | 3 fichiers tests |
| Modifier | tests existants (todo, bookmark) |

## Verification

1. `php artisan migrate` -> sans erreur
2. `php artisan db:seed --class=JokeSeeder` -> sans erreur
3. `php artisan test --compact --filter=Dashboard` -> tous les tests passent
4. `php artisan test --compact --filter=SweetMessage` -> tous les tests passent
5. `php artisan test --compact --filter=Birthday` -> tous les tests passent
6. `php artisan test --compact` -> aucune regression
7. `npm run build` -> sans erreur
