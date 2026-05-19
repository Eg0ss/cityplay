<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});
</script>

<template>
    <Head title="Paramètres du Profil" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-4xl lg:text-6xl font-black tracking-tighter uppercase italic text-white leading-none">
                CONFIG <span class="text-[#87d74e]">PROFIL</span>
            </h2>
            <p class="text-[10px] font-black uppercase tracking-[0.3em] text-gray-500 mt-4">Gestion des paramètres de sécurité et d'identité</p>
        </template>

        <div class="space-y-6">
            <!-- Infos profil -->
            <div class="bg-[#10101c] p-6 lg:p-10 rounded-[2rem] border border-[#2a245c]">
                <UpdateProfileInformationForm
                    :must-verify-email="mustVerifyEmail"
                    :status="status"
                    class="max-w-2xl"
                />
            </div>

            <!-- Mot de passe -->
            <div class="bg-[#10101c] p-6 lg:p-10 rounded-[2rem] border border-[#2a245c]">
                <UpdatePasswordForm class="max-w-2xl" />
            </div>

            <!-- Zone de danger -->
            <div class="bg-red-950/30 p-6 lg:p-10 rounded-[2rem] border border-red-500/20">
                <DeleteUserForm class="max-w-2xl" />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
