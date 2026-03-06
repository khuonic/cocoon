<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { update } from '@/actions/App/Http/Controllers/Settings/ProfileController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { mobilePatchForm } from '@/lib/form-helpers';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';

const page = usePage();
const user = page.props.auth.user;

const form = useForm({
    name: user.name,
});

function submit(): void {
    mobilePatchForm(form, update.url(), {
        preserveScroll: true,
    });
}
</script>

<template>
    <AppLayout>
        <Head title="Paramètres du profil" />

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <Heading
                    variant="small"
                    title="Informations du profil"
                    description="Modifiez votre nom"
                />

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid gap-2">
                        <Label for="name">Nom</Label>
                        <Input
                            id="name"
                            v-model="form.name"
                            type="text"
                            required
                            autocomplete="name"
                            placeholder="Nom complet"
                        />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">Adresse email</Label>
                        <Input
                            id="email"
                            type="email"
                            :default-value="user.email"
                            disabled
                            class="bg-muted"
                        />
                        <p class="text-xs text-muted-foreground">
                            L'adresse email ne peut pas être modifiée.
                        </p>
                    </div>

                    <div class="flex items-center gap-4">
                        <Button type="submit" :disabled="form.processing" data-test="update-profile-button">
                            Enregistrer
                        </Button>
                        <Transition enter-active-class="transition ease-in-out" enter-from-class="opacity-0" leave-active-class="transition ease-in-out" leave-to-class="opacity-0">
                            <p v-show="form.recentlySuccessful" class="text-sm text-neutral-600">Enregistré.</p>
                        </Transition>
                    </div>
                </form>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
