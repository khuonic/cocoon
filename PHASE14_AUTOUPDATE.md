# Phase 14 — Auto-update APK

## Contexte

L'app Cocoon est distribuée par APK direct (pas de Play Store). Actuellement, pour mettre à jour l'app, il faut envoyer manuellement le nouvel APK. On veut un mécanisme automatique : au lancement, l'app vérifie si une nouvelle version est disponible sur le serveur sync, et propose de télécharger/installer la mise à jour via une modale.

**Contraintes :**
- Même codebase pour Laravel Cloud et Raspberry Pi (aucun dev à faire lors de la migration)
- Vérification au lancement uniquement (pas de polling)
- Modale Dialog pour informer l'utilisateur + bouton "Mettre à jour" → `Browser.open(signedUrl)` (NativePHP)
- Ne se déclenche que sur NativePHP (en web classique, pas de check)
- **Sécurité** : version check protégé par `auth:sanctum`, download via signed URL temporaire (1h)

## Flux

```
Lancement app → AppLayout.onMounted
  → isNativePHP() ? non → rien
  → oui → checkForUpdate(syncApiUrl, currentVersionCode, authToken)
    → GET {syncApiUrl}/api/app/version (Bearer token)
      → 401 → token invalide, skip silencieusement
      → 404 → pas de release, skip
      → 200 → compare version_code
        → même version → rien
        → version plus récente → affiche UpdateDialog (modale)
          → "Mettre à jour" → Browser.open(signedDownloadUrl) → Android gère le download + install
          → "Plus tard" → ferme la modale

Sécurité vol de téléphone :
  → Révoquer le token Sanctum depuis l'autre téléphone
  → Le voleur ne passe pas la biométrie → pas d'accès à l'app
  → Même s'il extrait le token, le check version échoue (401)
  → Les signed URLs expirent en 1h max
```

## Étape 1 : Endpoints API

**Créer `app/Http/Controllers/Api/AppVersionController.php`**

- `check(Request)` : protégé par `auth:sanctum`
  - Lit `storage/app/releases/latest.json`
  - Si n'existe pas → 404
  - Génère un signed URL temporaire (1h) pour le download : `URL::temporarySignedRoute('api.app.download', now()->addHour())`
  - Retourne `{ version, version_code, changelog, download_url }`
- `download(Request)` : protégé par signature (`->middleware('signed')`)
  - Lit le fichier APK depuis `storage/app/releases/{filename}`
  - Si n'existe pas → 404
  - Retourne `StreamedResponse` avec `Content-Disposition: attachment`

**Modifier `routes/api.php`**

```php
// Protégé par Sanctum (dans le groupe existant)
Route::middleware(['auth:sanctum', RestrictToHousehold::class])->group(function () {
    // ... routes sync existantes ...
    Route::get('app/version', [AppVersionController::class, 'check']);
});

// Protégé par signed URL (pas besoin d'auth)
Route::get('app/download', [AppVersionController::class, 'download'])
    ->name('api.app.download')
    ->middleware('signed');
```

**Structure `storage/app/releases/latest.json` :**
```json
{
    "version": "1.0.0",
    "version_code": 1,
    "changelog": "Première version",
    "filename": "cocoon-1.0.0.apk",
    "released_at": "2026-02-17T12:00:00Z"
}
```

## Étape 2 : Commande artisan `app:publish-release`

**Créer `app/Console/Commands/PublishRelease.php`**

```
php artisan app:publish-release {apk_path} {--changelog=}
```

- Lit la version/version_code depuis `config('nativephp.version')` et `config('nativephp.version_code')`
- Valide que le fichier APK source existe
- Copie l'APK dans `storage/app/releases/cocoon-{version}.apk`
- Crée/met à jour `storage/app/releases/latest.json`
- Affiche un résumé (version, version_code, taille, chemin)

## Étape 3 : Service JS `update-checker.ts`

**Créer `resources/js/services/update-checker.ts`**

```ts
type UpdateInfo = {
    available: boolean;
    version?: string;
    changelog?: string;
    downloadUrl?: string;
};

export async function checkForUpdate(
    apiUrl: string,
    currentVersionCode: number,
    token: string,
): Promise<UpdateInfo>
```

- Appelle `GET {apiUrl}/api/app/version` avec header `Authorization: Bearer {token}`
- Compare `response.version_code` avec `currentVersionCode`
- Si `response.version_code > currentVersionCode` → retourne `{ available: true, version, changelog, downloadUrl }`
- Si erreur (401, 404, réseau) ou même version → retourne `{ available: false }`

Le token utilisé est celui stocké dans SecureStorage (même token que pour la sync et la biométrie).

## Étape 4 : Composant `UpdateDialog.vue`

**Créer `resources/js/components/UpdateDialog.vue`**

Modale utilisant les composants Dialog existants (reka-ui) :
- Props : `open`, `version`, `changelog`, `downloadUrl`
- Emit : `close`
- Titre : "Mise à jour disponible"
- Corps : "La version {version} est disponible." + changelog si présent
- Bouton primaire : "Mettre à jour" → appelle `Browser.open(downloadUrl)` via dynamic import `#nativephp`
- Bouton secondaire : "Plus tard" → `emit('close')`

## Étape 5 : Intégration AppLayout

**Modifier `resources/js/layouts/AppLayout.vue`**

Au montage, après le sync et la sauvegarde des credentials :
1. Si `syncApiUrl` existe et `isNativePHP()` :
   - Récupérer le token depuis SecureStorage via `getToken()` (nouvelle fonction dans biometric-auth.ts)
   - Appeler `checkForUpdate(syncApiUrl, appVersionCode, token)`
   - Si `available: true` → ouvrir `UpdateDialog` avec les infos

**Ajouter `getToken()` dans `resources/js/services/biometric-auth.ts`** :
- Nouvelle export qui lit le token depuis SecureStorage sans déclencher la biométrie

**Modifier `app/Http/Middleware/HandleInertiaRequests.php`**

Ajouter aux shared data :
```php
'appVersionCode' => (int) config('nativephp.version_code'),
```

## Étape 6 : Tests (~7 tests)

**Créer `tests/Feature/Api/AppVersionTest.php`**

1. `check` retourne les infos quand `latest.json` existe (auth requise)
2. `check` retourne 401 sans token valide
3. `check` retourne 404 quand pas de release
4. `download` retourne le fichier APK avec signed URL valide
5. `download` retourne 403 sans signature valide
6. Commande `app:publish-release` copie l'APK et crée `latest.json`
7. Commande `app:publish-release` échoue si fichier APK source inexistant

## Fichiers créés/modifiés

| Action | Fichier |
|--------|---------|
| Créer | `app/Http/Controllers/Api/AppVersionController.php` |
| Créer | `app/Console/Commands/PublishRelease.php` |
| Créer | `resources/js/services/update-checker.ts` |
| Créer | `resources/js/components/UpdateDialog.vue` |
| Créer | `tests/Feature/Api/AppVersionTest.php` |
| Modifier | `routes/api.php` |
| Modifier | `app/Http/Middleware/HandleInertiaRequests.php` |
| Modifier | `resources/js/layouts/AppLayout.vue` |
| Modifier | `resources/js/services/biometric-auth.ts` (ajout `getToken()`) |

## Vérification

1. `php artisan test --compact --filter=AppVersion` → tous les tests passent
2. `php artisan test --compact` → aucune régression (205+ tests)
3. `npm run build` → sans erreur
4. Test manuel : `php artisan app:publish-release fake.apk --changelog="Test"` → vérifier `/api/app/version` avec un Bearer token valide
