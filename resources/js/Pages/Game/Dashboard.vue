<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { userStatsStore } from '@/store.js';
import { Gamepad2, Trophy, Zap, TrendingUp, Play, Clock, RefreshCcw } from 'lucide-vue-next';

const props = defineProps({
    stats:          Object,
    pausedSessions: Array,
});

const isNavigating = ref(false);

const startAdventure = () => {
    if (isNavigating.value) return;
    router.visit(route('game.setup'), {
        onStart:  () => { isNavigating.value = true; },
        onFinish: () => { isNavigating.value = false; },
    });
};

const resumeSession = (token) => {
    router.get(`/game/resume/${token}`);
};
</script>

<template>
    <AuthenticatedLayout title="Dashboard">
        <div class="flex flex-col gap-4 sm:gap-5 animate-fade-in-up">

            <!-- HERO CTA -->
            <div class="panel-glass p-5 sm:p-6 border border-[#26272F] relative overflow-hidden group">
                <div class="absolute -top-16 -right-16 w-40 h-40 bg-[#2fc276]/10 rounded-full blur-[60px] pointer-events-none"></div>
                <div class="relative z-10 flex flex-col sm:flex-row sm:items-center gap-4">
                    <div class="w-14 h-14 sm:w-16 sm:h-16 bg-[#2fc276]/10 border-2 border-[#2fc276] shadow-[0_0_16px_rgba(47,194,118,0.25)] rounded-2xl flex items-center justify-center shrink-0">
                        <span class="text-2xl sm:text-3xl">🧭</span>
                    </div>
                    <div class="flex-1">
                        <span class="text-[9px] font-black uppercase tracking-[0.3em] text-[#2fc276] block mb-0.5">Nouvelle aventure</span>
                        <h1 class="text-xl sm:text-2xl font-black uppercase italic tracking-tighter text-white leading-tight mb-1">
                            Prêt à explorer ?
                        </h1>
                        <p class="text-[11px] text-gray-400 font-semibold mb-3 max-w-sm">
                            Solo, coop ou versus — choisis ta ville et résous les énigmes !
                        </p>
                        <button @click="startAdventure" :disabled="isNavigating"
                            class="btn-3d btn-3d-green px-5 py-3 text-[11px] font-black shadow-[0_5px_0_#1e7d4b] flex items-center gap-2 w-full sm:w-auto justify-center disabled:opacity-50">
                            <template v-if="isNavigating">
                                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                </svg>
                                Chargement...
                            </template>
                            <template v-else>
                                <Play :size="14" fill="currentColor" /> DÉMARRER L'AVENTURE
                            </template>
                        </button>
                    </div>
                </div>
            </div>

            <!-- AVENTURES EN PAUSE -->
            <div v-if="pausedSessions && pausedSessions.length > 0">
                <div class="flex items-center gap-2 mb-2.5">
                    <Clock :size="14" class="text-[#ffc628]" />
                    <span class="text-[9px] font-black uppercase tracking-widest text-[#ffc628]">Aventures en pause</span>
                </div>
                <div class="space-y-2">
                    <div v-for="ps in pausedSessions" :key="ps.id"
                        class="panel-glass border border-[#ffc628]/20 p-3 flex items-center gap-3 hover:border-[#ffc628]/40 transition-colors">
                        <div class="w-9 h-9 rounded-xl bg-[#ffc628]/10 border border-[#ffc628]/20 flex items-center justify-center shrink-0">
                            <span class="text-base">⏸️</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-black text-white capitalize truncate">
                                {{ ps.type }} · {{ ps.level }} · {{ ps.riddles_count }} énigmes
                            </p>
                            <p class="text-[8px] text-gray-500 font-bold">Mise en pause {{ ps.updated_at }}</p>
                        </div>
                        <button @click="resumeSession(ps.lien_token)"
                            class="btn-3d btn-3d-yellow px-3 py-2 text-[9px] shadow-[0_3px_0_#9e6f00] flex items-center gap-1.5 shrink-0">
                            <RefreshCcw :size="11" /> Reprendre
                        </button>
                    </div>
                </div>
            </div>

            <!-- STATS -->
            <div class="grid grid-cols-3 gap-2.5 sm:gap-4">
                <div class="panel-glass p-3 sm:p-5 border border-[#26272F] text-center">
                    <Gamepad2 :size="16" class="text-[#2c72f6] mx-auto mb-1" />
                    <p class="text-[7px] sm:text-[9px] font-black uppercase text-gray-500 tracking-wider mb-0.5">Parties</p>
                    <p class="text-2xl sm:text-3xl font-black text-[#2c72f6] tabular-nums">{{ stats?.total_games || 0 }}</p>
                </div>
                <div class="panel-glass p-3 sm:p-5 border border-[#26272F] text-center">
                    <Zap :size="16" class="text-[#2fc276] mx-auto mb-1" />
                    <p class="text-[7px] sm:text-[9px] font-black uppercase text-gray-500 tracking-wider mb-0.5">Résolues</p>
                    <p class="text-2xl sm:text-3xl font-black text-[#2fc276] tabular-nums">{{ stats?.riddles_solved || 0 }}</p>
                </div>
                <div class="panel-glass p-3 sm:p-5 border border-[#26272F] text-center">
                    <Trophy :size="16" class="text-[#f3a900] mx-auto mb-1" />
                    <p class="text-[7px] sm:text-[9px] font-black uppercase text-gray-500 tracking-wider mb-0.5">XP Total</p>
                    <p class="text-2xl sm:text-3xl font-black text-[#f3a900] tabular-nums">{{ stats?.total_points || 0 }}</p>
                </div>
            </div>

            <!-- LIENS RAPIDES -->
            <div class="grid grid-cols-2 gap-2.5">
                <Link :href="route('game.progression')"
                    class="panel-glass p-4 border border-[#26272F] flex items-center gap-3 hover:border-[#87d74e]/40 transition-colors active:scale-95 group">
                    <TrendingUp :size="18" class="text-[#87d74e] shrink-0" />
                    <div>
                        <p class="text-[8px] font-black uppercase tracking-widest text-gray-500">Voir ma</p>
                        <p class="text-sm font-black text-white group-hover:text-[#87d74e] transition-colors">Progression</p>
                    </div>
                </Link>
                <Link :href="route('leaderboard')"
                    class="panel-glass p-4 border border-[#26272F] flex items-center gap-3 hover:border-[#f3a900]/40 transition-colors active:scale-95 group">
                    <Trophy :size="18" class="text-[#f3a900] shrink-0" />
                    <div>
                        <p class="text-[8px] font-black uppercase tracking-widest text-gray-500">Voir le</p>
                        <p class="text-sm font-black text-white group-hover:text-[#f3a900] transition-colors">Classement</p>
                    </div>
                </Link>
            </div>
        </div>
    </AuthenticatedLayout>
</template>