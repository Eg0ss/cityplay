<script setup>
import InputError from '@/Components/InputError.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value.focus();
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value.focus();
            }
        },
    });
};
</script>

<template>
    <section>
        <header class="mb-8">
            <h2 class="text-xl font-black uppercase italic tracking-tighter text-white">
                🔒 Changer le <span class="text-[#2c72f6]">Mot de Passe</span>
            </h2>
            <p class="text-[10px] font-bold uppercase tracking-widest text-gray-500 mt-2">
                Utilisez un mot de passe long et aléatoire pour sécuriser votre compte
            </p>
        </header>

        <form @submit.prevent="updatePassword" class="space-y-6">
            <!-- Mot de passe actuel -->
            <div class="space-y-2">
                <label for="current_password" class="block text-[10px] font-black uppercase tracking-widest text-gray-400">
                    Mot de passe actuel
                </label>
                <input
                    id="current_password"
                    ref="currentPasswordInput"
                    v-model="form.current_password"
                    type="password"
                    autocomplete="current-password"
                    class="w-full bg-[#0D0E18] border-2 border-[#2a245c] focus:border-[#2c72f6] focus:ring-0 rounded-xl px-4 py-3 text-sm text-white font-bold placeholder-gray-600 transition-colors outline-none"
                />
                <p v-if="form.errors.current_password" class="text-xs text-red-400 font-bold">{{ form.errors.current_password }}</p>
            </div>

            <!-- Nouveau mot de passe -->
            <div class="space-y-2">
                <label for="password" class="block text-[10px] font-black uppercase tracking-widest text-gray-400">
                    Nouveau mot de passe
                </label>
                <input
                    id="password"
                    ref="passwordInput"
                    v-model="form.password"
                    type="password"
                    autocomplete="new-password"
                    class="w-full bg-[#0D0E18] border-2 border-[#2a245c] focus:border-[#2c72f6] focus:ring-0 rounded-xl px-4 py-3 text-sm text-white font-bold placeholder-gray-600 transition-colors outline-none"
                />
                <p v-if="form.errors.password" class="text-xs text-red-400 font-bold">{{ form.errors.password }}</p>
            </div>

            <!-- Confirmer mot de passe -->
            <div class="space-y-2">
                <label for="password_confirmation" class="block text-[10px] font-black uppercase tracking-widest text-gray-400">
                    Confirmer le mot de passe
                </label>
                <input
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    autocomplete="new-password"
                    class="w-full bg-[#0D0E18] border-2 border-[#2a245c] focus:border-[#2c72f6] focus:ring-0 rounded-xl px-4 py-3 text-sm text-white font-bold placeholder-gray-600 transition-colors outline-none"
                />
                <p v-if="form.errors.password_confirmation" class="text-xs text-red-400 font-bold">{{ form.errors.password_confirmation }}</p>
            </div>

            <!-- Bouton -->
            <div class="flex items-center gap-4 pt-2">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="btn-3d btn-3d-blue px-8 py-3 text-xs font-black uppercase tracking-widest shadow-[0_4px_0_#1344a1] disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    🔑 Mettre à jour
                </button>
                <Transition
                    enter-active-class="transition ease-in-out duration-300"
                    enter-from-class="opacity-0 translate-x-2"
                    leave-active-class="transition ease-in-out duration-200"
                    leave-to-class="opacity-0"
                >
                    <span v-if="form.recentlySuccessful" class="text-xs font-black text-[#87d74e] uppercase tracking-widest">
                        ✅ Mot de passe mis à jour !
                    </span>
                </Transition>
            </div>
        </form>
    </section>
</template>
