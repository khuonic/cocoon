# Phase OPT-1 — Saisie vocale pour la liste de courses

> **Optionnel** — Implémentée après la Phase 16 (Shopping Refonte).
> Permet de dicter un article à voix haute plutôt que de le taper.

---

## Contexte

Lors de la réunion du 18/02/2026 : _"optionnel méga trop bien => développer un text to speech pour saisie automatique par vocal"_.

L'idée est d'ajouter un bouton micro dans `AddItemForm` : on appuie, on dit "bananes", et le champ se remplit automatiquement.

---

## Approche retenue : Web Speech API

**Pourquoi ?**
- Natif dans le WebView Android (moteur Chromium embarqué par NativePHP)
- Aucune dépendance externe, aucun plugin NativePHP supplémentaire
- Gratuit, utilise la reconnaissance vocale de Google côté serveur (nécessite Internet)
- Fallback propre : le bouton micro est caché si la fonctionnalité n'est pas supportée
- Langue configurable en `fr-FR`

**Limitation** : la reconnaissance vocale nécessite une connexion Internet (API Google). En mode offline, le bouton se cache ou affiche un toast explicatif.

---

## Étape 1 : Permission Android

**Modifier `config/nativephp.php`**

Ajouter la permission `RECORD_AUDIO` dans la liste des permissions Android :

```php
'android' => [
    'permissions' => [
        // ... permissions existantes
        'android.permission.RECORD_AUDIO',
    ],
],
```

> NativePHP inclut automatiquement cette permission dans le `AndroidManifest.xml` lors du build.

---

## Étape 2 : Service `speech-recognition.ts`

**Créer `resources/js/services/speech-recognition.ts`**

```ts
// Types pour la Web Speech API (non inclus dans TypeScript par défaut)
declare global {
    interface Window {
        SpeechRecognition?: typeof SpeechRecognition;
        webkitSpeechRecognition?: typeof SpeechRecognition;
    }
}

export function isSupported(): boolean {
    return !!(window.SpeechRecognition || window.webkitSpeechRecognition);
}

type RecognitionCallbacks = {
    onResult: (transcript: string, isFinal: boolean) => void;
    onEnd: () => void;
    onError: (error: string) => void;
};

export function createRecognition(callbacks: RecognitionCallbacks): SpeechRecognition | null {
    const SpeechRecognitionClass = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (!SpeechRecognitionClass) return null;

    const recognition = new SpeechRecognitionClass();
    recognition.lang = 'fr-FR';
    recognition.interimResults = true; // afficher les résultats intermédiaires
    recognition.maxAlternatives = 1;

    recognition.onresult = (event) => {
        const result = event.results[event.results.length - 1];
        const transcript = result[0].transcript.trim();
        callbacks.onResult(transcript, result.isFinal);
    };

    recognition.onend = () => callbacks.onEnd();
    recognition.onerror = (event) => callbacks.onError(event.error);

    return recognition;
}
```

---

## Étape 3 : Modification de `AddItemForm.vue`

**Modifier `resources/js/components/shopping/AddItemForm.vue`**

### Changements

1. Importer `isSupported`, `createRecognition` depuis `@/services/speech-recognition`
2. Ajouter un état `isRecording = ref(false)` et `interimText = ref('')`
3. Ajouter un bouton `Mic` / `MicOff` (Lucide) à droite du champ nom — uniquement si `isSupported()`
4. Au clic sur le bouton :
   - Si pas en cours : `recognition.start()` → `isRecording = true`
   - Si en cours : `recognition.stop()` → `isRecording = false`
5. `onResult` : si intermédiaire → afficher dans `interimText` (placeholder animé) ; si final → `form.name = transcript`
6. `onEnd` : `isRecording = false`
7. `onError` : si `network` → toast "Connexion requise pour la saisie vocale" ; sinon stopper silencieusement

### Exemple d'UI

```
[ Ajouter un article...  🎤 ] [ Catégorie ▾ ] [+]
                  ↑
         Bouton micro intégré dans l'input (à droite)
         Rouge animé quand en cours d'écoute
```

### Code conceptuel

```vue
<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { Mic, MicOff } from 'lucide-vue-next';
import { isSupported, createRecognition } from '@/services/speech-recognition';

const voiceSupported = ref(false);
const isRecording = ref(false);
let recognition: SpeechRecognition | null = null;

onMounted(() => {
    voiceSupported.value = isSupported();
    if (voiceSupported.value) {
        recognition = createRecognition({
            onResult: (transcript, isFinal) => {
                if (isFinal) {
                    form.name = transcript;
                    isRecording.value = false;
                }
            },
            onEnd: () => { isRecording.value = false; },
            onError: () => { isRecording.value = false; },
        });
    }
});

onUnmounted(() => {
    recognition?.stop();
});

function toggleRecording(): void {
    if (isRecording.value) {
        recognition?.stop();
    } else {
        form.name = '';
        recognition?.start();
        isRecording.value = true;
    }
}
</script>

<template>
    <!-- Dans le formulaire, à côté de l'Input nom -->
    <Button
        v-if="voiceSupported"
        type="button"
        variant="ghost"
        size="icon"
        :class="isRecording ? 'text-destructive animate-pulse' : 'text-muted-foreground'"
        @click="toggleRecording"
    >
        <MicOff v-if="isRecording" :size="18" />
        <Mic v-else :size="18" />
    </Button>
</template>
```

---

## Étape 4 : Tests

> Pas de test automatisé pour la Web Speech API (API navigateur non mockable facilement en Pest).
> **Test manuel** : vérifier que le bouton apparaît sur Android, que la reconnaissance fonctionne en FR, que le champ se remplit correctement, et que le bouton est absent si l'API n't est pas supportée.

---

## Fichiers créés/modifiés

| Action | Fichier |
|--------|---------|
| Modifier | `config/nativephp.php` — ajout `RECORD_AUDIO` |
| Créer | `resources/js/services/speech-recognition.ts` |
| Modifier | `resources/js/components/shopping/AddItemForm.vue` |

---

## Notes

- Le bouton micro est **invisible en web classique** si `SpeechRecognition` n'est pas disponible (pas de pollution de l'UI desktop/navigateur non supporté)
- En cas d'erreur réseau, afficher un toast discret (à implémenter avec une solution de notification légère ou simplement un `console.warn`)
- Possibilité future d'étendre à d'autres champs de saisie (notes, tâches)
- Si NativePHP Mobile ajoute un jour une API Microphone + transcription offline, on pourra remplacer la Web Speech API facilement
