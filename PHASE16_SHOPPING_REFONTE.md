# Phase 16 — Refonte Shopping

## Objectif
Transformer l'affichage en cards avec menu ⋮, supprimer quantité/favoris, rendre les catégories collapsibles, mémoriser la dernière liste consultée.

---

## 16.1 Base de données

### Migration : modifier `shopping_items`
- Supprimer colonne `quantity`
- Supprimer colonne `is_favorite`

---

## 16.2 Backend

### `app/Models/ShoppingItem.php`
- Retirer `quantity` et `is_favorite` du `$fillable`
- Retirer le cast `is_favorite`

### `app/Http/Controllers/ShoppingItemController.php`
- Supprimer la méthode `toggleFavorite()`

### `app/Http/Requests/ShoppingItem/StoreShoppingItemRequest.php`
- Supprimer la règle de validation `quantity`

### `routes/web.php`
- Supprimer `Route::patch('shopping-items/{shopping_item}/toggle-favorite', ...)`

---

## 16.3 Frontend

### `resources/js/pages/Shopping/Show.vue` (refonte complète)

**Affichage en cards :**
- Chaque item est une card (padding `p-4`, coins arrondis `rounded-xl`, ombre `shadow-sm`)
- Click sur la card → toggle check (fond barré + opacité réduite si coché)
- **Pas** de case à cocher visible : la card entière est cliquable

**Menu ⋮ (DropdownMenu) :**
- Petit bouton `⋮` en haut à droite de chaque card (size-8, ghost variant)
- Options : "Modifier" | "Supprimer"
- "Modifier" : ouvre un petit formulaire (bottom sheet / dialog) avec juste le champ `nom` et le sélecteur `catégorie`

**Catégories collapsibles :**
- Chaque section de catégorie a un header cliquable
- Click sur le header → toggle affichage des items de la catégorie (avec animation douce)
- Icône chevron qui tourne (ChevronDown ↔ ChevronUp)
- État du collapse stocké en `ref<Record<string, boolean>>` (par catégorie)
- Par défaut : toutes ouvertes

**Section "Cochés" :**
- Conserver le comportement existant (collapsible en bas)

**Suppression :**
- Retirer tous les éléments liés aux favoris (bouton étoile, etc.)
- Retirer le champ quantité du formulaire d'ajout sticky en bas

**Formulaire d'ajout (sticky en bas) :**
- Uniquement : input texte (nom) + sélecteur catégorie + bouton "+"
- Plus de champ quantité

### `resources/js/pages/Shopping/Index.vue`

**Mémoriser la dernière liste :**
- Au montage : lire `localStorage.getItem('cocon_last_shopping_list_id')`
- Si l'ID existe et correspond à une liste dans la prop `lists` : `router.visit(show.url(id))` immédiatement
- Si pas d'ID ou liste introuvable : afficher normalement l'index

### `resources/js/pages/Shopping/Show.vue`

**Sauvegarder la dernière liste :**
- Dans `onMounted` : `localStorage.setItem('cocon_last_shopping_list_id', props.list.id)`

---

## 16.4 Sync

### `SyncService.php`
- Retirer `quantity` et `is_favorite` du payload `ShoppingItem` (si présents)
- Adapter la désérialisation en conséquence

---

## 16.5 Tests

### `tests/Feature/ShoppingItem/ShoppingItemTest.php`
- Supprimer les tests `toggleFavorite`
- Mettre à jour les tests `store` : ne plus envoyer `quantity` ni `is_favorite`
- Ajouter test : vérifier que `quantity` est ignoré si envoyé (ou retourne une erreur)

### `tests/Feature/ShoppingList/ShoppingListTest.php`
- Pas de changement majeur attendu

---

## Notes UX

- Le menu ⋮ en haut à droite ne gêne pas l'action principale (click sur la card pour cocher)
- Les catégories collapsibles permettent de "ranger" les rayons déjà faits
- La mémorisation de la dernière liste est côté client (localStorage) → pas de colonne en base, pas de sync nécessaire