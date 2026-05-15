<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

const players = [
    { rank: 1, name: "Koffi l'Explorateur", points: 12500, solved: 45, avatar: "👑", level: "Légende" },
    { rank: 2, name: "Awa du Nord", points: 11200, solved: 42, avatar: "🥈", level: "Elite" },
    { rank: 3, name: "Sami le Guerrier", points: 9800, solved: 38, avatar: "🥉", level: "Elite" },
    { rank: 4, name: "Bao Benin", points: 8500, solved: 30, avatar: "👤", level: "Vétéran" },
    { rank: 5, name: "Ouidah Queen", points: 7900, solved: 28, avatar: "👤", level: "Vétéran" },
    { rank: 6, name: "Dahomey King", points: 7200, solved: 25, avatar: "👤", level: "Explorateur" },
];
</script>

<template>
    <Head title="Classement Mondial" />
    <AuthenticatedLayout>
        <div class="space-y-12">
            <!-- Header -->
            <div class="text-center lg:text-left">
                <h1 class="text-4xl lg:text-7xl font-black tracking-tighter uppercase italic leading-none dark:text-white text-gray-900 mb-6">
                    Hall of <span class="text-[#FF9F1C]">Fame</span>
                </h1>
                <p class="text-lg font-medium dark:text-gray-400 text-gray-600 max-w-2xl">
                    Les meilleurs explorateurs du Bénin. Serez-vous le prochain à dominer la matrice ?
                </p>
            </div>

            <!-- Leaderboard Table -->
            <div class="dark:bg-[#111113] bg-white rounded-[2.5rem] border dark:border-white/5 border-gray-100 shadow-2xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="dark:bg-white/2 bg-gray-50 border-b dark:border-white/5 border-gray-100">
                                <th class="px-8 py-8 text-[10px] font-black uppercase tracking-[0.3em] text-gray-400 text-center w-24">Rang</th>
                                <th class="px-8 py-8 text-[10px] font-black uppercase tracking-[0.3em] text-gray-400">Joueur</th>
                                <th class="px-8 py-8 text-[10px] font-black uppercase tracking-[0.3em] text-gray-400 text-right">Score Global</th>
                                <th class="px-8 py-8 text-[10px] font-black uppercase tracking-[0.3em] text-gray-400 text-right hidden md:table-cell">Quêtes</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y dark:divide-white/5 divide-gray-100">
                            <tr v-for="player in players" :key="player.rank" 
                                class="hover:dark:bg-white/2 hover:bg-gray-50 transition-all group">
                                <td class="px-8 py-8 text-center">
                                    <div class="relative inline-block">
                                        <span class="text-3xl font-black italic tracking-tighter" 
                                              :class="player.rank <= 3 ? 'text-[#FF9F1C]' : 'dark:text-white/20 text-gray-300'">
                                            #{{ player.rank }}
                                        </span>
                                        <div v-if="player.rank === 1" class="absolute -top-4 -right-4 text-xl rotate-12 animate-pulse">✨</div>
                                    </div>
                                </td>
                                <td class="px-8 py-8">
                                    <div class="flex items-center gap-6">
                                        <div class="h-14 w-14 dark:bg-white/5 bg-gray-100 rounded-2xl flex items-center justify-center text-2xl group-hover:scale-110 transition-transform border dark:border-white/10 border-gray-200 shadow-lg">
                                            {{ player.avatar }}
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-xl font-black italic tracking-tighter uppercase dark:text-white text-gray-900">{{ player.name }}</span>
                                            <span class="text-[9px] font-black uppercase tracking-widest text-[#FF9F1C]">{{ player.level }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-8 text-right">
                                    <div class="flex flex-col items-end">
                                        <span class="text-2xl font-black text-[#FF9F1C] tabular-nums">{{ player.points.toLocaleString() }}</span>
                                        <span class="text-[8px] font-black uppercase tracking-widest text-gray-500">Points XP</span>
                                    </div>
                                </td>
                                <td class="px-8 py-8 text-right hidden md:table-cell">
                                    <span class="text-xl font-black dark:text-white text-gray-900 tabular-nums">{{ player.solved }}</span>
                                    <span class="ml-2 text-[10px] font-bold text-gray-500 uppercase tracking-widest">Actes</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Call to Action -->
            <div class="flex flex-col lg:flex-row items-center justify-between p-10 lg:p-16 dark:bg-[#FF9F1C]/10 bg-[#FF9F1C]/5 rounded-[3rem] border-2 border-dashed border-[#FF9F1C]/20 gap-8">
                <div class="text-center lg:text-left">
                    <h3 class="text-3xl font-black uppercase italic tracking-tighter dark:text-white text-gray-900 mb-2">Prêt à relever le défi ?</h3>
                    <p class="font-medium text-gray-500">Démarrez une nouvelle quête et grimpez dans le classement.</p>
                </div>
                <Link :href="route('places.index')" class="bg-[#FF9F1C] text-black px-12 py-6 rounded-2xl font-black uppercase tracking-widest text-xs shadow-xl hover:scale-105 transition-all">
                    Lancer une Mission
                </Link>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
