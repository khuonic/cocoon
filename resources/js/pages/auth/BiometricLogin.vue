<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import AuthBase from '@/layouts/AuthLayout.vue';
import { authenticate } from '@/services/biometric-auth';
import { Fingerprint } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';
import { verify } from '@/routes/biometric';

const error = ref('');
const attempting = ref(false);

async function attemptBiometric() {
    if (attempting.value) return;

    attempting.value = true;
    error.value = '';

    const result = await authenticate();

    if (result) {
        router.post(verify.url(), { token: result.token });
    } else {
        error.value = 'Authentification biométrique échouée.';
        attempting.value = false;
    }
}

onMounted(() => {
    attemptBiometric();
});
</script>

<template>
    <AuthBase
        title="Cocoon"
        description="Touchez pour vous connecter"
    >
        <Head title="Connexion biométrique" />

        <div class="flex flex-col items-center gap-6">
            <button
                class="flex h-24 w-24 items-center justify-center rounded-full bg-primary/10 text-primary transition-colors hover:bg-primary/20 active:bg-primary/30"
                @click="attemptBiometric"
                :disabled="attempting"
            >
                <Fingerprint class="h-12 w-12" />
            </button>

            <p v-if="error" class="text-center text-sm text-destructive">
                {{ error }}
            </p>

            <Button variant="ghost" as-child class="text-muted-foreground">
                <Link href="/login">
                    Utiliser email et mot de passe
                </Link>
            </Button>
        </div>
    </AuthBase>
</template>
