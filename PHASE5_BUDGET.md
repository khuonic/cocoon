# Phase 5 : Module Budget complet

## Contexte

Le module Budget est le coeur de Cocon. Il permet au couple de suivre leurs depenses partagees, voir qui doit quoi a l'autre, et regler la balance ("on est quittes"). Les modeles, enums, factories et migrations existent deja (Phase 2). Il faut maintenant implementer la logique metier, le CRUD complet, et les pages frontend.

## Decisions

| Sujet | Decision | Raison |
|-------|----------|--------|
| Graphiques historique | Barres CSS Tailwind | Pas de lib externe, suffisant pour 2 users |
| Reglement | Dialog de confirmation sur la page Budget | Pas besoin d'une page dediee |
| Show/Edit | Combines (edit = detail) | App mobile simple, pas besoin d'une vue read-only |
| Formulaire | `useForm` d'Inertia | Champs conditionnels (split_value, recurrence) |
| Calcul balance | Service `BalanceCalculator` | Testable unitairement, reutilisable pour le Dashboard |

## Architecture

### BalanceCalculator (`app/Services/BalanceCalculator.php`)

Methode `calculate()` :
- Query toutes les depenses non reglees (`settled_at IS NULL`)
- Pour chaque depense, calcule ce que le non-payeur doit :
  - `equal` : montant / 2
  - `full_payer` : 0 (depense perso, pas de dette)
  - `full_other` : montant total
  - `custom` : `split_value`
- Calcule la balance nette entre les deux users
- Retourne : `balance` (string decimal), `creditor` (User|null), `debtor` (User|null), `is_settled` (bool)

### Routes

```php
// routes/web.php (dans le groupe auth+verified)
Route::post('expenses/settle', [ExpenseController::class, 'settle'])->name('expenses.settle');
Route::get('expenses/history', [ExpenseController::class, 'history'])->name('expenses.history');
Route::resource('expenses', ExpenseController::class)->except(['show']);
```

> Routes custom declarees AVANT le resource pour eviter que `{expense}` capture "settle" ou "history".

### Validation (Form Requests)

**StoreExpenseRequest / UpdateExpenseRequest** :
- `amount` : required, numeric, min:0.01, max:99999.99
- `description` : required, string, max:255
- `category_id` : required, exists:expense_categories
- `paid_by` : required, exists:users
- `split_type` : required, enum SplitType
- `split_value` : nullable, numeric, required_if:split_type,custom
- `date` : required, date
- `is_recurring` : boolean
- `recurrence_type` : nullable, required_if:is_recurring,true, enum RecurrenceType

Messages en francais.

## Pages Frontend

### Budget/Index.vue
- **BalanceBanner** en haut : affiche qui doit quoi + bouton "Regler" (ouvre Dialog)
- Liste de **ExpenseCard** (depenses non reglees, triees par date desc)
- EmptyState si aucune depense
- Bouton "+" dans le header-right pour ajouter
- Lien "Voir l'historique" en bas

### Budget/Create.vue
Formulaire avec `useForm` :
1. Montant (input numerique, grand, centre)
2. Description (text input)
3. Categorie (CategoryPicker : grille 4 colonnes d'icones colorees)
4. Paye par (toggle 2 boutons : Kevin / Lola)
5. Repartition (radio : Moitie-moitie / Perso / 100% l'autre / Custom)
   - Si custom : input montant pour split_value
6. Date (date picker, defaut aujourd'hui)
7. Recurrence (switch + selecteur type si active)
8. Bouton "Ajouter la depense"

### Budget/Edit.vue
- Comme Create, pre-rempli avec les donnees de la depense
- Bouton "Supprimer" en bas avec Dialog de confirmation

### Budget/History.vue
- Barres CSS colorees par categorie (% du total)
- Liste paginee de toutes les depenses (reglees = style dimmed + badge "Regle")

## Composants

| Composant | Props | Description |
|-----------|-------|-------------|
| `BalanceBanner` | balance: BalanceData | Banniere balance + Dialog reglement |
| `ExpenseCard` | expense: Expense | Carte depense (icone, description, montant, payeur) |
| `CategoryPicker` | categories, modelValue | Grille 4 colonnes selectable |
| `CategoryIcon` | name: string, color: string | Map nom icone -> composant lucide |

## Tests

### Unit : BalanceCalculatorTest (8 tests)
- Balance zero sans depenses
- Split equal (100 EUR -> 50 du)
- Split full_payer (depense perso -> 0 du)
- Split full_other (100 EUR -> 100 du)
- Split custom (100 EUR, split_value=30 -> 30 du)
- Compensation entre users (Kevin paie 100, Lola paie 60, equal -> net 20)
- Ignore les depenses reglees
- `is_settled` = true quand balance = 0

### Feature : Expense (5 fichiers, ~20 tests)
- **IndexTest** : acces, affichage non-reglees, balance
- **CreateTest** : acces formulaire, store valide, validations champs
- **EditTest** : acces, update, delete
- **SettleTest** : archive depenses, redirect
- **HistoryTest** : acces, toutes depenses, totaux categories

## Fichiers impactes (21)

| Action | Fichier |
|--------|---------|
| Creer | `app/Services/BalanceCalculator.php` |
| Creer | `app/Http/Requests/Expense/StoreExpenseRequest.php` |
| Creer | `app/Http/Requests/Expense/UpdateExpenseRequest.php` |
| Modifier | `app/Http/Controllers/ExpenseController.php` |
| Modifier | `routes/web.php` |
| Creer | `resources/js/types/budget.ts` |
| Modifier | `resources/js/types/index.ts` |
| Creer | `resources/js/components/budget/CategoryIcon.vue` |
| Creer | `resources/js/components/budget/CategoryPicker.vue` |
| Creer | `resources/js/components/budget/ExpenseCard.vue` |
| Creer | `resources/js/components/budget/BalanceBanner.vue` |
| Remplacer | `resources/js/pages/Budget/Index.vue` |
| Remplacer | `resources/js/pages/Budget/Create.vue` |
| Creer | `resources/js/pages/Budget/Edit.vue` |
| Creer | `resources/js/pages/Budget/History.vue` |
| Creer | `tests/Unit/Services/BalanceCalculatorTest.php` |
| Creer | `tests/Feature/Expense/ExpenseIndexTest.php` |
| Creer | `tests/Feature/Expense/ExpenseCreateTest.php` |
| Creer | `tests/Feature/Expense/ExpenseEditTest.php` |
| Creer | `tests/Feature/Expense/ExpenseSettleTest.php` |
| Creer | `tests/Feature/Expense/ExpenseHistoryTest.php` |
