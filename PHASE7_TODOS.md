# Phase 7 — Module Tâches (Todos)

## Contexte

Phase 7 du projet Cocoon. CRUD complet avec modal de création/édition, groupement partagées/perso/terminées, sans récurrence.

## Fonctionnalités

- CRUD tâches (store, update, toggle, destroy) — tout en modal sur la page index
- Groupement : tâches partagées, tâches perso, tâches terminées (collapsible)
- Assignation à un utilisateur
- Date d'échéance optionnelle
- Toggle done/undone avec completed_at

## Fichiers créés/modifiés

| Action | Fichier |
|--------|---------|
| Créer | `app/Http/Requests/Todo/StoreTodoRequest.php` |
| Créer | `app/Http/Requests/Todo/UpdateTodoRequest.php` |
| Modifier | `routes/web.php` |
| Modifier | `app/Http/Controllers/TodoController.php` |
| Créer | `resources/js/types/todo.ts` |
| Modifier | `resources/js/types/index.ts` |
| Créer | `resources/js/components/todos/TodoItem.vue` |
| Créer | `resources/js/components/todos/TodoFormDialog.vue` |
| Modifier | `resources/js/pages/Todos/Index.vue` |
| Créer | `tests/Feature/Todo/TodoTest.php` |
