# Plan Réunion Lola & Kevin — Évolutions Cocoon
> Réunion du 18/02/2026 — Plans détaillés dans les fichiers PHASE*.md référencés ci-dessous.

## Résumé des changements

### Suppressions
- **Bookmarks** : suppression complète (modèle, routes, UI, tests, sync)
- **MealIdeas** : suppression complète (modèle, routes, UI, tests, sync)
- **Todos (anciens)** : table droppée, remplacée par `todo_lists` + `todos` nouvelle structure
- **Page Anniversaires (standalone)** : intégrée dans le Calendrier

### Nouveautés
- **Module Calendrier** : vue mensuelle style Google Calendar, événements catégorisés/colorés/rappels, anniversaires intégrés
- **Plugin `cocoon/local-notifications`** : package NativePHP local (`packages/cocoon/local-notifications/`) pour rappels Android sans serveur
- **Module Notes fusionné** : Notes + Todos dans une seule entrée de menu (2 onglets)
- **Notes en pages dédiées** : édition plein écran avec détection de liens cliquables

### Refontes
- **Shopping** : cards avec menu ⋮, collapse catégories, supprimer quantité/favoris, mémoriser dernière liste
- **Budget** : catégories renommées, historique mensuel par défaut avec filtres, lien historique depuis solde
- **Recettes** : suppression idées, ajout image (NativePHP Camera), choix URL/saisie manuelle, index page
- **Dashboard** : widget "Aujourd'hui" avec événements + anniversaires du jour

### Navigation

| | Actuel | Nouveau |
|---|---|---|
| **BottomNav** | Accueil \| Budget \| Courses \| Tâches \| Plus | Accueil \| Calendrier \| Budget \| Notes \| Plus |
| **Plus** | Repas, Notes, Bookmarks, Anniversaires, Paramètres | Courses, Repas, Paramètres |

---

## Phases d'implémentation

| Phase | Fichier | Description | Dépendances |
|-------|---------|-------------|-------------|
| **15** | [PHASE15_CLEANUP.md](PHASE15_CLEANUP.md) | Nettoyage Bookmarks + MealIdeas, FAB, logo login | — |
| **16** | [PHASE16_SHOPPING_REFONTE.md](PHASE16_SHOPPING_REFONTE.md) | Cards + menu ⋮, collapse catégories, mémoriser liste | 15 |
| **17** | [PHASE17_BUDGET_V2.md](PHASE17_BUDGET_V2.md) | Catégories renommées, historique mensuel, lien historique | 15 |
| **18** | [PHASE18_RECIPES_V2.md](PHASE18_RECIPES_V2.md) | Index recettes, image Camera, URL/saisie manuelle | 15 |
| **19** | [PHASE19_NOTES_FUSION.md](PHASE19_NOTES_FUSION.md) | Fusion Todos + Notes, TodoList, Notes en pages | 15 |
| **20** | [PHASE20_CALENDRIER.md](PHASE20_CALENDRIER.md) | Module Calendrier complet + plugin local-notifications | 15 |
| **21** | [PHASE21_DASHBOARD_V2.md](PHASE21_DASHBOARD_V2.md) | Dashboard avec événements du jour | 19, 20 |
| **22** | [PHASE22_BUGS.md](PHASE22_BUGS.md) | Blagues + mot mignon qui ne s'affichent pas | — |
| **OPT-1** | [PHASE_OPT1_VOICE_INPUT.md](PHASE_OPT1_VOICE_INPUT.md) | Saisie vocale dans AddItemForm (Web Speech API) | 16 |

---

## Checklist globale (Ne pas oublier)

- [ ] Modifier tous les tests impactés par chaque phase
- [ ] Mettre à jour `.ai/guidelines/contexte.md` après chaque phase
- [ ] Mettre à jour `SyncService.php` MODEL_MAP (retirer Bookmark + MealIdea, ajouter TodoList + CalendarEvent)
- [ ] Mettre à jour `COCON_PLAN.md` après completion
- [ ] `php artisan wayfinder:generate` après chaque modification de routes/controllers
- [ ] `vendor/bin/pint --dirty --format agent` avant finalisation de chaque phase
- [ ] Build APK de validation après les phases majeures (16, 19, 20)