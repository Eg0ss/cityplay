<script setup>
import InputError from '@/Components/InputError.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: { type: Boolean },
    status: { type: String },
});

const user = usePage().props.auth.user;
const form = useForm({
    name: user.name,
    email: user.email,
});
</script>

<template>
    <section>
        <header class="mb-8">
            <h2 class="text-xl font-black uppercase italic tracking-tighter text-white">
                🧑 Informations du <span class="text-[#87d74e]">Profil</span>
            </h2>
            <p class="text-[10px] font-bold uppercase tracking-widest text-gray-500 mt-2">
                Modifiez votre pseudo et votre adresse email
            </p>
        </header>

        <form @submit.prevent="form.patch(route('profile.update'))" class="space-y-6">
            <!-- Pseudo -->
            <div class="space-y-2">
                <label for="name" class="block text-[10px] font-black uppercase tracking-widest text-gray-400">
                    Pseudo
                </label>
                <input
                    id="name"
                    type="text"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                    class="w-full bg-[#0D0E18] border-2 border-[#2a245c] focus:border-[#87d74e] focus:ring-0 rounded-xl px-4 py-3 text-sm text-white font-bold placeholder-gray-600 transition-colors outline-none"
                />
                <p v-if="form.errors.name" class="text-xs text-red-400 font-bold">{{ form.errors.name }}</p>
            </div>

            <!-- Email -->
            <div class="space-y-2">
                <label for="email" class="block text-[10px] font-black uppercase tracking-widest text-gray-400">
                    Adresse Email
                </label>
                <input
                    id="email"
                    type="email"
                    v-model="form.email"
                    required
                    autocomplete="username"
                    class="w-full bg-[#0D0E18] border-2 border-[#2a245c] focus:border-[#87d74e] focus:ring-0 rounded-xl px-4 py-3 text-sm text-white font-bold placeholder-gray-600 transition-colors outline-none"
                />
                <p v-if="form.errors.email" class="text-xs text-red-400 font-bold">{{ form.errors.email }}</p>
            </div>

            <!-- Vérification email -->
            <div v-if="mustVerifyEmail && user.email_verified_at === null" class="bg-yellow-500/10 border border-yellow-500/30 rounded-xl p-4">
                <p class="text-xs font-bold text-yellow-400">
                    ⚠️ Votre adresse email n'est pas vérifiée.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="underline text-[#87d74e] hover:text-white ml-1 transition-colors"
                    >
                        Renvoyer l'email de vérification
                    </Link>
                </p>
                <p v-show="status === 'verification-link-sent'" class="mt-2 text-xs font-bold text-[#87d74e]">
                    ✅ Un nouveau lien de vérification a été envoyé.
                </p>
            </div>

            <!-- Bouton sauvegarde -->
            <div class="flex items-center gap-4 pt-2">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="btn-3d btn-3d-green px-8 py-3 text-xs font-black uppercase tracking-widest shadow-[0_4px_0_#1e7d4b] disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    💾 Sauvegarder
                </button>
                <Transition
                    enter-active-class="transition ease-in-out duration-300"
                    enter-from-class="opacity-0 translate-x-2"
                    leave-active-class="transition ease-in-out duration-200"
                    leave-to-class="opacity-0"
                >
                    <span v-if="form.recentlySuccessful" class="text-xs font-black text-[#87d74e] uppercase tracking-widest">
                        ✅ Sauvegardé !
                    </span>
                </Transition>
            </div>
        </form>
    </section>
</template>
