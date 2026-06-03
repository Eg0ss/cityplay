<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    Gamepad2, Search, XCircle, Trophy, Map, Award, Crown,
    TrendingUp, Rocket, CheckCircle2, Star
} from 'lucide-vue-next';

defineProps({
    levelName: String,
    nextLevelName: String,
    totalPoints: Number,
    xpMin: Number,
    xpMax: Number,
    progressPercent: Number,
    stats: Object,
    recentAttempts: Array,
    badges: Array,
});
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <div>
                    <span class="text-[9px] font-black tracking-[0.3em] text-[#87d74e] uppercase italic block">Tableau d'Honneur</span>
                    <h1 class="text-xl sm:text-2xl font-black uppercase italic tracking-tighter flex items-center gap-2">
                        Ma <span class="text-[#87d74e]">Progression</span>
                        <TrendingUp :size="20" class="text-[#87d74e]" />
                    </h1>
                </div>
                <Link :href="route('game.setup')"
                    class="btn-3d btn-3d-green px-4 py-2.5 text-[9px] shadow-[0_4px_0_#5d9933] flex items-center gap-1.5 shrink-0">
                    <Rocket :size="12" /> Jouer
                </Link>
            </div>
        </template>

        <div class="space-y-4 sm:space-y-6 animate-fade-in-up">

            <!-- XP Bar -->
            <div class="panel-glass p-4 sm:p-6 border border-[#2a245c]">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <p class="text-[8px] font-black uppercase tracking-widest text-gray-500">Rang Actuel</p>
                        <p class="text-lg font-black uppercase italic text-[#87d74e] text-glow-green leading-tight">{{ levelName }}</p>
                    </div>
                    <div class="text-center">
                        <p class="text-[8px] font-black uppercase tracking-widest text-gray-500">XP Total</p>
                        <p class="text-2xl font-black text-white tabular-nums">{{ totalPoints }} <span class="text-xs text-gray-400">XP</span></p>
                    </div>
                    <div class="text-right">
                        <p class="text-[8px] font-black uppercase tracking-widest text-gray-500">Prochain</p>
                        <p class="text-sm font-black uppercase italic text-[#ffc628] leading-tight">{{ nextLevelName }}</p>
                    </div>
                </div>
                <div class="relative w-full h-3 bg-[#10101c] rounded-full border border-[#2a245c] overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-[#7751de] to-[#87d74e] rounded-full transition-all duration-1000"
                         :style="{ width: progressPercent + '%' }"></div>
                </div>
                <div class="flex justify-between text-[8px] font-black uppercase tracking-widest text-gray-400 mt-1.5">
                    <span>{{ xpMin }} XP</span>
                    <span class="text-[#87d74e]">{{ progressPercent }}%</span>
                    <span>{{ xpMax }} XP</span>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-3 gap-2.5 sm:gap-4">
                <div class="panel-glass p-3 sm:p-5 border border-[#2a245c] text-center">
                    <Gamepad2 :size="16" class="text-[#4769b0] mx-auto mb-1" />
                    <p class="text-[7px] sm:text-[9px] font-black uppercase text-gray-500 tracking-wider mb-0.5">Parties</p>
                    <p class="text-2xl sm:text-3xl font-black text-[#4769b0] tabular-nums">{{ stats.total_games }}</p>
                </div>
                <div class="panel-glass p-3 sm:p-5 border border-[#2a245c] text-center">
                    <Search :size="16" class="text-[#87d74e] mx-auto mb-1" />
                    <p class="text-[7px] sm:text-[9px] font-black uppercase text-gray-500 tracking-wider mb-0.5">Résolues</p>
                    <p class="text-2xl sm:text-3xl font-black text-[#87d74e] text-glow-green tabular-nums">{{ stats.solved_count }}</p>
                </div>
                <div class="panel-glass p-3 sm:p-5 border border-[#2a245c] text-center">
                    <XCircle :size="16" class="text-red-500 mx-auto mb-1" />
                    <p class="text-[7px] sm:text-[9px] font-black uppercase text-gray-500 tracking-wider mb-0.5">Ratées</p>
                    <p class="text-2xl sm:text-3xl font-black text-red-500 tabular-nums">{{ stats.failed_count }}</p>
                </div>
            </div>

            <!-- Badges -->
            <div>
                <h3 class="text-sm font-black uppercase italic tracking-tighter text-[#ffc628] flex items-center gap-2 mb-3">
                    <Trophy :size="16" class="text-[#ffc628]" /> Badges d'Explorateur
                </h3>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                    <div v-for="badge in badges" :key="badge.id"
                        class="panel-glass p-3 border text-center transition-all"
                        :class="badge.unlocked
                            ? 'border-[#87d74e] shadow-[0_0_12px_rgba(135,215,78,0.15)]'
                            : 'border-[#2a245c] opacity-40'">
                        <div class="w-10 h-10 mx-auto rounded-full bg-[#10101c] border-2 flex items-center justify-center mb-2"
                            :class="badge.unlocked ? 'border-[#87d74e]' : 'border-[#2a245c]'">
                            <component :is="badge.unlocked ? Award : Crown" :size="18"
                                :class="badge.unlocked ? 'text-[#87d74e]' : 'text-gray-500'" />
                        </div>
                        <p class="text-[10px] font-black uppercase tracking-tight text-white leading-tight">{{ badge.title.split(' ')[0] }}</p>
                        <p class="text-[8px] text-gray-500 font-bold mt-0.5 leading-tight">{{ badge.description }}</p>
                        <span class="inline-flex items-center gap-0.5 text-[7px] font-black uppercase tracking-widest py-1 px-2 rounded-full mt-1.5"
                            :class="badge.unlocked ? 'bg-[#87d74e]/20 text-[#87d74e]' : 'bg-[#1c183a] text-gray-500'">
                            {{ badge.unlocked ? '✓ Débloqué' : '🔒 Verrouillé' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Historique — cards sur mobile au lieu du tableau -->
            <div>
                <h3 class="text-sm font-black uppercase italic tracking-tighter text-[#7751de] flex items-center gap-2 mb-3">
                    <Map :size="16" class="text-[#7751de]" /> Dernières tentatives
                </h3>

                <!-- Vue liste sur mobile, tableau sur desktop -->
                <div class="space-y-2 sm:hidden">
                    <div v-if="recentAttempts.length === 0"
                        class="panel-glass border border-[#2a245c] p-4 text-center text-gray-500 text-xs font-black uppercase tracking-widest">
                        Aucune tentative pour le moment.
                    </div>
                    <div v-for="attempt in recentAttempts" :key="attempt.id"
                        class="panel-glass border border-[#2a245c] p-3 flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0"
                            :class="attempt.status === 'gagne' ? 'bg-[#87d74e]/20' : 'bg-red-500/20'">
                            <component :is="attempt.status === 'gagne' ? CheckCircle2 : XCircle" :size="16"
                                :class="attempt.status === 'gagne' ? 'text-[#87d74e]' : 'text-red-400'" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-black text-xs text-white truncate">{{ attempt.place_name }}</p>
                            <p class="text-[9px] text-gray-500 truncate">{{ attempt.riddle_title }}</p>
                            <p class="text-[8px] text-gray-600 mt-0.5">{{ attempt.date }}</p>
                        </div>
                        <span class="font-black text-sm shrink-0"
                            :class="attempt.status === 'gagne' ? 'text-[#87d74e]' : 'text-gray-600'">
                            {{ attempt.status === 'gagne' ? '+' + attempt.points_earned : '0' }} XP
                        </span>
                    </div>
                </div>

                <!-- Tableau desktop -->
                <div class="hidden sm:block panel-glass border border-[#2a245c] overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-[#10101c] border-b border-[#2a245c] text-gray-500">
                                    <th class="py-3 px-4 text-[8px] font-black uppercase tracking-widest">Date</th>
                                    <th class="py-3 px-4 text-[8px] font-black uppercase tracking-widest">Lieu</th>
                                    <th class="py-3 px-4 text-[8px] font-black uppercase tracking-widest">Énigme</th>
                                    <th class="py-3 px-4 text-[8px] font-black uppercase tracking-widest text-center">Résultat</th>
                                    <th class="py-3 px-4 text-[8px] font-black uppercase tracking-widest text-right">XP</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="attempt in recentAttempts" :key="attempt.id"
                                    class="border-b border-[#2a245c]/50 hover:bg-[#10101c]/40 transition-colors text-sm">
                                    <td class="py-3 px-4 text-gray-500 font-bold text-[9px] whitespace-nowrap">{{ attempt.date }}</td>
                                    <td class="py-3 px-4 font-black text-white text-xs">{{ attempt.place_name }}</td>
                                    <td class="py-3 px-4 text-gray-400 text-xs max-w-xs truncate">{{ attempt.riddle_title }}</td>
                                    <td class="py-3 px-4 text-center">
                                        <span class="inline-flex items-center gap-1 text-[7px] font-black uppercase tracking-widest py-0.5 px-2.5 rounded-full"
                                            :class="attempt.status === 'gagne'
                                                ? 'bg-[#87d74e]/20 text-[#87d74e] border border-[#87d74e]/30'
                                                : 'bg-red-500/20 text-red-400 border border-red-500/30'">
                                            <component :is="attempt.status === 'gagne' ? CheckCircle2 : XCircle" :size="9" />
                                            {{ attempt.status === 'gagne' ? 'Succès' : 'Échec' }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-right font-black text-sm"
                                        :class="attempt.status === 'gagne' ? 'text-[#87d74e]' : 'text-gray-500'">
                                        {{ attempt.status === 'gagne' ? '+' + attempt.points_earned : '0' }} XP
                                    </td>
                                </tr>
                                <tr v-if="recentAttempts.length === 0">
                                    <td colspan="5" class="py-8 text-center text-gray-500 text-xs font-black uppercase tracking-widest">
                                        Aucune tentative enregistrée.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
