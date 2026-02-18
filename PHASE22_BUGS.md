# Phase 22 — Corrections de bugs

## Objectif
Corriger les deux bugs signalés lors de la réunion : les blagues ne s'affichent pas et le mot mignon ne s'affiche pas sur le Dashboard.

---

## 22.1 Blagues qui ne s'affichent pas

### Investigation
1. Vérifier que le seeder `JokeSeeder` a bien été lancé et que la table `jokes` contient des données
2. Vérifier `DashboardController` : comment la blague est sélectionnée et passée à la vue
3. Vérifier `Dashboard.vue` : la prop `joke` est-elle bien consommée et affichée ?

### Corrections potentielles
- Si la table est vide : `php artisan db:seed --class=JokeSeeder`
- Si la logique de rotation quotidienne est cassée (ex: `Joke::find(now()->dayOfYear % count)`) : vérifier les edge cases (table vide, day > count)
- Si la prop Inertia est mal nommée ou nulle : corriger le binding dans `DashboardController` et le template

---

## 22.2 Mot mignon qui ne s'affiche pas

### Investigation
1. Vérifier `DashboardController` : récupération du `SweetMessage` de l'utilisateur connecté
2. Vérifier `Dashboard.vue` : la prop `sweetMessage` est-elle affichée conditionnellement ?
3. Vérifier `SweetMessageController::store()` : le `updateOrCreate` fonctionne-t-il ?

### Corrections potentielles
- Si null car aucun message encore créé : afficher un placeholder "Écrivez un mot doux..."
- Si la prop arrive bien mais le composant ne l'affiche pas : corriger le template Vue
- Si `updateOrCreate` utilise une mauvaise clé : corriger la logique

---

## Notes
Ces bugs seront investigués une fois les phases précédentes terminées, car certaines modifications du Dashboard (Phase 21) pourraient également impacter ces éléments.