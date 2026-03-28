<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import { store } from '@/actions/App/Http/Controllers/ShoppingListController';

const form = useForm({
    name: '',
});

function submit(): void {
    form.post(store.url());
}
</script>

<template>
    <AppLayout title="Nouvelle liste">
        <Head title="Nouvelle liste" />

        <form @submit.prevent="submit" class="space-y-6 p-4">
            <div class="space-y-2">
                <Label for="name">Nom de la liste</Label>
                <Input
                    id="name"
                    v-model="form.name"
                    type="text"
                    placeholder="Ex: Courses de la semaine"
                    required
                    autofocus
                />
                <InputError :message="form.errors.name" />
            </div>

            <Button
                type="submit"
                class="w-full"
                size="lg"
                :disabled="form.processing"
            >
                Créer la liste
            </Button>
        </form>
    </AppLayout>
</template>
