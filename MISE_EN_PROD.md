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
> Générer : `php artisan key:generate --show`
> `SESSION_DRIVER` n'est pas nécessaire — le Cloud ne sert que des endpoints
> API stateless (Sanctum Bearer), jamais de sessions.

### 2c. Premier déploiement

Pousser sur la branche configurée (ou déclencher manuellement depuis le dashboard Cloud).

Une fois déployé, lancer les migrations depuis le dashboard Cloud → **Artisan** :

```
migrate --force
db:seed --class=ExpenseCategorySeeder --force
db:seed --class=JokeSeeder --force
db:seed --class=ShoppingListSeeder --force
```

### 2d. Créer les comptes utilisateurs sur Cloud

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
> pour obtenir le token cloud et le stocker dans le Secure Storage Android.
> Ce token est ensuite réutilisé à chaque session (y compris les logins biométriques).

### 2e. Récupérer l'URL du projet

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

SYNC_API_URL=https://cocoon-xxxx.laravel.cloud   # URL récupérée à l'étape 2e
```

### 3b. Générer les credentials de signature (une seule fois)

```bash
php artisan native:credentials android
```

Cette commande génère un keystore et met à jour `.env` avec les credentials de signature.

> **Important** : conserver le keystore généré — il est nécessaire pour toutes les mises à jour futures. Sans lui, Android refusera d'installer une mise à jour par-dessus l'app existante.

### 3c. Builder les assets frontend

```bash
npm run build -- --mode=android
```

### 3d. Packager l'APK signé

```bash
php artisan native:package android \
    --keystore=/chemin/vers/keystore \
    --keystore-password=PASSWORD \
    --key-alias=ALIAS \
    --key-password=KEY_PASSWORD
```

> Les valeurs `--keystore`, `--keystore-password`, `--key-alias`, `--key-password` sont celles générées à l'étape 3b et ajoutées dans `.env`.

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
AWS_VERIFY_SSL=false         # nécessaire sur Windows (SSL cURL)
```

Et dans `config/filesystems.php`, disk `s3`, ajouter :
```php
'http' => ['verify' => env('AWS_VERIFY_SSL', true)],
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
# Retirer AWS_VERIFY_SSL
```

> Le bucket Cloud (`FILESYSTEM_DISK=private` côté Cloud) et le disk `s3` local pointent vers le même bucket — c'est ce qui permet aux téléphones de télécharger l'APK via l'API Cloud.

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

Ou par transfert réseau :
1. Copier l'APK sur le téléphone (partage réseau / cloud personnel)
2. Ouvrir le fichier depuis le gestionnaire de fichiers
3. Si bloqué : **Paramètres → Sécurité → Sources inconnues** → autoriser

### Mises à jour suivantes — auto-update

Une fois l'app installée, elle vérifie automatiquement les nouvelles versions.
Il suffit de rebuilder et republier (voir étape 7).

---

## 5. Premier lancement sur chaque téléphone

### 5a. Écran de setup

Au tout premier lancement, l'app affiche l'écran `/setup` :

1. Saisir l'**email** (`kevininc155@gmail.com` ou `lolavivant@hotmail.fr`)
2. Saisir le **mot de passe** (le même que celui créé sur le Cloud à l'étape 2d)
3. L'app crée le compte en base SQLite locale et ouvre le dashboard

### 5b. Activer la biométrie

Dans **Paramètres** de l'app :
- Activer l'authentification par empreinte/visage
- Les credentials sont stockés dans le Secure Storage Android (Keystore)

### 5c. Synchronisation

La sync s'active automatiquement — aucune action manuelle :

1. Au setup, l'app appelle `POST {syncApiUrl}/api/login` avec les credentials saisis
2. Le token cloud obtenu est stocké dans le Secure Storage Android (`cocoon_sync_token`)
3. À chaque lancement (y compris biométrique), AppLayout relit ce token et active la sync

> Le mot de passe saisi au setup doit correspondre à celui créé sur le Cloud (étape 2d).

---

## 6. Checklist avant de donner l'app à Lola

- [ ] Backend Cloud déployé et accessible (tester `https://cocoon-xxxx.laravel.cloud/api/user` avec le token)
- [ ] Migrations Cloud OK
- [ ] Seeders ExpenseCategory + Joke lancés sur Cloud
- [ ] `.env` local mis à jour avec `SYNC_API_URL`
- [ ] APK buildé en production (`NATIVEPHP_APP_VERSION="1.0.0"`, `VERSION_CODE=1`)
- [ ] APK testé sur le téléphone de Kevin (setup + biométrie + sync)
- [ ] APK installé sur le téléphone de Lola
- [ ] Compte Lola créé via `/setup` sur son téléphone
- [ ] Sync configurée sur les deux appareils
- [ ] Sync testée dans les deux sens (créer une dépense sur Kevin → sync → visible sur Lola)
- [ ] Notifications testées (créer un anniversaire → rappel reçu)

---

## 7. Développement en parallèle de la prod

### APP_ID différent pour coexistence debug/prod

Pour avoir l'app debug et l'app prod sur le même téléphone simultanément, utiliser un `APP_ID` différent en développement :

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

Android considère les deux apps comme distinctes → installation sans conflit, pas besoin de désinstaller entre les deux.

### Idées pour distinguer visuellement debug vs prod

- **Header rouge** : conditionner la couleur du header sur `APP_ENV=local` via une prop Inertia
- **Icône différente** : remplacer `ic_launcher` dans `nativephp/android/app/src/debug/res/` (dossier debug Android Studio)
- **Splash screen différent** : idem, variante dans le dossier debug

---

## 8. Workflow pour les mises à jour

```bash
# 1. Développer + tester
php artisan test --compact

# 2. Incrémenter la version dans .env
#    NATIVEPHP_APP_VERSION="1.1.0"
#    NATIVEPHP_APP_VERSION_CODE=2    ← toujours un entier qui monte

# 3. Builder
npm run build -- --mode=android
php artisan native:package android \
    --keystore=/chemin/vers/keystore \
    --keystore-password=PASSWORD \
    --key-alias=ALIAS \
    --key-password=KEY_PASSWORD

# 4. Publier (met à jour latest.json sur Cloud via storage)
php artisan app:publish-release \
    nativephp/android/app/build/outputs/apk/release/app-release.apk \
    --changelog="Ce qui a changé"

# 5. Pousser le code sur GitHub → Cloud redéploie automatiquement

# Les deux téléphones verront la mise à jour au prochain lancement
```

---

## 8. Dépannage

| Problème | Solution |
|----------|----------|
| App bloquée sur `/setup` | L'email n'est pas dans la whitelist `config/cocon.php` |
| Sync échoue (401) | Token cloud invalide — se déconnecter et se reconnecter avec email+password pour le renouveler |
| Sync échoue (réseau) | Vérifier `SYNC_API_URL` dans les paramètres de l'app |
| APK refusé à l'installation | Activer "Sources inconnues" dans Paramètres Android |
| Biométrie inactive | Désactiver puis réactiver dans les Paramètres de l'app |
| Auto-update ne détecte pas la MAJ | Vérifier que `NATIVEPHP_APP_VERSION_CODE` a bien été incrémenté |
| `latest.json` introuvable | Vérifier que `storage:link` est fait et que le volume Cloud est configuré |


remplacement build commands : 
from

```
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

npm ci --audit false
npm run build
```

to
```
composer config http-basic.plugins.nativephp.com kevininc155@gmail.com 6e68dc831d3729cc1ffe961ffefec2cfa53494e0d576d5b770acc7fb88cde25a                                                  
composer install --no-dev --no-interaction --optimize-autoloader                                                                                                                         
php artisan config:cache                                                                                                                                                                 
php artisan route:cache                                                                                                                                                                  
php artisan event:cache

```
