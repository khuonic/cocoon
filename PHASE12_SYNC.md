# Phase 12 : Sync Offline-First

## Décisions

- **Base cloud** : Serverless Postgres (Laravel Cloud), code 100% Eloquent standard (portable vers Raspberry Pi)
- **Données** : Tout est synchronisé (tous les modules)
- **Déclenchement** : Immédiat à chaque modification + queue offline si déconnecté
- **Conflits** : Last-write-wins (basé sur `updated_at`)
- **Biométrie** : Phase séparée (après la sync)

## Architecture

```
Téléphone A (SQLite)  ←→  Cloud API (Serverless Postgres)  ←→  Téléphone B (SQLite)
```

- Le même codebase Laravel sert les 2 contextes :
  - **Local (NativePHP)** : `routes/web.php` → pages Inertia + SQLite
  - **Cloud (Laravel Cloud)** : `routes/api.php` → API sync + Postgres
- Chaque entité a un `uuid` (déjà présent sur tous les modèles)
- Le cloud est la source de vérité pour la synchronisation

## Modèles à synchroniser

| Modèle | Notes |
|--------|-------|
| Expense | Avec split_type, category_id |
| ShoppingList | + ses ShoppingItems (cascade) |
| ShoppingItem | Lié à sa ShoppingList via uuid |
| Todo | Avec assigned_to (résolu par uuid user) |
| MealIdea | Avec tags JSON |
| Recipe | + RecipeIngredients + RecipeSteps (cascade) |
| RecipeIngredient | Lié à sa Recipe via uuid |
| RecipeStep | Lié à sa Recipe via uuid |
| Note | Avec couleur, pin |
| Bookmark | Avec catégorie, favori |
| SweetMessage | 1 par user |
| Birthday | Avec date |

**Non synchronisés** : User (créé via setup sur chaque device), Joke (seeder identique), ExpenseCategory (seeder identique), SyncQueue (local uniquement)

## Étape 1 : API Auth (Sanctum)

### 1.1 Route login API
- `POST /api/login` → `Auth/ApiLoginController` (existe déjà)
- Retourne un token Sanctum plain-text
- Vérifie email + mot de passe + whitelist

### 1.2 Stockage du token
- Côté mobile : `SecureStorage` (NativePHP) pour stocker le token
- Ajouté dans les headers : `Authorization: Bearer {token}`

### 1.3 Config Sanctum
- Vérifier `config/sanctum.php` : guard `web`, stateful domains

## Étape 2 : Trait Syncable + SyncQueue

### 2.1 Trait `Syncable`
- `app/Traits/Syncable.php`
- Appliqué sur tous les modèles à synchroniser
- Écoute les events Eloquent `created`, `updated`, `deleted`
- À chaque event → crée une entrée dans `sync_queue` (table locale)
- Flag `$isSyncing` pour éviter les boucles infinies (ne pas re-queuer les changements venant du pull)

```php
trait Syncable
{
    public bool $isSyncing = false;

    public static function bootSyncable(): void
    {
        static::created(fn ($model) => $model->queueSync('created'));
        static::updated(fn ($model) => $model->queueSync('updated'));
        static::deleted(fn ($model) => $model->queueSync('deleted'));
    }

    protected function queueSync(string $action): void
    {
        if ($this->isSyncing) return;

        SyncQueue::create([
            'syncable_type' => $this->getMorphClass(),
            'syncable_uuid' => $this->uuid,
            'action' => $action,
            'payload' => $action === 'deleted' ? null : $this->toArray(),
            'created_at' => now(),
        ]);
    }
}
```

### 2.2 Migration `sync_queue`
- `id`, `syncable_type` (string), `syncable_uuid` (string), `action` (enum: created, updated, deleted), `payload` (json nullable), `pushed_at` (nullable timestamp), `created_at` (timestamp)

### 2.3 Modèle `SyncQueue`
- `app/Models/SyncQueue.php`
- Scopes : `pending()` (where pushed_at is null), `pushed()` (where pushed_at not null)

## Étape 3 : API Routes + Controllers (Cloud)

### 3.1 Routes API (`routes/api.php`)

```php
Route::post('login', [ApiLoginController::class, 'login']);

Route::middleware(['auth:sanctum', 'household'])->group(function () {
    Route::post('sync/push', [SyncController::class, 'push']);
    Route::get('sync/pull', [SyncController::class, 'pull']);
    Route::post('sync/full', [SyncController::class, 'full']);
});
```

### 3.2 `SyncController`

**push** (`POST /api/sync/push`)
- Reçoit un tableau de changements : `{ changes: [{ type, uuid, action, data, updated_at }] }`
- Pour chaque changement :
  - `created` : chercher par uuid → si existe déjà, comparer `updated_at` → sinon `create()`
  - `updated` : trouver par uuid → comparer `updated_at` → si le push est plus récent, `update()`
  - `deleted` : trouver par uuid → `delete()`
- Retourne `{ applied: count, rejected: count, server_time: now() }`

**pull** (`GET /api/sync/pull?since={timestamp}`)
- Retourne toutes les entrées modifiées depuis `$since` pour tous les modèles syncables
- Exclut les modifications faites par le même device (via un header `X-Device-Id`)
- Format : `{ changes: [...], server_time: now() }`

**full** (`POST /api/sync/full`)
- Push + pull combinés pour le premier sync
- Le client envoie toutes ses données
- Le serveur retourne toutes les données qu'il a

### 3.3 Service `SyncService`

`app/Services/SyncService.php` — logique métier :
- `applyChange(string $type, string $uuid, string $action, array $data, Carbon $updatedAt): bool`
- `getChangesSince(Carbon $since, ?string $excludeDeviceId): Collection`
- `getModelClassFromType(string $type): string` — mapping type string → Model class
- Résolution last-write-wins encapsulée ici

## Étape 4 : Client Sync (Mobile)

### 4.1 Service JS `SyncClient`

`resources/js/services/sync-client.ts` :
- `push()` : récupère la queue non poussée → POST vers l'API → marque comme poussée
- `pull()` : GET depuis l'API avec `?since=lastSyncedAt` → applique les changements via router.reload()
- `fullSync()` : pour le premier sync
- `getToken()` : récupère le token depuis SecureStorage

### 4.2 Déclenchement automatique

- **Au lancement** : pull au montage de `AppLayout.vue`
- **À chaque modification** : le trait Syncable queue le changement → event JS dispatché → push immédiat
- **Hors ligne** : les changes restent dans `sync_queue` → push dès que la connexion revient
- **Détection réseau** : `Network.status()` (NativePHP) pour savoir si connecté

### 4.3 Config API URL

- `.env` : `SYNC_API_URL=` (vide par défaut, sync désactivée tant que non configuré)
- `config/cocon.php` : `'sync_api_url' => env('SYNC_API_URL')`
- Passé aux pages Inertia via shared data

## Étape 5 : Gestion des relations (cascade sync)

Pour les modèles imbriqués (ShoppingList→Items, Recipe→Ingredients/Steps) :
- Le push envoie le parent + ses enfants dans le même batch
- Le serveur applique dans l'ordre : parent d'abord, enfants ensuite
- La suppression d'un parent supprime ses enfants (cascade en DB)
- Les enfants sont identifiés par leur propre `uuid`

## Étape 6 : Device ID

- Chaque installation a un `device_id` unique (généré au premier lancement, stocké en DB locale)
- Envoyé dans un header `X-Device-Id` à chaque requête sync
- Permet d'exclure ses propres modifications du pull

## Étape 7 : Tests

### Tests Feature API (cloud-side)
- Login API retourne un token
- Push crée/modifie/supprime des enregistrements
- Pull retourne les changements depuis un timestamp
- Last-write-wins : une modif plus ancienne est rejetée
- Full sync fonctionne au premier lancement
- Auth refusée sans token / avec token invalide

### Tests Feature Sync (local-side)
- Le trait Syncable crée des entrées dans sync_queue
- Le flag isSyncing empêche les boucles
- Les changements pending sont récupérés correctement

## Ordre d'implémentation

| Sous-étape | Contenu |
|------------|---------|
| 12.1 | Migration sync_queue + modèle SyncQueue |
| 12.2 | Trait Syncable + application sur tous les modèles |
| 12.3 | SyncService (logique métier push/pull) |
| 12.4 | SyncController (push/pull/full) + routes API |
| 12.5 | Tests API (push, pull, conflits, auth) |
| 12.6 | Config API URL + shared Inertia data |
| 12.7 | SyncClient JS (push/pull côté mobile) |
| 12.8 | Intégration AppLayout (sync au lancement, détection réseau) |
| 12.9 | Tests locaux (trait, queue, service) |
| 12.10 | Update contexte + mémoire |

## Notes techniques

- **Pas de raw SQL** : 100% Eloquent pour rester portable (Postgres → MySQL/MariaDB)
- **Timestamps UTC** : toutes les comparaisons `updated_at` en UTC
- **Batch size** : limiter les push/pull à 100 changements par requête (pagination)
- **Nettoyage** : purger les entrées `sync_queue` poussées depuis > 30 jours
- **SweetMessage** : cas spécial, pas de uuid propre — identifié par `user_id`
- **Sync désactivable** : si `SYNC_API_URL` est vide, la sync est inactive (mode 100% local)
