# Phase 19 — Fusion Notes + Todos

## Objectif
Créer un module "Notes" unifié avec 2 onglets : **Notes** (pages dédiées plein écran, détection de liens) et **Todos** (TodoLists contenant des tâches simples, style Shopping).

---

## 19.1 Base de données

### Migration : supprimer l'ancienne table `todos`
```php
Schema::dropIfExists('todos');
```

### Migration : créer `todo_lists`
```php
Schema::create('todo_lists', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique();
    $table->string('title');
    $table->boolean('is_personal')->default(false);
    $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
    $table->timestamps();
});
```

### Migration : créer nouvelle table `todos`
```php
Schema::create('todos', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique();
    $table->foreignId('todo_list_id')->constrained()->cascadeOnDelete();
    $table->string('title');
    $table->boolean('is_done')->default(false);
    $table->timestamp('completed_at')->nullable();
    $table->timestamps();
});
```

### Migration : modifier `notes`
- Supprimer colonne `show_on_dashboard` (plus utilisée)

---

## 19.2 Modèles

### `app/Models/TodoList.php` (nouveau)
- `$fillable` : uuid, title, is_personal, user_id
- `$casts` : is_personal → boolean
- Relation : `hasMany(Todo::class)`
- Relation : `belongsTo(User::class)`
- Trait `Syncable`
- Boot : générer uuid automatiquement

### `app/Models/Todo.php` (refactorisé complètement)
- `$fillable` : uuid, todo_list_id, title, is_done, completed_at
- `$casts` : is_done → boolean, completed_at → datetime
- Relation : `belongsTo(TodoList::class)`
- Trait `Syncable`
- Scopes : `pending()`, `done()`

### `app/Models/Note.php` (modifié)
- Retirer `show_on_dashboard` du `$fillable` et des casts

---

## 19.3 Form Requests

### `app/Http/Requests/TodoList/StoreTodoListRequest.php` (nouveau)
- `title` : required|string|max:255
- `is_personal` : boolean

### `app/Http/Requests/TodoList/UpdateTodoListRequest.php` (nouveau)
- `title` : required|string|max:255

### `app/Http/Requests/Todo/StoreTodoRequest.php` (refactorisé)
- `title` : required|string|max:255

---

## 19.4 Controllers

### `app/Http/Controllers/TodoListController.php` (nouveau)
- `show(TodoList $todoList)` : page dédiée avec todos (pending en haut, done en bas)
- `store(StoreTodoListRequest $request)` : créer une liste
- `update(UpdateTodoListRequest $request, TodoList $todoList)` : modifier le titre
- `destroy(TodoList $todoList)` : supprimer la liste et ses todos (cascade)

### `app/Http/Controllers/TodoController.php` (refactorisé)
- `store(StoreTodoRequest $request, TodoList $todoList)` : ajouter un todo à une liste
- `toggle(Todo $todo)` : toggle is_done + completed_at
- `update(Request $request, Todo $todo)` : modifier le titre
- `destroy(Todo $todo)` : supprimer

### `app/Http/Controllers/NoteController.php` (modifié)
- Ajouter `show(Note $note)` : retourne la page dédiée de la note (Inertia render)
- Adapter `update()` si nécessaire (sans show_on_dashboard)

---

## 19.5 Routes

### `routes/web.php` — remplacer les routes todos et notes actuelles

```php
// Notes fusionnées — onglet Notes
Route::get('notes', [NoteController::class, 'index'])->name('notes.index');
Route::post('notes', [NoteController::class, 'store'])->name('notes.store');
Route::get('notes/{note}', [NoteController::class, 'show'])->name('notes.show');
Route::patch('notes/{note}', [NoteController::class, 'update'])->name('notes.update');
Route::patch('notes/{note}/toggle-pin', [NoteController::class, 'togglePin'])->name('notes.toggle-pin');
Route::delete('notes/{note}', [NoteController::class, 'destroy'])->name('notes.destroy');

// Notes fusionnées — onglet Todos (TodoLists)
Route::get('todo-lists/{todo_list}', [TodoListController::class, 'show'])->name('todo-lists.show');
Route::post('todo-lists', [TodoListController::class, 'store'])->name('todo-lists.store');
Route::patch('todo-lists/{todo_list}', [TodoListController::class, 'update'])->name('todo-lists.update');
Route::delete('todo-lists/{todo_list}', [TodoListController::class, 'destroy'])->name('todo-lists.destroy');

// Todos dans une liste
Route::post('todo-lists/{todo_list}/todos', [TodoController::class, 'store'])->name('todos.store');
Route::patch('todos/{todo}/toggle', [TodoController::class, 'toggle'])->name('todos.toggle');
Route::patch('todos/{todo}', [TodoController::class, 'update'])->name('todos.update');
Route::delete('todos/{todo}', [TodoController::class, 'destroy'])->name('todos.destroy');
```

---

## 19.6 Frontend

### `resources/js/pages/Notes/Index.vue` (refactorisé)

**Header :** titre "Notes"

**Onglets (tabs) :** Notes | Todos
- Persister l'onglet actif via `?tab=notes|todos` dans l'URL

**Onglet "Notes" :**
- Grille 2 colonnes des notes (comme actuellement)
- Click sur une note → `/notes/{id}` (page dédiée, plus de modal d'édition inline)
- FAB → modal création note (titre + couleur) → redirige vers `/notes/{id}` après création
- Épinglées affichées en premier
- Supprimer depuis un menu ⋮ sur la card (Épingler/Désépingler | Supprimer)

**Onglet "Todos" :**
- Liste des TodoLists (cards simples)
- Card : titre, badge "Perso" si is_personal, nombre de todos restants
- Menu ⋮ sur la card : "Modifier le titre" | "Supprimer"
- Click sur la card → `/todo-lists/{id}`
- FAB → modal création TodoList (titre + toggle Partagée/Personnelle)

### `resources/js/pages/Notes/Show.vue` (nouvelle page dédiée)
- **Fond coloré** selon `note.color` (comme les cards)
- **Header :** BackButton (← retour vers `/notes`) + actions (épingler, supprimer)
- **Titre :** grand input texte en haut, editable inline
- **Contenu :** grand `textarea` qui prend tout l'espace disponible, auto-resize
- **Auto-save :** debounce 1 seconde → PATCH `/notes/{note}`
- **Détection de liens :** afficher le contenu en mode lecture avec les URLs rendues cliquables (via `Browser.open()`) ; basculer entre "lecture" et "édition" au tap
- **Couleur de fond :** basée sur `note.color` (enum NoteColor)

### `resources/js/pages/TodoLists/Show.vue` (nouvelle page)
- Style similaire à `Shopping/Show.vue`
- **Header :** BackButton + titre de la liste + badge "Perso" si is_personal
- **Formulaire sticky en bas :** input texte (ajout rapide) + bouton "+"
- **Liste des todos :**
  - Cards simples (titre, case à cocher à gauche)
  - Click sur la card → toggle done (titre barré)
  - Pas de catégories
- **Section "Terminés" :** en bas, collapsible, todos avec `is_done = true`
- **Pas de FAB** (le formulaire sticky suffit)

### `resources/js/components/BottomNav.vue`
- Entrée "Tâches" (CheckSquare, `/todos`) → "Notes" (StickyNote, `/notes`)

### `resources/js/pages/More.vue`
- Retirer l'entrée "Notes" (maintenant dans la BottomNav)
- Ajouter l'entrée "Courses" (ShoppingCart, `/shopping-lists`) en premier
- Retirer l'entrée "Anniversaires" (maintenant dans Calendrier — Phase 20)
- Résultat final : Courses | Repas | Paramètres

---

## 19.7 Sync

### `app/Services/SyncService.php`
- Remplacer `Todo::class` par `TodoList::class` dans MODEL_MAP
- `Todo::class` reste dans MODEL_MAP mais avec le nouveau payload (title, is_done, completed_at, todo_list_uuid)
- Retirer `Bookmark::class` (Phase 15)
- Retirer `MealIdea::class` (Phase 15)

---

## 19.8 Tests

### Supprimer
- `tests/Feature/Todo/TodoTest.php` (ancienne structure)

### Créer
- `tests/Feature/TodoList/TodoListTest.php`
  - store (partagée + personnelle), update titre, destroy, show page
- `tests/Feature/Todo/TodoTest.php` (recréé)
  - store dans une liste, toggle, update titre, destroy

### Mettre à jour
- `tests/Feature/Note/NoteTest.php`
  - Ajouter test : `show` retourne la page dédiée
  - Retirer tests relatifs à `show_on_dashboard`
- `tests/Feature/Dashboard/DashboardTest.php`
  - Retirer assertions sur todos épinglés et bookmarks épinglés
- `tests/Feature/Sync/SyncableTest.php`
  - Mettre à jour pour TodoList + Todo nouvelle structure

---

## Notes architecturales

- Les notes sont éditées en pages dédiées (plus de modal d'édition) mais créées via modal légère
- La détection de liens dans le contenu d'une note est côté Vue (regex URL) → rendu conditionnel lecture/édition
- Les TodoLists partagées sont visibles par les 2 utilisateurs, les personnelles uniquement par leur créateur