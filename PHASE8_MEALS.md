# Phase 8 — Module Repas (Banque d'idées + Recettes)

## Résumé

Remplace le concept initial de "grille repas semaine" par une **banque d'idées et recettes** avec 2 onglets sur `/meal-plans`.

- **Idées** : titre + description optionnelle + URL optionnel + tags prédéfinis — CRUD via modal
- **Recettes** : titre, description, URL, temps prépa/cuisson, portions, tags, ingrédients structurés, étapes ordonnées — pages dédiées create/show/edit

## Modèles

- `MealIdea` — modifié : ajout description, url dans fillable
- `Recipe` — nouveau : title, description, url, prep_time, cook_time, servings, tags, created_by, uuid
- `RecipeIngredient` — nouveau : recipe_id, name, quantity, unit, sort_order
- `RecipeStep` — nouveau : recipe_id, instruction, sort_order

## Enum

- `MealTag` : Rapide, Végé, Comfort, Léger, Gourmand

## Nettoyage

- Supprimé : `MealPlan`, `MealType`, `MealPlanFactory`, migration meal_plans
- Migration drop_meal_plans_table créée

## Routes

- `GET /meal-plans` — index (idées + recettes + tags)
- `POST /meal-ideas` — store
- `PUT /meal-ideas/{meal_idea}` — update
- `DELETE /meal-ideas/{meal_idea}` — destroy
- `GET /recipes/create` — create
- `POST /recipes` — store
- `GET /recipes/{recipe}` — show
- `GET /recipes/{recipe}/edit` — edit
- `PUT /recipes/{recipe}` — update
- `DELETE /recipes/{recipe}` — destroy

## Tests

- 10 tests MealIdea (Feature)
- 12 tests Recipe (Feature)
- 140 tests total, aucune régression
