# Mise en production — Cocoon

> Guide complet pour déployer le backend de sync, builder l'APK, et configurer les deux téléphones.

---

## Vue d'ensemble

L'app tourne **offline-first** : chaque téléphone a sa propre base SQLite locale.
La synchronisation entre les deux appareils passe par une **API déployée sur Laravel Cloud** (Postgres serverless).

```
[Kevin Android]  ←──sync──→  [Laravel Cloud API]  ←──sync──→  [Lola Android]
   SQLite local                  Postgres serverless               SQLite local
```

> Le backend Cloud fait tourner la **même codebase** Laravel — seul `DB_CONNECTION=pgsql`
> change par rapport au développement local en SQLite.

**Flux de sync complet :**
1. Chaque modification locale est enregistrée dans `SyncLog` (via le trait `Syncable`)
2. Au lancement, `AppLayout` appelle `sync()` depuis `sync-client.ts`
3. Si c'est la première sync : `fullSync()` — envoie les pending locaux + reçoit tout le Cloud
4. Sinon : `pushLocalChanges()` pousse les pending locaux, puis `pull()` récupère les nouveautés
5. Les entrées `SyncLog` sont marquées `synced_at` après un push réussi

---

## 1. Prérequis

- Compte [Laravel Cloud](https://cloud.laravel.com) créé
- Repo GitHub du projet (Laravel Cloud déploie depuis GitHub)
- Android Studio installé sur la machine de build
- PHP 8.4 + Composer + Node 20+ sur la machine de build

---

## 2. Déployer le backend de sync sur Laravel Cloud

### 2a. Créer le projet sur Laravel Cloud

1. Aller sur [cloud.laravel.com](https://cloud.laravel.com)
2. **New project** → connecter le repo GitHub `cocoon`
3. Type d'environnement : **Serverless**
4. Base de données : **Postgres** (créer une nouvelle base)
5. Laisser Laravel Cloud détecter automatiquement le framework

Laravel Cloud gère lui-même le déploiement à chaque push sur la branche configurée (ex: `main`).

### 2b. Variables d'environnement à configurer dans Cloud

Dans **Settings → Environment** du projet Cloud, ajouter :

```
APP_KEY=           # générer : php artisan key:generate --show
APP_ENV=production
APP_DEBUG=false

# DB_* fournis automatiquement par Cloud (Postgres)
DB_CONNECTION=pgsql
```

> `APP_KEY` doit être une clé distincte de celle du `.env` local.
> `SESSION_DRIVER` n'est pas nécessaire — le Cloud ne sert que des endpoints
> API stateless (Sanctum Bearer), jamais de sessions.

### 2c. Build commands dans Cloud

Dans **Settings → Build Commands**, remplacer les commandes par défaut par :

```
composer config http-basic.plugins.nativephp.com kevininc155@gmail.com TOKEN_NATIVEPHP
composer install --no-dev --no-interaction --optimize-autoloader
php artisan config:cache
php artisan route:cache
php artisan event:cache
```

> Le `TOKEN_NATIVEPHP` est le token d'accès NativePHP (voir `.env` local ou dashboard NativePHP).
> `npm run build` n'est **pas** nécessaire sur Cloud — le serveur Cloud ne sert que l'API (pas de Vite manifest).
> `--prefer-dist` doit être **absent** pour que le path package `cocoon/local-notifications` soit correctement résolu.

### 2d. Premier déploiement

Pousser sur la branche configurée (ou déclencher manuellement depuis le dashboard Cloud).

Une fois déployé, lancer les migrations depuis le dashboard Cloud → **Artisan** :

```
migrate --force
db:seed --class=ExpenseCategorySeeder --force
db:seed --class=JokeSeeder --force
db:seed --class=ShoppingListSeeder --force
```

### 2e. Créer les comptes utilisateurs sur Cloud

Depuis **Dashboard Cloud → Tinker** (ou terminal Artisan) :

```php
\App\Models\User::create([
    'name'     => 'Kevin',
    'email'    => 'kevininc155@gmail.com',
    'password' => bcrypt('MOT_DE_PASSE_COMMUN'),
]);

\App\Models\User::create([
    'name'     => 'Lola',
    'email'    => 'lolavivant@hotmail.fr',
    'password' => bcrypt('MOT_DE_PASSE_COMMUN'),
]);
```

> Utiliser le **même mot de passe** que celui qui sera saisi lors du setup sur les téléphones.
> L'app appelle automatiquement `POST {syncApiUrl}/api/login` au premier setup/login
> pour obtenir le token Cloud et le stocker en localStorage (`cocoon_sync_token`).
> Ce token est ensuite réutilisé à chaque session (y compris les logins biométriques).

> **Après avoir créé les users**, relancer le seeder ShoppingList pour qu'il puisse s'associer aux users :
> `db:seed --class=ShoppingListSeeder --force`

### 2f. Récupérer l'URL du projet

L'URL ressemble à `https://cocoon-xxxx.laravel.cloud`.
C'est la valeur à mettre dans `SYNC_API_URL` côté APK (étape 3a).

---

## 3. Préparer et builder l'APK

### 3a. Configurer `.env` local pour le build de production

```env
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...              # clé locale (différente du Cloud)

NATIVEPHP_APP_ID=com.cocoon.app
NATIVEPHP_APP_VERSION="1.0.0"
NATIVEPHP_APP_VERSION_CODE=1   # entier, incrémenter à chaque release

SYNC_API_URL=https://cocoon-xxxx.laravel.cloud   # URL récupérée à l'étape 2f
```

### 3b. Générer les credentials de signature (une seule fois)

```bash
php artisan native:credentials android
```

Cette commande génère un keystore et met à jour `.env` avec les credentials de signature.

> **Important** : conserver le keystore généré — il est nécessaire pour toutes les mises à jour futures.
> Sans lui, Android refusera d'installer une mise à jour par-dessus l'app existante.
> Le keystore se trouve dans `credentials/` à la racine du projet.

### 3c. Builder les assets frontend

```bash
npm run build -- --mode=android
```

### 3d. Packager l'APK signé

```bash
php artisan native:package android \
    --keystore=C:\Users\kevin\Herd\cocoon\credentials\app-release-key.jks \
    --keystore-password=PASSWORD \
    --key-alias=ALIAS \
    --key-password=KEY_PASSWORD
```

> Les valeurs `--keystore-password`, `--key-alias`, `--key-password` sont dans `.env`
> sous les clés `NATIVEPHP_KEYSTORE_*` générées à l'étape 3b.

L'APK se trouve dans :
```
nativephp/android/app/build/outputs/apk/release/app-release.apk
```

### 3e. Publier la release (système d'auto-update)

Cette commande upload l'APK sur le bucket Cloud S3 et génère `latest.json`.
Les téléphones consultent ce fichier pour détecter les mises à jour.

**Avant de lancer, configurer `.env` local pour pointer vers le bucket Cloud :**

```env
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=...        # depuis Dashboard Cloud → Resources → Storage
AWS_SECRET_ACCESS_KEY=...
AWS_DEFAULT_REGION=...
AWS_BUCKET=...
AWS_ENDPOINT=...
AWS_USE_PATH_STYLE_ENDPOINT=true
AWS_VERIFY_SSL=false         # nécessaire sur Windows (SSL cURL error 60 avec R2)
```

**Lancer la commande :**

```bash
php artisan app:publish-release \
    nativephp/android/app/build/outputs/apk/release/app-release.apk \
    --changelog="Version initiale"
```

**Après publication, remettre `.env` local :**
```env
FILESYSTEM_DISK=local
# Retirer les variables AWS_*
```

> Le bucket Cloud (`FILESYSTEM_DISK=private` côté Cloud) et le disk `s3` local pointent vers le même bucket.
> `config/filesystems.php` → disk `s3` doit avoir `'http' => ['verify' => env('AWS_VERIFY_SSL', true)]`
> pour que `AWS_VERIFY_SSL=false` soit respecté côté cURL.

---

## 4. Installer l'app sur les téléphones

### Première installation — transfert direct

**Option A — Transfert manuel (recommandé, plus simple) :**
1. Brancher le téléphone en USB, choisir **Transfert de fichiers** (MTP)
2. Copier `nativephp/android/app/build/outputs/apk/release/app-release.apk` sur le téléphone
3. Ouvrir le fichier depuis le gestionnaire de fichiers du téléphone

**Option B — ADB (USB + débogage USB activé sur le téléphone) :**
```bash
adb install nativephp/android/app/build/outputs/apk/release/app-release.apk
```

Si bloqué : **Paramètres → Sécurité → Sources inconnues** → autoriser

### Mises à jour suivantes — auto-update

Une fois l'app installée, elle vérifie automatiquement les nouvelles versions au lancement.
Il suffit de rebuilder et republier (voir étape 7).

---

## 5. Premier lancement sur chaque téléphone

### 5a. Écran de setup

Au tout premier lancement, l'app affiche l'écran `/setup` :

1. Saisir l'**email** (`kevininc155@gmail.com` ou `lolavivant@hotmail.fr`)
2. Saisir le **mot de passe** (le même que celui créé sur le Cloud à l'étape 2e)
3. L'app crée le compte en base SQLite locale et ouvre le dashboard

> Au setup, l'app appelle automatiquement `POST {syncApiUrl}/api/login` pour obtenir
> le token Cloud (`cocoon_sync_token`) et le stocker en localStorage.
> Elle crée aussi un token Sanctum local (`cocoon_auth_token`) pour authentifier les appels API locaux.

### 5b. Activer la biométrie

Dans **Paramètres** de l'app :
- Activer l'authentification par empreinte/visage
- Les credentials sont stockés dans le Secure Storage Android (Keystore)

> La biométrie n'est disponible que si `cocoon_auth_token` est présent en localStorage
> (flashé automatiquement au setup et au login email/mot de passe).

### 5c. Synchronisation

La sync s'active automatiquement — aucune action manuelle :

1. Au setup/login, le token Cloud est stocké dans `cocoon_sync_token`
2. À chaque lancement, `AppLayout` récupère ce token et configure `sync-client`
3. Les modifications locales (via `Syncable` → `SyncLog`) sont poussées au Cloud
4. Les nouveautés Cloud sont récupérées et appliquées localement (last-write-wins)

---

## 6. Checklist avant de donner l'app à Lola

- [ ] Backend Cloud déployé et accessible (tester `https://cocoon-xxxx.laravel.cloud/up`)
- [ ] Migrations Cloud OK
- [ ] Seeders ExpenseCategory + Joke + ShoppingList lancés sur Cloud
- [ ] Users Kevin + Lola créés sur Cloud
- [ ] `.env` local mis à jour avec `SYNC_API_URL`
- [ ] APK buildé en production (`NATIVEPHP_APP_VERSION="1.0.0"`, `VERSION_CODE=1`)
- [ ] APK testé sur le téléphone de Kevin (setup + biométrie + sync)
- [ ] Sync testée (créer une dépense → sync → visible sur Cloud)
- [ ] APK installé sur le téléphone de Lola
- [ ] Compte Lola créé via `/setup` sur son téléphone
- [ ] Sync testée dans les deux sens (Kevin → Cloud → Lola et inversement)

---

## 7. Développement en parallèle de la prod

### APP_ID différent pour coexistence debug/prod

Pour avoir l'app debug et l'app prod sur le même téléphone simultanément :

**`.env` local (dev) :**
```env
NATIVEPHP_APP_ID=com.cocoon.app.debug
APP_NAME="Cocoon Debug"
```

**`.env` prod :**
```env
NATIVEPHP_APP_ID=com.cocoon.app
APP_NAME="Cocoon"
```

Android considère les deux apps comme distinctes → installation sans conflit.

### Idées pour distinguer visuellement debug vs prod

- **Header rouge** : conditionner la couleur du header sur `APP_ENV=local` via une prop Inertia
- **Icône différente** : remplacer `ic_launcher` dans `nativephp/android/app/src/debug/res/`

---

## 8. Workflow pour les mises à jour

```bash
# 1. Développer + tester
php artisan test --compact

# 2. Incrémenter la version dans .env
#    NATIVEPHP_APP_VERSION="1.1.0"
#    NATIVEPHP_APP_VERSION_CODE=2    ← toujours un entier qui monte

# 3. Builder les assets
npm run build -- --mode=android

# 4. Packager l'APK signé
php artisan native:package android \
    --keystore=C:\Users\kevin\Herd\cocoon\credentials\app-release-key.jks \
    --keystore-password=PASSWORD \
    --key-alias=ALIAS \
    --key-password=KEY_PASSWORD

# 5. Configurer .env local avec les variables AWS_ (voir étape 3e)

# 6. Publier (met à jour latest.json sur Cloud via storage)
php artisan app:publish-release \
    nativephp/android/app/build/outputs/apk/release/app-release.apk \
    --changelog="Ce qui a changé"

# 7. Remettre FILESYSTEM_DISK=local et retirer les AWS_*

# 8. Pousser le code sur GitHub → Cloud redéploie automatiquement

# Les deux téléphones verront la mise à jour au prochain lancement
```

---

## 9. Dépannage

| Problème | Solution |
|----------|----------|
| App bloquée sur `/setup` | L'email n'est pas dans la whitelist `config/cocon.php` |
| Sync échoue (401) | Token Cloud invalide — se déconnecter et se reconnecter avec email+password pour le renouveler |
| Sync échoue (réseau) | Vérifier `SYNC_API_URL` dans les paramètres de l'app |
| APK refusé à l'installation | Activer "Sources inconnues" dans Paramètres Android |
| Biométrie inactive | Se déconnecter et se reconnecter avec email+password (génère un nouveau `cocoon_auth_token`) |
| Auto-update ne détecte pas la MAJ | Vérifier que `NATIVEPHP_APP_VERSION_CODE` a bien été incrémenté |
| `latest.json` introuvable | Vérifier que le volume Cloud est configuré et que `app:publish-release` a réussi |
| Erreur SSL cURL lors de publish-release | Ajouter `AWS_VERIFY_SSL=false` dans `.env` local + vérifier `config/filesystems.php` disk s3 |
| Build Cloud échoue (path package) | Vérifier que `--prefer-dist` est absent des build commands + que `composer config http-basic` est présent |
| Setup échoue avec 500 | Cold start Cloud trop long — réessayer ; le `flashSyncToken` a un timeout de 10s et est non-bloquant |

Attention a l'utilisation de native:install --force qui supprime les binaries
Pour récuperer les binaries important : 
https://d23y5k23b3lz91.cloudfront.net/android/android/jniLibs.zip
Ensuite mettre "arm64-v8a" ici :
C:\Users\kevin\Herd\cocoon\nativephp\android\app\src\main\staticLibs\

Sinon voir ici https://bin.nativephp.com/main/versions.json
