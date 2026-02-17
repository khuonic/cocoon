# Phase 13 — Biométrie (Face ID / Empreinte)

## Statut : Complet

## Objectif

Après le premier login email+mdp, le token Sanctum est stocké dans SecureStorage (NativePHP). Les lancements suivants utilisent Face ID / empreinte pour déverrouiller l'accès directement. Fallback vers login classique si biométrie échoue.

## Flux utilisateur

```
1er lancement → Login email+mdp → Token Sanctum flashé → Stocké dans SecureStorage → Dashboard
Lancements suivants → Écran biométrie (Face ID/empreinte) → Succès → Verify token → Dashboard
                                                           → Échec → Login email+mdp
Logout → SecureStorage nettoyé → Retour au login classique
```

## Architecture

### Backend

- **LoginResponse** (`app/Http/Responses/LoginResponse.php`) : Custom Fortify response qui crée un token Sanctum et le flash en session (`api_token`)
- **LogoutResponse** (`app/Http/Responses/LogoutResponse.php`) : Flash `logged_out: true` pour signaler au frontend de nettoyer SecureStorage
- **BiometricController** (`app/Http/Controllers/Auth/BiometricController.php`) :
  - `show()` : page Inertia `auth/BiometricLogin`
  - `verify(Request)` : valide le token Sanctum via `PersonalAccessToken::findToken()`, connecte l'utilisateur en session
- **HandleInertiaRequests** : partage `flash.api_token` et `flash.logged_out` avec le frontend
- **FortifyServiceProvider** : enregistre LoginResponse et LogoutResponse comme singletons

### Frontend

- **biometric-auth.ts** (`resources/js/services/biometric-auth.ts`) : service encapsulant NativePHP Biometric + SecureStorage
  - `isNativePHP()` : détecte l'environnement NativePHP (dynamic import `#nativephp`)
  - `hasSavedCredentials()` : vérifie si un token existe dans SecureStorage
  - `saveCredentials(token, user)` : stocke token + user
  - `authenticate()` : lance Biometric.prompt(), écoute Events.Biometric.Completed, récupère les credentials
  - `clearCredentials()` : supprime les credentials
- **BiometricLogin.vue** (`resources/js/pages/auth/BiometricLogin.vue`) : écran biométrique avec icône empreinte, auto-prompt au montage, fallback vers login classique
- **Login.vue** : au montage, redirige vers `/biometric-login` si NativePHP + credentials sauvés ; nettoie SecureStorage si `flash.logged_out`
- **AppLayout.vue** : au montage, si `flash.api_token` existe, sauvegarde les credentials dans SecureStorage

### Vite Config

- `#nativephp` externalisé dans `build.rollupOptions.external` pour permettre le build web (le module n'existe que dans le runtime NativePHP)

### Dépendance ajoutée

- `nativephp/mobile-biometrics` v1.0 (plugin NativePHP)

## Routes

| Méthode | URL | Nom | Middleware |
|---------|-----|-----|-----------|
| GET | `/biometric-login` | `biometric.login` | guest |
| POST | `/biometric-login` | `biometric.verify` | guest |

## Tests (8 tests)

1. Guest peut voir la page biometric-login
2. Authenticated user est redirigé depuis biometric-login
3. Verify avec token valide connecte l'utilisateur
4. Verify avec token invalide échoue
5. Verify avec token révoqué échoue
6. Verify requiert le champ token
7. Login réussi flash un api_token Sanctum valide
8. Logout flash le signal logged_out

## Fichiers créés/modifiés

| Action | Fichier |
|--------|---------|
| Créer | `resources/js/services/biometric-auth.ts` |
| Créer | `resources/js/pages/auth/BiometricLogin.vue` |
| Créer | `app/Http/Controllers/Auth/BiometricController.php` |
| Créer | `app/Http/Responses/LoginResponse.php` |
| Créer | `app/Http/Responses/LogoutResponse.php` |
| Créer | `tests/Feature/Auth/BiometricTest.php` |
| Modifier | `app/Providers/FortifyServiceProvider.php` |
| Modifier | `app/Http/Middleware/HandleInertiaRequests.php` |
| Modifier | `resources/js/pages/auth/Login.vue` |
| Modifier | `resources/js/layouts/AppLayout.vue` |
| Modifier | `routes/web.php` |
| Modifier | `vite.config.ts` |
