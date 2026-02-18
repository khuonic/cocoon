# Phase 20 — Module Calendrier

## Objectif
Remplacer la page Anniversaires (standalone) par un module Calendrier complet : vue mensuelle style Google Calendar, événements avec catégories colorées et rappels, anniversaires intégrés. Plugin `cocoon/local-notifications` pour les rappels Android sans serveur.

---

## 20.1 Plugin `cocoon/local-notifications`

### Principe
Plugin NativePHP local (path repository Composer) permettant de planifier des notifications locales sur Android via `AlarmManager`. Fonctionne sans serveur, offline-first.

### Structure
```
packages/cocoon/local-notifications/
├── composer.json
├── nativephp.json
├── src/
│   ├── LocalNotificationsServiceProvider.php
│   ├── Facades/
│   │   └── LocalNotification.php
│   ├── LocalNotificationManager.php
│   └── Events/
│       └── NotificationFired.php
└── resources/
    ├── android/src/
    │   └── com/cocoon/localnotifications/
    │       ├── ScheduleNotification.kt
    │       ├── CancelNotification.kt
    │       ├── CancelAllNotifications.kt
    │       └── NotificationAlarmReceiver.kt
    └── js/
        └── index.ts
```

### `composer.json` du plugin
```json
{
    "name": "cocoon/local-notifications",
    "type": "nativephp-plugin",
    "require": { "php": "^8.2" },
    "extra": {
        "laravel": {
            "providers": ["Cocoon\\LocalNotifications\\LocalNotificationsServiceProvider"]
        },
        "nativephp": { "manifest": "nativephp.json" }
    },
    "autoload": {
        "psr-4": { "Cocoon\\LocalNotifications\\": "src/" }
    }
}
```

### `nativephp.json`
```json
{
    "namespace": "CocoonLocalNotifications",
    "bridge_functions": [
        {
            "name": "LocalNotification.Schedule",
            "android": "com.cocoon.localnotifications.ScheduleNotification"
        },
        {
            "name": "LocalNotification.Cancel",
            "android": "com.cocoon.localnotifications.CancelNotification"
        },
        {
            "name": "LocalNotification.CancelAll",
            "android": "com.cocoon.localnotifications.CancelAllNotifications"
        }
    ],
    "events": [],
    "android": {
        "permissions": [
            "android.permission.POST_NOTIFICATIONS",
            "android.permission.SCHEDULE_EXACT_ALARM",
            "android.permission.RECEIVE_BOOT_COMPLETED"
        ],
        "dependencies": {}
    }
}
```

### `ScheduleNotification.kt` (Android)
- Implémente `BridgeFunction`
- Params reçus : `id` (String), `title` (String), `body` (String), `timestamp` (Long, ms depuis epoch)
- Crée un `NotificationChannel` "cocoon_reminders" si absent (Android 8+)
- Demande la permission `POST_NOTIFICATIONS` au runtime si Android 13+ (via `ActivityCompat`)
- Planifie via `AlarmManager.setExactAndAllowWhileIdle()` avec `PendingIntent` vers `NotificationAlarmReceiver`
- Persiste la notification dans `SharedPreferences` pour reprogrammation après reboot

### `CancelNotification.kt`
- Params : `id` (String)
- Annule le `PendingIntent` correspondant via `AlarmManager.cancel()`
- Retire l'entrée des `SharedPreferences`

### `CancelAllNotifications.kt`
- Annule toutes les notifications planifiées (via SharedPreferences)

### `NotificationAlarmReceiver.kt`
- `BroadcastReceiver` déclenché par l'AlarmManager
- Écoute aussi `android.intent.action.BOOT_COMPLETED` → reprogramme toutes les notifs depuis SharedPreferences
- Affiche la notification via `NotificationManagerCompat`

### `LocalNotificationManager.php`
```php
public function schedule(string $id, string $title, string $body, Carbon $at): void;
public function cancel(string $id): void;
public function cancelAll(): void;
```
Appelle les bridge functions via `nativephp_call()` (ou l'équivalent NativePHP v3).

### `Facades/LocalNotification.php`
Facade standard Laravel pointant vers `LocalNotificationManager`.

### `resources/js/index.ts`
```ts
export async function scheduleNotification(id: string, title: string, body: string, at: Date): Promise<void>
export async function cancelNotification(id: string): Promise<void>
```
Appelle `/_native/api/call` avec les params correspondants.

### Intégration dans le projet
`composer.json` du projet :
```json
"repositories": [{"type": "path", "url": "packages/cocoon/local-notifications"}],
"require": {"cocoon/local-notifications": "*"}
```
```bash
composer require cocoon/local-notifications
php artisan native:plugin:register cocoon/local-notifications
```

---

## 20.2 Base de données

### Migration : modifier `birthdays`
- Ajouter colonne `reminder_days_before` (nullable integer)
  - `null` = pas de rappel
  - `0` = rappel le jour J (à 9h00)
  - `1` = rappel la veille (à 9h00)

### Migration : créer `calendar_events`
```php
Schema::create('calendar_events', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique();
    $table->string('title');
    $table->text('description')->nullable();
    $table->string('location')->nullable();
    $table->string('category')->default('Loisir'); // enum EventCategory
    $table->dateTime('starts_at');
    $table->dateTime('ends_at')->nullable();
    $table->boolean('all_day')->default(false);
    $table->boolean('is_personal')->default(false);
    $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
    $table->integer('reminder_before')->nullable(); // minutes avant, null = pas de rappel
    $table->timestamps();
});
```

---

## 20.3 Enum

### `app/Enums/EventCategory.php` (nouveau)
```php
enum EventCategory: string {
    case Conges = 'Conges';
    case Pro = 'Pro';
    case Loisir = 'Loisir';
    case Rdv = 'Rdv';

    public function label(): string {
        return match($this) {
            self::Conges => 'Congés',
            self::Pro => 'Pro',
            self::Loisir => 'Loisirs',
            self::Rdv => 'RDV',
        };
    }

    public function color(): string {
        return match($this) {
            self::Conges => '#10B981', // vert
            self::Pro    => '#3B82F6', // bleu
            self::Loisir => '#8B5CF6', // violet
            self::Rdv    => '#F59E0B', // orange
        };
    }
}
```

---

## 20.4 Modèles

### `app/Models/CalendarEvent.php` (nouveau)
- `$fillable` : uuid, title, description, location, category, starts_at, ends_at, all_day, is_personal, user_id, reminder_before
- `$casts` : category → EventCategory, starts_at/ends_at → datetime, all_day/is_personal → boolean
- Trait `Syncable`
- Relation : `belongsTo(User::class)`

### `app/Models/Birthday.php` (modifié)
- Ajouter `reminder_days_before` dans `$fillable` et `$casts` (integer|null)

---

## 20.5 Service de rappels

### `app/Services/ReminderService.php` (nouveau)
```php
public function scheduleEventReminder(CalendarEvent $event): void;
public function cancelEventReminder(CalendarEvent $event): void;
public function scheduleBirthdayReminder(Birthday $birthday, int $year): void;
public function cancelBirthdayReminder(Birthday $birthday): void;
```

**Logique `scheduleEventReminder` :**
- Si `$event->reminder_before === null` → ne rien faire
- Calculer `$at = $event->starts_at->subMinutes($event->reminder_before)`
- Appeler `LocalNotification::schedule("event_{$event->uuid}", $event->title, "Rappel : {$event->title}", $at)`

**Logique `scheduleBirthdayReminder` :**
- Si `$birthday->reminder_days_before === null` → ne rien faire
- Calculer la date de l'anniversaire pour l'année en cours
- `$at = Carbon::parse("{$year}-{$birthday->date->format('m-d')}")->subDays($birthday->reminder_days_before)->setTime(9, 0)`
- Appeler `LocalNotification::schedule("birthday_{$birthday->uuid}", "🎂 Anniversaire", "{$birthday->name} a son anniversaire !", $at)`

---

## 20.6 Controllers

### `app/Http/Controllers/CalendarController.php` (nouveau)
- `index()` : retourner les événements du mois courant (ou `?month=YYYY-MM`) + tous les anniversaires du mois + les 2 utilisateurs (pour les filtres)
- `store()` : créer un événement → appeler `ReminderService::scheduleEventReminder()`
- `update()` : modifier → annuler l'ancien rappel + replanifier
- `destroy()` : supprimer → `ReminderService::cancelEventReminder()`

### `app/Http/Controllers/BirthdayController.php` (modifié)
- `store()` : après création → `ReminderService::scheduleBirthdayReminder()` si reminder défini
- `update()` : annuler + replanifier
- `destroy()` : `ReminderService::cancelBirthdayReminder()`

### Form Requests
- `app/Http/Requests/Calendar/StoreCalendarEventRequest.php`
- `app/Http/Requests/Calendar/UpdateCalendarEventRequest.php`
- Mettre à jour `app/Http/Requests/Birthday/StoreBirthdayRequest.php` (ajouter `reminder_days_before`)
- Mettre à jour `app/Http/Requests/Birthday/UpdateBirthdayRequest.php`

---

## 20.7 Routes

### `routes/web.php`
```php
// Calendrier
Route::get('calendar', [CalendarController::class, 'index'])->name('calendar.index');
Route::post('calendar', [CalendarController::class, 'store'])->name('calendar.store');
Route::patch('calendar/{calendar_event}', [CalendarController::class, 'update'])->name('calendar.update');
Route::delete('calendar/{calendar_event}', [CalendarController::class, 'destroy'])->name('calendar.destroy');

// Anniversaires (CRUD reste, mais plus de page index dédiée — intégrés dans Calendrier)
Route::resource('birthdays', BirthdayController::class)->only(['store', 'update', 'destroy']);
// La page /birthdays (index) reste accessible depuis "Plus" pour gestion des anniversaires
Route::get('birthdays', [BirthdayController::class, 'index'])->name('birthdays.index');
```

---

## 20.8 Frontend

### `resources/js/pages/Calendar/Index.vue` (nouvelle page principale)

**Structure générale :**
```
Header : "Calendrier"
Navigation mois : ← Février 2026 →
Filtres utilisateurs : [● Commun] [● Kevin] [● Lola]  (pills toggleables)
Filtres catégories : [Tout] [Congés] [Pro] [Loisirs] [RDV] [Anniversaires]
─────────────────────────────────────────────────────
Grille mensuelle :
  L   M   M   J   V   S   D
  .   .   .   .   .   1   2
  3   4   5   6   7   8   9   ← pastilles colorées sous chaque jour
  ...
─────────────────────────────────────────────────────
FAB : + (créer un événement)
```

**Grille mensuelle :**
- Calculée côté Vue à partir de `month` + events passés par le controller
- Chaque case de jour : numéro + max 3 pastilles colorées (par catégorie) + "+N" si plus
- Anniversaires : pastille rose `#EC4899` avec icône 🎂
- Jour courant : fond coloré (cercle primaire)
- Click sur un jour → ouvre la **Day Modal**

**Day Modal :**
- Titre : "Lundi 18 février"
- Liste des événements du jour (cards avec couleur de catégorie)
- Anniversaires en section séparée (badge rose + nom + âge)
- Bouton "+" en bas → ferme la modal + ouvre EventModal en mode création avec date pré-remplie

**EventModal (créer/modifier) :**
- Titre (input, required)
- Catégorie (4 pills colorées : Congés | Pro | Loisirs | RDV)
- Date (date picker natif HTML)
- Journée entière (toggle)
- Si pas journée entière : Heure début + Heure fin
- Lieu (input, optionnel)
- Description (textarea, optionnel)
- Rappel (select) :
  - Pas de rappel
  - Veille (1440 min)
  - Jour J à 9h (calculé dynamiquement)
  - 1h avant (60 min)
  - 30 min avant (30 min)
- Partagé / Personnel (toggle, défaut = Partagé)
- Bouton Enregistrer | Supprimer (si édition)

**Filtres utilisateurs :**
- Récupérer les 2 users depuis les props
- Chaque pill toggle l'affichage des événements de cet utilisateur
- "Commun" = is_personal false (toujours visible par défaut)

### `resources/js/pages/Birthdays/Index.vue` (modifié)
- Garder la page pour gérer la liste des anniversaires (CRUD)
- Ajouter champ `reminder_days_before` dans le formulaire : select "Pas de rappel | Veille | Jour J"
- Accessible depuis la page Plus (ajouté en Phase 19)

### `resources/js/components/BottomNav.vue`
- "Courses" (ShoppingCart, `/shopping-lists`) → "Calendrier" (CalendarDays, `/calendar`)

---

## 20.9 Sync

### `app/Services/SyncService.php`
- Ajouter `CalendarEvent::class` dans `MODEL_MAP`
- `Birthday::class` déjà présent — mettre à jour le payload pour inclure `reminder_days_before`

---

## 20.10 Tests

### `tests/Feature/Calendar/CalendarTest.php` (nouveau)
- `index` retourne les événements du mois courant
- `store` crée un événement partagé (is_personal false)
- `store` crée un événement personnel (is_personal true)
- `update` modifie un événement
- `destroy` supprime un événement
- Filtrage : événement personnel d'un autre user non visible ?

### `tests/Feature/Birthday/BirthdayTest.php` (modifié)
- Ajouter test : store avec `reminder_days_before`
- Ajouter test : update avec `reminder_days_before`

---

## Notes importantes

- **Permissions Android 12+** : `SCHEDULE_EXACT_ALARM` peut nécessiter une approbation manuelle dans les paramètres Android. Si refusée, fallback sur `setAndAllowWhileIdle()` (moins précis mais fonctionnel).
- **Permission Android 13+** : `POST_NOTIFICATIONS` doit être demandée au runtime. À gérer dans l'AppLayout au premier lancement.
- **Reboot** : le `BroadcastReceiver` sur `BOOT_COMPLETED` permet de reprogrammer les notifs après redémarrage du téléphone.
- **Anniversaires** : toujours partagés, jamais personnels.
- **Page /birthdays** : reste accessible depuis "Plus" pour la gestion. L'affichage dans le calendrier se fait via CalendarController qui les inclut dans les données du mois.
