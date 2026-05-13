<script setup>
import AdminLayout from './AdminLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    stats: Object,
});
</script>

<template>
    <Head title="Admin Dashboard" />
    <AdminLayout>
        <div class="space-y-12">
            <div>
                <h1 class="text-4xl font-black text-[#1A1A1A]">Bienvenue, Admin</h1>
                <p class="text-gray-500 mt-2">Voici un aperçu de l'activité sur Cityplay Bénin.</p>
            </div>

            <!-- Stats Grid -->
            <div class="grid gap-8 md:grid-cols-4">
                <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100">
                    <span class="text-xs font-bold uppercase tracking-widest text-gray-400">Utilisateurs</span>
                    <div class="text-4xl font-black mt-2 text-[#1A1A1A]">{{ stats.users_count }}</div>
                </div>
                <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100">
                    <span class="text-xs font-bold uppercase tracking-widest text-gray-400">Lieux Créés</span>
                    <div class="text-4xl font-black mt-2 text-[#FF9F1C]">{{ stats.places_count }}</div>
                </div>
                <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100">
                    <span class="text-xs font-bold uppercase tracking-widest text-gray-400">Énigmes</span>
                    <div class="text-4xl font-black mt-2 text-[#1A1A1A]">{{ stats.enigmas_count }}</div>
                </div>
                <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100">
                    <span class="text-xs font-bold uppercase tracking-widest text-gray-400">Taux de Complétion</span>
                    <div class="text-4xl font-black mt-2 text-green-500">{{ stats.solved_count }}%</div>
                </div>
            </div>

            <!-- Recent Activity Placeholder -->
            <div class="bg-white p-12 rounded-3xl shadow-sm border border-gray-100">
                <h3 class="text-2xl font-bold mb-8">Activités Récentes</h3>
                <div class="space-y-6">
                    <div v-for="i in 3" :key="i" class="flex items-center justify-between py-4 border-b border-gray-50 last:border-0">
                        <div class="flex items-center gap-4">
                            <div class="h-10 w-10 bg-gray-100 rounded-full flex items-center justify-center">👤</div>
                            <div>
                                <div class="font-bold">Nouvel Utilisateur</div>
                                <div class="text-sm text-gray-400">Il y a 2 heures</div>
                            </div>
                        </div>
                        <div class="text-[#FF9F1C] font-bold">+ 0 pts</div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>