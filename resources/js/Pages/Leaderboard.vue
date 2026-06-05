<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Trophy, Star, Zap, Gamepad2 } from 'lucide-vue-next';

// Props depuis le controller (à brancher plus tard sur de vraies données)
defineProps({
    topPlayers: { type: Array, default: () => [] },
});

// Données de démo si pas encore branchées au backend
const demoPlayers = [
    { rank: 1, name: "Koffi l'Explorateur", points: 1250000, solved: 45, level: 'Légende du Bénin 👑' },
    { rank: 2, name: "Awa du Nord",          points: 1120000, solved: 42, level: 'Passionné d\'XP ⚡'   },
    { rank: 3, name: "Sami le Guerrier",     points: 980000,  solved: 38, level: 'Chasseur 🕵️'         },
    { rank: 4, name: "Bao Bénin",            points: 850000,  solved: 30, level: 'Chasseur 🕵️'         },
    { rank: 5, name: "Ouidah Queen",         points: 790000,  solved: 28, level: 'Explorateur 🦁'       },
    { rank: 6, name: "Dahomey King",         points: 720000,  solved: 25, level: 'Explorateur 🦁'       },
];
</script>

<template>
    <AuthenticatedLayout title="Classement">

        <div class="flex flex-col gap-5 animate-fade-in-up">

            <!-- Header -->
            <div class="text-center sm:text-left">
                <span class="text-[9px] font-black tracking-[0.3em] text-[#ffc628] uppercase italic block mb-1">Compétition mondiale</span>
                <h1 class="text-2xl sm:text-3xl font-black uppercase italic tracking-tighter text-white flex items-center gap-2 justify-center sm:justify-start">
                    <Trophy :size="24" class="text-[#ffc628]" /> Hall of <span class="text-[#ffc628]">Fame</span>
                </h1>
                <p class="text-[11px] text-gray-400 font-semibold mt-1">
                    Les meilleurs explorateurs du Bénin. Serez-vous le prochain à dominer ?
                </p>
            </div>

            <!-- Podium top 3 -->
            <div class="grid grid-cols-3 gap-2 sm:gap-4">
                <div v-for="player in demoPlayers.slice(0, 3)" :key="player.rank"
                    :class="player.rank === 1 ? 'order-2' : player.rank === 2 ? 'order-1' : 'order-3'"
                    class="panel-glass border border-[#26272F] p-3 sm:p-4 text-center flex flex-col items-center gap-2"
                    :style="player.rank === 1 ? 'border-color: rgba(255,198,40,0.4); box-shadow: 0 0 20px rgba(255,198,40,0.1)' : ''">
                    <span class="text-xl sm:text-3xl">
                        {{ player.rank === 1 ? '👑' : player.rank === 2 ? '🥈' : '🥉' }}
                    </span>
                    <p class="text-[10px] sm:text-xs font-black text-white uppercase italic leading-tight truncate w-full">
                        {{ player.name.split(' ')[0] }}
                    </p>
                    <p class="text-lg sm:text-2xl font-black tabular-nums"
                        :class="player.rank === 1 ? 'text-[#ffc628] text-glow-yellow' : 'text-gray-300'">
                        {{ player.points.toLocaleString() }}
                    </p>
                    <span class="text-[7px] font-black uppercase text-gray-500 tracking-widest">XP</span>
                </div>
            </div>

            <!-- Tableau complet -->
            <div class="panel-glass border border-[#26272F] overflow-hidden">

                <!-- Mobile : liste de cards -->
                <div class="sm:hidden divide-y divide-[#26272F]">
                    <div v-for="player in demoPlayers" :key="player.rank"
                        class="flex items-center gap-3 p-3"
                        :class="player.rank <= 3 ? 'bg-[#ffc628]/5' : ''">
                        <div class="w-9 h-9 shrink-0 rounded-xl flex items-center justify-center font-black text-sm"
                            :class="player.rank === 1 ? 'bg-[#ffc628]/20 text-[#ffc628]'
                                  : player.rank === 2 ? 'bg-gray-400/20 text-gray-300'
                                  : player.rank === 3 ? 'bg-amber-700/20 text-amber-600'
                                  : 'bg-[#1c183a] text-gray-500'">
                            {{ player.rank <= 3 ? ['👑','🥈','🥉'][player.rank-1] : '#' + player.rank }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-black text-sm text-white truncate">{{ player.name }}</p>
                            <p class="text-[8px] font-black uppercase text-[#ffc628] tracking-widest">{{ player.level }}</p>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="font-black text-sm tabular-nums" :class="player.rank === 1 ? 'text-[#ffc628]' : 'text-white'">
                                {{ player.points.toLocaleString() }}
                            </p>
                            <p class="text-[7px] font-black uppercase text-gray-500">XP</p>
                        </div>
                    </div>
                </div>

                <!-- Desktop : tableau -->
                <table class="hidden sm:table w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-[#10101c] border-b border-[#26272F] text-gray-500">
                            <th class="px-6 py-4 text-[8px] font-black uppercase tracking-widest text-center w-16">Rang</th>
                            <th class="px-6 py-4 text-[8px] font-black uppercase tracking-widest">Joueur</th>
                            <th class="px-6 py-4 text-[8px] font-black uppercase tracking-widest text-right">XP Total</th>
                            <th class="px-6 py-4 text-[8px] font-black uppercase tracking-widest text-right">Énigmes</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#26272F]/50">
                        <tr v-for="player in demoPlayers" :key="player.rank"
                            class="hover:bg-white/2 transition-colors group"
                            :class="player.rank <= 3 ? 'bg-[#ffc628]/3' : ''">
                            <td class="px-6 py-4 text-center">
                                <span class="text-lg font-black"
                                    :class="player.rank <= 3 ? 'text-[#ffc628]' : 'text-gray-600'">
                                    {{ player.rank <= 3 ? ['👑','🥈','🥉'][player.rank-1] : '#' + player.rank }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="h-10 w-10 rounded-xl bg-[#1c183a] border border-[#2a245c] flex items-center justify-center font-black text-xs text-[#87d74e]">
                                        {{ player.name.substring(0, 2).toUpperCase() }}
                                    </div>
                                    <div>
                                        <p class="font-black text-sm text-white uppercase italic">{{ player.name }}</p>
                                        <p class="text-[8px] font-black uppercase text-[#ffc628] tracking-widest">{{ player.level }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span class="text-xl font-black tabular-nums"
                                    :class="player.rank === 1 ? 'text-[#ffc628] text-glow-yellow' : 'text-white'">
                                    {{ player.points.toLocaleString() }}
                                </span>
                                <span class="text-[8px] font-black uppercase text-gray-500 ml-1">XP</span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span class="font-black text-white">{{ player.solved }}</span>
                                <span class="text-[8px] text-gray-500 ml-1">résolues</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- CTA -->
            <div class="panel-glass border-2 border-dashed border-[#ffc628]/20 p-5 sm:p-6 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="text-center sm:text-left">
                    <h3 class="text-base font-black uppercase italic tracking-tighter text-white mb-1">Prêt à relever le défi ?</h3>
                    <p class="text-[11px] text-gray-400 font-semibold">Lance une quête et grimpe dans le classement.</p>
                </div>
                <Link :href="route('game.setup')"
                    class="btn-3d btn-3d-yellow px-6 py-3 text-xs shadow-[0_5px_0_#9e6f00] whitespace-nowrap">
                    🚀 Lancer une Mission
                </Link>
            </div>
        </div>
    </AuthenticatedLayout>
</template>