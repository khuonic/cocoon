# Phase 9 — Module Notes

## Contexte

Phase 9 du projet Cocoon. Module de notes partagées, style mémos / post-its. Le modèle `Note`, la factory, le controller (stub index) et la page Vue (empty state) existent déjà.

- **Texte brut** : titre + contenu (textarea), pas de markdown ni d'éditeur riche
- **CRUD via modal** : même pattern que Todos (Dialog create/edit sur l'index)
- **Épingler** : les notes épinglées apparaissent en haut (champ `is_pinned` existe déjà)
- **Couleurs** : choix de couleur par note (enum de 6 couleurs pastel, style Google Keep)

## Étape 1 : Migration — ajouter `color` à `notes`

```
php artisan make:migration add_color_to_notes_table --table=notes
```

- `color` : string, nullable, after `is_pinned` (null = couleur par défaut)

→ `php artisan migrate`

## Étape 2 : Enum NoteColor

**Créer `app/Enums/NoteColor.php`**

| Case | Valeur | Label FR | Classe Tailwind (bg) |
|------|--------|----------|----------------------|
| Default | `default` | Par défaut | `bg-card` |
| Yellow | `yellow` | Jaune | `bg-yellow-100` |
| Green | `green` | Vert | `bg-green-100` |
| Blue | `blue` | Bleu | `bg-blue-100` |
| Pink | `pink` | Rose | `bg-pink-100` |
| Purple | `purple` | Violet | `bg-purple-100` |

- Méthode `label(): string` → labels FR
- Méthode `bgClass(): string` → classe Tailwind pour le fond de la carte

## Étape 3 : Modèle + Factory

**Modifier `app/Models/Note.php`**
- Ajouter `color` dans `$fillable`
- Ajouter cast : `color` → `NoteColor::class`

**Modifier `database/factories/NoteFactory.php`**
- Ajouter `color` : `fake()->optional(0.5)->randomElement(NoteColor::cases())`

## Étape 4 : Form Requests

**Créer `app/Http/Requests/Note/StoreNoteRequest.php`**
- `title` : required, string, max:255
- `content` : required, string, max:10000
- `is_pinned` : required, boolean
- `color` : nullable, string, Rule::in(NoteColor values)
- Messages FR

**Créer `app/Http/Requests/Note/UpdateNoteRequest.php`**
- Mêmes règles que store

## Étape 5 : Controller

**Modifier `app/Http/Controllers/NoteController.php`**

- `index()` : passe `notes` (épinglées en premier, puis par updated_at desc), avec `creator`
- `store(StoreNoteRequest)` : crée avec `uuid` + `created_by`, redirect notes.index
- `update(UpdateNoteRequest, Note)` : met à jour, redirect notes.index
- `togglePin(Note)` : inverse `is_pinned`, redirect notes.index
- `destroy(Note)` : supprime, redirect notes.index

## Étape 6 : Routes

**Modifier `routes/web.php`**

```php
Route::patch('notes/{note}/toggle-pin', [NoteController::class, 'togglePin'])->name('notes.toggle-pin');
Route::resource('notes', NoteController::class)->only(['index', 'store', 'update', 'destroy']);
```

→ `php artisan wayfinder:generate`

## Étape 7 : Types TypeScript

**Créer `resources/js/types/note.ts`**

```ts
export type NoteColor = 'default' | 'yellow' | 'green' | 'blue' | 'pink' | 'purple';

export type Note = {
    id: number;
    title: string;
    content: string;
    is_pinned: boolean;
    color: NoteColor | null;
    created_by: number;
    uuid: string;
    created_at: string;
    updated_at: string;
    creator?: { id: number; name: string };
};
```

**Modifier `resources/js/types/index.ts`**
- Ajouter `export * from './note'`

## Étape 8 : Composants Vue (3 fichiers)

**`resources/js/components/notes/NoteCard.vue`**
- Props : `note: Note`
- Événements : `@edit`, `@toggle-pin`
- Carte avec fond coloré via `note.color` (bgClass mapping côté TS)
- Affiche : titre (font-semibold), contenu tronqué (line-clamp-3), icône pin si épinglé
- Click sur la carte → emit `edit`
- Bouton pin (icône Pin) + bouton supprimer (icône Trash2) en bas de carte
- Affiche le créateur (petit texte muted) + date relative (updated_at)

**`resources/js/components/notes/NoteFormDialog.vue`**
- Pattern identique à TodoFormDialog : Dialog + useForm + watch open/note
- Champs : title (Input), content (Textarea, 6 lignes min), is_pinned (Switch), color (6 pastilles de couleur cliquables)
- Mode create / edit selon la prop `note`

**`resources/js/components/notes/ColorPicker.vue`**
- Props : `modelValue: NoteColor | null`
- 6 cercles de couleur cliquables (w-7 h-7 rounded-full + ring si sélectionné)
- Mapping couleur → classe Tailwind bg

## Étape 9 : Page Vue

**Réécrire `resources/js/pages/Notes/Index.vue`**
- Props : `notes: Note[]`
- Header : titre "Notes" + bouton "+" pour ouvrir le modal create
- Si aucune note : EmptyState avec icône StickyNote + bouton "Ajouter une note"
- Sinon : grille 2 colonnes (`grid grid-cols-2 gap-3 p-4`) de NoteCard
  - Notes épinglées en premier (le tri est fait côté serveur)
- NoteFormDialog pour create/edit
- Toggle pin via `router.patch(togglePin.url(note.id))`

## Étape 10 : Tests Pest (~12 tests)

**Créer `tests/Feature/Note/NoteTest.php`**

1. Guests redirigés vers login (GET notes.index)
2. Index retourne les notes de l'app
3. Notes épinglées apparaissent en premier
4. Store crée une note avec uuid et created_by
5. Store valide title requis
6. Store valide content requis
7. Store valide color parmi les valeurs de l'enum
8. Update modifie une note
9. Toggle pin inverse is_pinned
10. Destroy supprime une note
11. Store crée une note avec couleur
12. Store crée une note sans couleur (null par défaut)

## Étape 11 : Finalisation

- `vendor/bin/pint --dirty --format agent`
- `php artisan wayfinder:generate`
- `npm run build`
- `php artisan test --compact`
- Mettre à jour `COCON_PLAN.md`, `.ai/guidelines/contexte.md`, `MEMORY.md`

## Fichiers créés/modifiés

| Action | Fichier |
|--------|---------|
| Créer | 1 migration (add_color_to_notes) |
| Créer | `app/Enums/NoteColor.php` |
| Modifier | `app/Models/Note.php` (fillable + cast) |
| Modifier | `database/factories/NoteFactory.php` (color) |
| Créer | `app/Http/Requests/Note/StoreNoteRequest.php` |
| Créer | `app/Http/Requests/Note/UpdateNoteRequest.php` |
| Modifier | `app/Http/Controllers/NoteController.php` (CRUD + togglePin) |
| Modifier | `routes/web.php` (routes notes) |
| Créer | `resources/js/types/note.ts` |
| Modifier | `resources/js/types/index.ts` |
| Créer | `resources/js/components/notes/NoteCard.vue` |
| Créer | `resources/js/components/notes/NoteFormDialog.vue` |
| Créer | `resources/js/components/notes/ColorPicker.vue` |
| Modifier | `resources/js/pages/Notes/Index.vue` |
| Créer | `tests/Feature/Note/NoteTest.php` |
| Modifier | `COCON_PLAN.md`, `.ai/guidelines/contexte.md` |

## Vérification

1. `php artisan migrate` → sans erreur
2. `php artisan test --compact --filter=Note` → tous les tests passent
3. `php artisan test --compact` → aucune régression (131+ tests)
4. `npm run build` → sans erreur
