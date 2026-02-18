# Phase 21 — Dashboard V2

## Objectif
Mettre à jour le Dashboard pour afficher les événements et anniversaires du jour depuis le nouveau module Calendrier.

---

## 21.1 Backend

### `app/Http/Controllers/DashboardController.php`

**Retirer :**
- `todosHighlighted` (show_on_dashboard supprimé en Phase 19)
- `bookmarksHighlighted` (Bookmarks supprimés en Phase 15)

**Ajouter :**
- Récupérer les événements du jour : `CalendarEvent::whereDate('starts_at', today())->orderBy('starts_at')->get()`
- Récupérer les anniversaires du jour (logique existante `Birthday::whereMonth/whereDay`)
- Fusionner et trier par heure
- Passer à la vue :
  - `todayItems` : collection fusionnée (max 5 affichés)
  - `todayItemsCount` : total réel (pour afficher "+N si > 5)

---

## 21.2 Frontend

### `resources/js/pages/Dashboard.vue`

**Retirer :**
- Widget "Todos épinglés"
- Widget "Bookmarks épinglés"

**Modifier le widget "Anniversaires du jour" → "Aujourd'hui" :**
- Afficher les événements du jour ET les anniversaires
- Chaque item :
  - Événement : pastille colorée (couleur de catégorie) + titre + heure si non all_day
  - Anniversaire : pastille rose 🎂 + "Anniversaire de {nom}" + âge
- Si `todayItemsCount > 5` : bouton "Voir tout →" → `/calendar`
- Si aucun item : message "Rien de prévu aujourd'hui 🎉"

---

## 21.3 Logo Login

### `resources/js/pages/auth/Login.vue`
- Ajouter au-dessus du formulaire de connexion :
  - Si un fichier `resources/js/assets/logo.svg` existe : l'afficher (hauteur ~64px)
  - Sinon : nom "Cocoon" en grand (`text-4xl font-bold text-primary`) avec une icône décorative
- Centrer horizontalement

---

## 21.4 Tests

### `tests/Feature/Dashboard/DashboardTest.php`

**Retirer :**
- Tests sur les todos épinglés
- Tests sur les bookmarks épinglés

**Ajouter :**
- Test : un événement du jour apparaît dans `todayItems`
- Test : un anniversaire du jour apparaît dans `todayItems`
- Test : si > 5 items, `todayItemsCount` est correct
- Test : un événement de demain n'apparaît pas dans `todayItems`
