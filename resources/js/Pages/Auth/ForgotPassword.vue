<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <GuestLayout>
        <Head title="Récupération de Code" />

        <div class="mb-6 text-sm dark:text-gray-400 text-gray-600 font-medium leading-relaxed">
            Vous avez perdu votre code d'accès ? Pas de panique, explorateur. Indiquez-nous votre adresse Matrix (email) et nous vous enverrons un lien de réinitialisation sécurisé.
        </div>

        <div
            v-if="status"
            class="mb-6 p-4 bg-green-500/10 border border-green-500/20 rounded-xl text-xs font-black uppercase tracking-widest text-green-500"
        >
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <div>
                <InputLabel for="email" value="Canal de Liaison (Email)" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="votre@email.com"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="flex items-center justify-end">
                <PrimaryButton
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                    class="w-full"
                >
                    Envoyer le lien de secours
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
