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
            <h2 class="text-4xl lg:text-6xl font-black tracking-tighter uppercase italic dark:text-white text-gray-900 leading-none">
                CONFIG <span class="text-[#FF9F1C]">PROFIL</span>
            </h2>
            <p class="text-[10px] font-black uppercase tracking-[0.3em] text-gray-500 mt-4">Gestion des paramètres de sécurité et d'identité</p>
        </template>

        <div class="space-y-8">
            <div class="dark:bg-[#111113] bg-white p-6 lg:p-10 rounded-[2.5rem] shadow-2xl border dark:border-white/5 border-gray-100">
                <UpdateProfileInformationForm
                    :must-verify-email="mustVerifyEmail"
                    :status="status"
                    class="max-w-2xl"
                />
            </div>

            <div class="dark:bg-[#111113] bg-white p-6 lg:p-10 rounded-[2.5rem] shadow-2xl border dark:border-white/5 border-gray-100">
                <UpdatePasswordForm class="max-w-2xl" />
            </div>

            <div class="dark:bg-red-500/5 bg-red-50 p-6 lg:p-10 rounded-[2.5rem] shadow-2xl border border-red-500/20">
                <DeleteUserForm class="max-w-2xl" />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
