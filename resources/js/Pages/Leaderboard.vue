<script setup>
import PageTemplate from './PageTemplate.vue';

const players = [
    { rank: 1, name: "Koffi l'Explorateur", points: 12500, solved: 45, avatar: "👑" },
    { rank: 2, name: "Awa du Nord", points: 11200, solved: 42, avatar: "🥈" },
    { rank: 3, name: "Sami le Guerrier", points: 9800, solved: 38, avatar: "🥉" },
    { rank: 4, name: "Bao Benin", points: 8500, solved: 30, avatar: "👤" },
    { rank: 5, name: "Ouidah Queen", points: 7900, solved: 28, avatar: "👤" },
    { rank: 6, name: "Dahomey King", points: 7200, solved: 25, avatar: "👤" },
];
</script>

<template>
    <PageTemplate title="CLASSEMENT MONDIAL" subtitle="Les meilleurs explorateurs du Bénin. Serez-vous le prochain à dominer le classement ?">
        <div class="bg-white rounded-[3rem] border border-gray-100 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100">
                            <th class="px-12 py-8 text-[10px] font-black uppercase tracking-[0.2em] text-gray-400 text-center w-24">Rang</th>
                            <th class="px-12 py-8 text-[10px] font-black uppercase tracking-[0.2em] text-gray-400">Joueur</th>
                            <th class="px-12 py-8 text-[10px] font-black uppercase tracking-[0.2em] text-gray-400 text-right">Points</th>
                            <th class="px-12 py-8 text-[10px] font-black uppercase tracking-[0.2em] text-gray-400 text-right">Énigmes Résolues</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="player in players" :key="player.rank" class="hover:bg-gray-50 transition-colors group">
                            <td class="px-12 py-8 text-center">
                                <span class="text-2xl font-black italic tracking-tighter" :class="{'text-[#FF9F1C]': player.rank <= 3}">#{{ player.rank }}</span>
                            </td>
                            <td class="px-12 py-8">
                                <div class="flex items-center gap-6">
                                    <div class="h-14 w-14 bg-gray-50 rounded-2xl flex items-center justify-center text-2xl group-hover:scale-110 transition-transform">
                                        {{ player.avatar }}
                                    </div>
                                    <span class="text-xl font-bold italic tracking-tighter uppercase">{{ player.name }}</span>
                                </div>
                            </td>
                            <td class="px-12 py-8 text-right font-black text-[#FF9F1C] text-xl">
                                {{ player.points.toLocaleString() }}
                            </td>
                            <td class="px-12 py-8 text-right font-bold text-gray-500 text-lg">
                                {{ player.solved }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </PageTemplate>
</template>
