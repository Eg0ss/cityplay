<script setup>
import Modal from '@/Components/Modal.vue';
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({ password: '' });

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;
    nextTick(() => passwordInput.value.focus());
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;
    form.clearErrors();
    form.reset();
};
</script>

<template>
    <section class="space-y-6">
        <header class="mb-8">
            <h2 class="text-xl font-black uppercase italic tracking-tighter text-red-400">
                ⚠️ Zone <span class="text-red-500">Dangereuse</span>
            </h2>
            <p class="text-[10px] font-bold uppercase tracking-widest text-gray-500 mt-2">
                La suppression de votre compte est irréversible — toutes vos données seront perdues
            </p>
        </header>

        <button
            @click="confirmUserDeletion"
            class="btn-3d btn-3d-red px-8 py-3 text-xs font-black uppercase tracking-widest shadow-[0_4px_0_#9e2318]"
        >
            🗑️ Supprimer mon compte
        </button>

        <!-- Modal de confirmation -->
        <Modal :show="confirmingUserDeletion" @close="closeModal">
            <div class="bg-[#0D0E18] border border-red-500/30 rounded-3xl p-8 text-center">
                <!-- Icône -->
                <div class="text-5xl mb-4">💀</div>

                <h2 class="text-2xl font-black uppercase italic tracking-tighter text-red-400 mb-2">
                    Suppression du compte
                </h2>
                <p class="text-sm text-gray-400 font-semibold leading-relaxed mb-8 max-w-md mx-auto">
                    Cette action est <span class="text-red-400 font-black">irréversible</span>. Toutes vos données, parties, et progression seront définitivement supprimées. Confirmez avec votre mot de passe.
                </p>

                <!-- Input mot de passe -->
                <div class="mb-6 text-left">
                    <label class="block text-[10px] font-black uppercase tracking-widest text-gray-400 mb-2">
                        Mot de passe de confirmation
                    </label>
                    <input
                        id="password"
                        ref="passwordInput"
                        v-model="form.password"
                        type="password"
                        placeholder="Votre mot de passe..."
                        @keyup.enter="deleteUser"
                        class="w-full bg-[#171235] border-2 border-red-500/30 focus:border-red-500 focus:ring-0 rounded-xl px-4 py-3 text-sm text-white font-bold placeholder-gray-600 transition-colors outline-none"
                    />
                    <p v-if="form.errors.password" class="mt-2 text-xs text-red-400 font-bold">{{ form.errors.password }}</p>
                </div>

                <!-- Boutons -->
                <div class="flex gap-4 justify-center">
                    <button
                        @click="closeModal"
                        class="px-6 py-3 rounded-xl font-black uppercase text-[10px] tracking-widest bg-[#1c183a] border border-[#2a245c] text-white hover:text-[#87d74e] transition-colors"
                    >
                        Annuler
                    </button>
                    <button
                        @click="deleteUser"
                        :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
                        :disabled="form.processing"
                        class="btn-3d btn-3d-red px-6 py-3 text-[10px] font-black uppercase tracking-widest shadow-[0_4px_0_#9e2318]"
                    >
                        ✅ Confirmer la suppression
                    </button>
                </div>
            </div>
        </Modal>
    </section>
</template>
