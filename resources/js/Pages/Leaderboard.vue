<script setup>
import PageTemplate from './PageTemplate.vue';

const players = [
    { rank: 1, name: "Koffi L.", points: 1250, solved: 24 },
    { rank: 2, name: "Sika A.", points: 1100, solved: 21 },
    { rank: 3, name: "Yao B.", points: 950, solved: 18 },
    { rank: 4, name: "Mireille T.", points: 880, solved: 17 },
    { rank: 5, name: "Dossou P.", points: 720, solved: 14 },
    { rank: 6, name: "Amadou M.", points: 650, solved: 12 },
    { rank: 7, name: "Evelyne K.", points: 590, solved: 11 },
];
</script>

<template>
    <PageTemplate title="Classement des Explorateurs" subtitle="Découvrez qui sont les meilleurs solveurs d'énigmes du Bénin.">
        <div class="bg-white rounded-3xl shadow-xl overflow-hidden border border-gray-100">
            <table class="w-full text-left">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="px-8 py-6 text-sm font-bold uppercase tracking-widest text-gray-400">Rang</th>
                        <th class="px-8 py-6 text-sm font-bold uppercase tracking-widest text-gray-400">Explorateur</th>
                        <th class="px-8 py-6 text-sm font-bold uppercase tracking-widest text-gray-400">Points</th>
                        <th class="px-8 py-6 text-sm font-bold uppercase tracking-widest text-gray-400">Énigmes Résolues</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="player in players" :key="player.rank" class="hover:bg-gray-50 transition-colors">
                        <td class="px-8 py-6">
                            <span v-if="player.rank <= 3" class="h-8 w-8 rounded-full bg-[#FF9F1C] text-white flex items-center justify-center font-bold">{{ player.rank }}</span>
                            <span v-else class="px-3 font-bold text-gray-400">{{ player.rank }}</span>
                        </td>
                        <td class="px-8 py-6 font-bold text-lg">{{ player.name }}</td>
                        <td class="px-8 py-6">
                            <span class="text-[#FF9F1C] font-black">{{ player.points }} pts</span>
                        </td>
                        <td class="px-8 py-6 text-gray-500">{{ player.solved }} énigmes</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </PageTemplate>
</template>
