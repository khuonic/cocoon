# Plan — Saisie vocale articles de courses (Option A)

## Problème actuel

`webkitSpeechRecognition` retourne `not-allowed` dans le WebView NativePHP car le `WebChromeClient` n'implémente pas `onPermissionRequest`. L'API existe mais la permission est auto-refusée par Android.

## Solution

Remplacer la Web Speech API par :
1. **`nativephp/microphone`** — plugin natif pour enregistrer l'audio sur l'appareil
2. **OpenAI Whisper API** (ou équivalent) — transcription serveur du fichier audio
3. **Endpoint Laravel** — reçoit le fichier, appelle Whisper, retourne le transcript

---

## Étapes

### 1. Installer le plugin `nativephp/microphone`

```bash
composer require nativephp/microphone
php artisan native:plugin:register nativephp/microphone --no-interaction
php artisan native:run android --build
```

Vérifier dans `NativeServiceProvider::plugins()` :
```php
\Native\Mobile\Providers\MicrophoneServiceProvider::class,
```

### 2. Créer l'endpoint de transcription

```bash
php artisan make:controller Api/TranscribeController --no-interaction
```

`POST /api/transcribe` — accepte un fichier audio, appelle OpenAI Whisper, retourne le texte.

```php
// app/Http/Controllers/Api/TranscribeController.php
public function __invoke(Request $request): JsonResponse
{
    $request->validate(['audio' => 'required|file|mimes:m4a,mp4,webm,ogg,wav|max:10240']);

    $response = Http::withToken(config('services.openai.key'))
        ->attach('file', file_get_contents($request->file('audio')->path()), 'audio.m4a')
        ->post('https://api.openai.com/v1/audio/transcriptions', [
            'model' => 'whisper-1',
            'language' => 'fr',
        ]);

    return response()->json(['transcript' => $response->json('text')]);
}
```

Ajouter dans `config/services.php` :
```php
'openai' => ['key' => env('OPENAI_API_KEY')],
```

Ajouter `OPENAI_API_KEY=sk-...` dans `.env`.

Route dans `routes/api.php` :
```php
Route::post('/transcribe', Api\TranscribeController::class)->middleware('auth:sanctum');
```

### 3. Créer le service JS de transcription

`resources/js/services/voice-transcribe.ts`

```typescript
// Utilise window.Native.on pour écouter l'event MicrophoneRecorded
// Enregistre via nativeCall('Microphone.Record', { ... })
// Upload le fichier via fetch POST /api/transcribe
// Retourne le transcript
```

Flow complet :
1. Tap FAB → `nativeCall('Microphone.Record', { prompt: 'Dites le nom de l\'article' })`
2. Écoute event `Native\Mobile\Events\Microphone\MicrophoneRecorded` sur `window.Native`
3. Le payload contient le `path` du fichier audio sur l'appareil
4. Lire le fichier via `nativeCall('File.Read', { path })` ou URL locale
5. POST `/api/transcribe` avec le fichier audio
6. Ajouter l'article avec le transcript retourné

### 4. Modifier `Shopping/Show.vue`

Remplacer le bloc `webkitSpeechRecognition` par l'appel au nouveau service.

```typescript
import { transcribeVoice } from '@/services/voice-transcribe';

async function toggleListening(): Promise<void> {
    if (isListening.value) return;
    isListening.value = true;
    try {
        const transcript = await transcribeVoice();
        if (transcript) {
            router.post(store.url(props.shoppingList.id), { name: transcript, category: null }, { preserveScroll: true });
        }
    } finally {
        isListening.value = false;
    }
}
```

### 5. Tests

- `tests/Feature/Api/TranscribeControllerTest.php` — mock Http::fake() pour Whisper
- Test: fichier manquant → 422, fichier trop grand → 422, succès → transcript retourné

---

## Dépendances

| Élément | Détail |
|---------|--------|
| Plugin | `nativephp/microphone` (composer) |
| API | OpenAI Whisper (`whisper-1`) — ~0.006$/minute audio |
| Env | `OPENAI_API_KEY` dans `.env` |
| Build | `native:run android --build` requis après installation plugin |

## Notes

- Le plugin microphone est Android + iOS (contrairement à local-notifications qui est Android only)
- Whisper supporte très bien le français avec `language: 'fr'`
- Alternative gratuite : `openai/whisper` auto-hébergé (plus complexe)
- Vérifier la doc NativePHP Microphone avant de coder : `https://nativephp.com/docs/mobile/2/apis/microphone`
