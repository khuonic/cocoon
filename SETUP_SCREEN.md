# Écran de Setup (premier lancement)

## Contexte
Sur un appareil neuf (NativePHP Mobile), la base SQLite est vide : pas d'utilisateurs, pas de catégories. Il faut un écran de configuration initiale pour que l'utilisateur puisse créer son compte, tout en respectant la whitelist. L'objectif immédiat est de permettre à Kevin de tester l'app sur son téléphone via NativePHP Jump.

## Flow utilisateur
1. Premier lancement → DB vide → redirect auto vers `/setup`
2. L'utilisateur saisit son **email + mot de passe + confirmation**
3. Backend vérifie que l'email est dans la whitelist
4. Crée **les 2 comptes avec le même mot de passe** (l'utilisateur + le partenaire) — nécessaire pour que le module Budget fonctionne (BalanceCalculator a besoin de 2 users), et permet de se connecter en tant que l'autre pour tester
5. Seed les **catégories de dépenses** si la table est vide
6. Auto-login → redirect vers `/`
7. Les lancements suivants → écran de login classique

## Décisions prises
- **Même mot de passe** pour les 2 comptes (pratique pour tester, chaque téléphone a sa propre DB)
- **Config enrichie** : `allowed_users` remplace `allowed_emails` (nom + email)
- **Seeding automatique** des catégories de dépenses lors du setup
- **Auto-login** après la création du compte
- **Login traduit en français** en même temps

## Fichiers impactés

| Action | Fichier |
|--------|---------|
| Modifier | `config/cocon.php` |
| Modifier | `app/Http/Middleware/RestrictToHousehold.php` |
| Modifier | `app/Http/Controllers/Auth/ApiLoginController.php` |
| Modifier | `database/seeders/UserSeeder.php` |
| Modifier | `app/Providers/FortifyServiceProvider.php` |
| Modifier | `routes/web.php` |
| Modifier | `resources/js/pages/auth/Login.vue` |
| Créer | `app/Http/Controllers/Auth/SetupController.php` |
| Créer | `app/Http/Requests/Auth/SetupRequest.php` |
| Créer | `resources/js/pages/auth/Setup.vue` |
| Créer | `tests/Feature/Auth/SetupTest.php` |

## Tests (9 tests)
- Setup page accessible quand pas d'utilisateurs
- Setup redirige vers login quand des utilisateurs existent
- Login redirige vers setup quand pas d'utilisateurs
- Setup crée les 2 comptes + catégories
- Auto-login après setup
- Rejet email hors whitelist
- Validation mot de passe (confirmation)
- Store bloqué si users existent déjà
- Le partenaire peut se connecter avec le même mot de passe
