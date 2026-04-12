<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthBase from '@/layouts/AuthLayout.vue';
import { hasSavedCredentials, isNativePHP, markCredentialsSaved } from '@/services/biometric-auth';
import { store } from '@/routes/login';
import { onMounted } from 'vue';

defineProps<{
    status?: string;
}>();

const nativePHP = ref(isNativePHP());

onMounted(async () => {
    if (!isNativePHP()) return;

    if (hasSavedCredentials()) {
        router.visit('/biometric-login');
        return;
    }

    // Fallback : localStorage effacé mais SecureStorage a peut-être encore le token
    try {
        const res = await fetch('/biometric-available', { headers: { Accept: 'application/json' } });
        if (res.ok) {
            const { available } = (await res.json()) as { available: boolean };
            if (available) {
                markCredentialsSaved();
                router.visit('/biometric-login');
            }
        }
    } catch {
        // ignore — l'utilisateur reste sur le login classique
    }
});
</script>

<template>
    <AuthBase>
        <Head title="Connexion" />

        <div class="mb-8 flex flex-col items-center">
            <img src="/icon_login.png" alt="Cocoon" class="size-36" />
        </div>

        <div
            v-if="status"
            class="mb-4 text-center text-sm font-medium text-green-600"
        >
            {{ status }}
        </div>

        <Form
            v-bind="store.form()"
            :reset-on-success="['password']"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-6"
        >
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="email">Adresse email</Label>
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="email"
                        placeholder="email@example.com"
                    />
                    <InputError :message="errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="password">Mot de passe</Label>
                    <Input
                        id="password"
                        type="password"
                        name="password"
                        required
                        :tabindex="2"
                        autocomplete="current-password"
                        placeholder="Mot de passe"
                    />
                    <InputError :message="errors.password" />
                </div>

                <div v-if="!nativePHP" class="flex items-center justify-between">
                    <Label for="remember" class="flex items-center space-x-3">
                        <Checkbox id="remember" name="remember" :tabindex="3" />
                        <span>Se souvenir de moi</span>
                    </Label>
                </div>

                <Button
                    type="submit"
                    class="mt-4 w-full"
                    :tabindex="4"
                    :disabled="processing"
                    data-test="login-button"
                >
                    <Spinner v-if="processing" />
                    Se connecter
                </Button>
            </div>

            <!-- Registration disabled -->
        </Form>
    </AuthBase>
</template>
