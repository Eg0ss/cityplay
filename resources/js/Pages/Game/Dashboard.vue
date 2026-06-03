<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useConfirm } from 'primevue/useconfirm';
import { Gamepad2, Trophy, Zap, TrendingUp } from 'lucide-vue-next';

const props = defineProps({ stats: Object });
const confirm = useConfirm();
const isNavigating = ref(false);

const startAdventure = () => {
    if (isNavigating.value) return;
    router.visit(route('game.setup'), {
        onStart:  () => { isNavigating.value = true; },
        onFinish: () => { isNavigating.value = false; }
    });
};
</script>

<template>
    <AuthenticatedLayout title="Dashboard">
        <!-- Contenu optimisé phone-first : tout visible sans scroll -->
        <div class="flex flex-col gap-4 sm:gap-6 animate-fade-in-up">

            <!-- HERO : CTA principal -->
            <div class="panel-glass p-5 sm:p-7 border border-[#26272F] relative overflow-hidden group">
                <div class="absolute -top-16 -right-16 w-40 h-40 bg-[#2fc276]/10 rounded-full blur-[60px] pointer-events-none"></div>

                <div class="relative z-10 flex flex-col sm:flex-row sm:items-center gap-5">
                    <!-- Icône animée -->
                    <div class="w-16 h-16 sm:w-20 sm:h-20 bg-[#2fc276]/10 border-2 border-[#2fc276] shadow-[0_0_20px_rgba(47,194,118,0.25)] rounded-2xl flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                        <span class="text-3xl sm:text-4xl">🧭</span>
                    </div>

                    <div class="flex-1">
                        <span class="text-[9px] font-black uppercase tracking-[0.3em] text-[#2fc276] text-glow-green block mb-1">Nouvel itinéraire</span>
                        <h1 class="text-2xl sm:text-3xl font-black uppercase italic tracking-tighter text-white leading-tight mb-2">
                            Prêt à explorer ?
                        </h1>
                        <p class="text-xs text-gray-400 font-semibold leading-relaxed mb-4 max-w-sm">
                            Lance une quête solo ou défie tes amis en coop ou versus.
                        </p>

                        <button
                            @click="startAdventure"
                            :disabled="isNavigating"
                            class="btn-3d btn-3d-green px-6 py-3.5 text-sm font-black shadow-[0_5px_0_#1e7d4b] flex items-center gap-2 w-full sm:w-auto justify-center"
                        >
                            <template v-if="isNavigating">
                                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                                </svg>
                                Chargement...
                            </template>
                            <template v-else>
                                🚀 DÉMARRER L'AVENTURE
                            </template>
                        </button>
                    </div>
                </div>
            </div>

            <!-- STATS : grille compacte -->
            <div class="grid grid-cols-3 gap-3 sm:gap-4">
                <div class="panel-glass p-3 sm:p-5 border border-[#26272F] text-center">
                    <Gamepad2 :size="18" class="text-[#2c72f6] mx-auto mb-1.5" />
                    <span class="text-[7px] sm:text-[9px] font-black uppercase text-gray-500 tracking-wider block mb-1">Parties</span>
                    <span class="text-2xl sm:text-3xl font-black text-[#2c72f6] text-glow-blue tabular-nums">
                        {{ stats?.total_games || 0 }}
                    </span>
                </div>

                <div class="panel-glass p-3 sm:p-5 border border-[#26272F] text-center">
                    <Zap :size="18" class="text-[#2fc276] mx-auto mb-1.5" />
                    <span class="text-[7px] sm:text-[9px] font-black uppercase text-gray-500 tracking-wider block mb-1">Énigmes</span>
                    <span class="text-2xl sm:text-3xl font-black text-[#2fc276] text-glow-green tabular-nums">
                        {{ stats?.riddles_solved || 0 }}
                    </span>
                </div>

                <div class="panel-glass p-3 sm:p-5 border border-[#26272F] text-center">
                    <Trophy :size="18" class="text-[#f3a900] mx-auto mb-1.5" />
                    <span class="text-[7px] sm:text-[9px] font-black uppercase text-gray-500 tracking-wider block mb-1">Score</span>
                    <span class="text-2xl sm:text-3xl font-black text-[#f3a900] text-glow-yellow tabular-nums">
                        {{ stats?.total_points || 0 }}
                    </span>
                </div>
            </div>

            <!-- LIENS RAPIDES -->
            <div class="grid grid-cols-2 gap-3">
                <Link :href="route('game.progression')"
                    class="panel-glass p-4 border border-[#26272F] flex items-center gap-3 hover:border-[#87d74e]/40 transition-colors active:scale-95 transition-transform group">
                    <TrendingUp :size="20" class="text-[#87d74e] shrink-0" />
                    <div>
                        <p class="text-[8px] font-black uppercase tracking-widest text-gray-500">Voir ma</p>
                        <p class="text-sm font-black text-white group-hover:text-[#87d74e] transition-colors">Progression</p>
                    </div>
                </Link>
                <Link :href="route('leaderboard')"
                    class="panel-glass p-4 border border-[#26272F] flex items-center gap-3 hover:border-[#f3a900]/40 transition-colors active:scale-95 transition-transform group">
                    <Trophy :size="20" class="text-[#f3a900] shrink-0" />
                    <div>
                        <p class="text-[8px] font-black uppercase tracking-widest text-gray-500">Voir le</p>
                        <p class="text-sm font-black text-white group-hover:text-[#f3a900] transition-colors">Classement</p>
                    </div>
                </Link>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
