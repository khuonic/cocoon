# Plan — Fix authentification biométrique

## Problème

`SecureStorage.Get/Set/Delete` ne sont PAS dans le `BridgeFunctionRegistry` exposé via `/_native/api/call`.
L'erreur : `METHOD_NOT_FOUND — Method 'SecureStorage.Get' is not registered in the bridge registry`.

`SecureStorage` est géré par la librairie NativePHP Mobile compilée (AAR) via JNI direct depuis PHP,
mais n'est pas accessible via l'endpoint HTTP `/_native/api/call` utilisé par le JS.

**Règle : SecureStorage ne doit jamais être appelé depuis JS.**

---

## Nouveau flow (corrigé)

```
1. Login.vue → vérifie localStorage('cocoon_has_credentials') → redirige vers /biometric-login
2. BiometricLogin.vue → appelle authenticate() → Biometric.Prompt via /_native/api/call
3. window.Native.on(BiometricCompleted) → succès → router.post(verify.url())  [SANS token]
4. BiometricController@verify → SecureStorage::get('cocoon_auth_token') PHP-side
5. PHP valide le token Sanctum → Auth::login() → redirect('/')
```

---

## Changements à faire

### 1. `LoginResponse.php` — Sauvegarder le token dans SecureStorage côté PHP

```php
use Native\Mobile\Facades\SecureStorage;

// Après création du token :
$token = $request->user()->createToken('mobile')->plainTextToken;
SecureStorage::set('cocoon_auth_token', $token);
session()->flash('api_token', $token); // garder pour sync
```

**Avantage** : JS n'a plus besoin de toucher SecureStorage du tout.

### 2. `BiometricController@verify` — Lire le token depuis PHP

```php
public function verify(Request $request): RedirectResponse
{
    $token = \Native\Mobile\Facades\SecureStorage::get('cocoon_auth_token');

    if (! $token) {
        return back()->withErrors(['biometric' => 'Aucune session sauvegardée.']);
    }

    $accessToken = PersonalAccessToken::findToken($token);

    if (! $accessToken || ! $accessToken->tokenable) {
        return back()->withErrors(['biometric' => 'Session expirée, reconnectez-vous.']);
    }

    Auth::login($accessToken->tokenable);
    $request->session()->regenerate();

    return redirect()->intended(config('fortify.home'));
}
```

**Supprimer** la validation `['token' => 'required|string']`.

### 3. `biometric-auth.ts` — `authenticate()` retourne juste true/false

```typescript
export async function authenticate(): Promise<boolean> {
    const native = getNative();
    if (!native) return false;

    return new Promise((resolve) => {
        const handler = async (payload: unknown) => {
            native.off(BIOMETRIC_EVENT, handler);
            const p = payload as { success: boolean };
            resolve(p.success);
        };

        native.on(BIOMETRIC_EVENT, handler);

        nativeCall('Biometric.Prompt', {}).catch((e) => {
            console.error('[BiometricAuth] Biometric.Prompt call failed:', e);
            native.off(BIOMETRIC_EVENT, handler);
            resolve(false);
        });
    });
}
```

**Supprimer** `hasSavedCredentials`, `saveCredentials`, `clearCredentials`, `getToken`, `saveSyncToken`, `getSyncToken`
qui utilisent tous SecureStorage.

### 4. `hasSavedCredentials` → localStorage flag

```typescript
export function hasSavedCredentials(): boolean {
    return localStorage.getItem('cocoon_has_credentials') === '1';
}

export function markCredentialsSaved(): void {
    localStorage.setItem('cocoon_has_credentials', '1');
}

export function clearCredentialsFlag(): void {
    localStorage.removeItem('cocoon_has_credentials');
}
```

### 5. `BiometricLogin.vue` — Appel sans token

```typescript
if (result) {
    router.post(verify.url()); // plus de { token: result.token }
}
```

### 6. `AppLayout.vue` — Utiliser markCredentialsSaved() au lieu de saveCredentials()

Chercher où `saveCredentials` est appelé (sur `flash.api_token`) et remplacer par `markCredentialsSaved()`.

### 7. `LogoutResponse.php` — Nettoyer le flag localStorage

Garder `flash('logged_out', true)` pour que le JS appelle `clearCredentialsFlag()` au logout.

### 8. `clearCredentials` dans Login.vue / logout

Remplacer `clearCredentials()` par `clearCredentialsFlag()`.

---

## Ce qu'on garde inchangé

- `nativeCall('Biometric.Prompt', {})` — fonctionne (enregistré dans PluginBridgeFunctionRegistration.kt)
- `window.Native.on(BIOMETRIC_EVENT, ...)` — fonctionne
- `BiometricController@show` — inchangé
- Routes biométriques — inchangées

---

## Tests à mettre à jour

- `BiometricControllerTest` : `verify` ne requiert plus `token` dans le body
- Mocker `SecureStorage::get()` via `SecureStorage::shouldReceive('get')`
