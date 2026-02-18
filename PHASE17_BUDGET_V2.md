# Phase 17 — Budget V2

## Objectif
Renommer deux catégories de dépenses, passer l'historique en vue mensuelle par défaut avec filtres, et ajouter un lien vers l'historique depuis le widget solde.

---

## 17.1 Catégories de dépenses

### `database/seeders/ExpenseCategorySeeder.php`
- "Loyer" → **"Charges"** (même icône ou icône maison)
- "Santé" → **"Cadeaux"** (nouvelle icône : `Gift`)

> Pas de migration de données — les données de dev sont réinitialisables via `php artisan db:seed`.

---

## 17.2 Historique mensuel avec filtres

### `app/Http/Controllers/ExpenseController::history()`
Accepter les paramètres de requête :
- `?period=monthly|annual|total` (défaut : `monthly`)
- `?month=YYYY-MM` (défaut : mois courant, uniquement pour `period=monthly`)

Logique de filtrage :
- `monthly` : settlements dont les dépenses ont `date` dans le mois sélectionné
- `annual` : settlements de l'année courante
- `total` : tous les settlements sans filtre

Passer à la vue :
- `settlements` : collection filtrée
- `period` : string (monthly/annual/total)
- `currentMonth` : string YYYY-MM (pour la navigation)
- `totalAmount` : somme des dépenses sur la période

### `resources/js/pages/Budget/History.vue`

**Filtres en haut (3 pills) :**
- Mensuel | Annuel | Total
- Clic → recharge la page avec `?period=...`

**Navigation mensuelle (visible uniquement si `period=monthly`) :**
- `← Janvier 2026   Février 2026   Mars 2026 →`
- Flèches changent `?month=YYYY-MM`

**Résumé de la période :**
- Total dépensé, répartition par utilisateur

---

## 17.3 Lien historique depuis le solde

### `resources/js/pages/Budget/Index.vue`
Dans le widget balance (BannerBalance ou équivalent) :
- Ajouter sous le solde un lien texte : `"Voir l'historique →"` → `/expenses/history`
- Visible en permanence (pas seulement quand balance = 0)

---

## 17.4 Tests

### `tests/Feature/Expense/ExpenseHistoryTest.php`
- Ajouter test : historique avec `?period=monthly` ne retourne que le mois courant
- Ajouter test : historique avec `?period=annual` retourne l'année courante
- Ajouter test : historique avec `?period=total` retourne tout
- Ajouter test : navigation `?month=YYYY-MM` retourne le bon mois