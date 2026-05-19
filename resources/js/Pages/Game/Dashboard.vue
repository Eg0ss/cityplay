<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useConfirm } from 'primevue/useconfirm';

const props = defineProps({
    stats: Object
});

const confirm = useConfirm();

const confirmLogout = () => {
    confirm.require({
        message: 'Vous serez déconnecté et redirigé vers la page de connexion.',
        header: 'Quitter la session',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Rester',
        acceptLabel: 'Se déconnecter',
        rejectClass: 'p-button-secondary p-button-outlined text-gray-300 border-gray-600 hover:bg-gray-800 px-4 py-2 rounded-lg mr-2',
        acceptClass: 'p-button-danger bg-red-600 border-red-600 text-white hover:bg-red-500 px-4 py-2 rounded-lg',
        accept: () => {
            router.post(route('logout'));
        },
    });
};
</script>

<template>
    <AuthenticatedLayout title="Gaming Dashboard">
        <div class="min-h-screen text-white font-sans py-8 px-4 sm:px-6 lg:px-8 relative">
            <div class="max-w-6xl mx-auto">
                
                <!-- Main Header (GeoGuessr Arcade Intro) -->
                <div class="text-center mb-16 relative">
                    <div class="absolute -top-10 left-1/2 -translate-x-1/2 w-48 h-48 bg-[#2fc276]/10 rounded-full blur-[80px] -z-10"></div>
                    <h1 class="text-4xl sm:text-6xl font-black tracking-tighter uppercase italic text-white mb-4">
                        PRÊT À EXPLORER <span class="text-[#2fc276] text-glow-green">LE MONDE</span> ?
                    </h1>
                    <p class="text-lg text-gray-400 font-semibold max-w-xl mx-auto leading-relaxed">
                        Affrontez des énigmes, parcourez votre ville en géolocalisation, accumulez les XP et atteignez les sommets du classement global.
                    </p>
                </div>

                <!-- Two-Column Console Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-start">
                    
                    <!-- COLUMN 1: Start Adventure Console -->
                    <div class="panel-glass p-5 sm:p-8 border border-[#26272F] relative overflow-hidden group">
                        <!-- Neon background halo -->
                        <div class="absolute -top-24 -right-24 w-48 h-48 bg-[#2fc276]/10 rounded-full blur-[60px] pointer-events-none transition-opacity group-hover:opacity-100 duration-300"></div>
                        
                        <div class="relative z-10 text-center flex flex-col items-center">
                            <!-- Animated Geoguessr Map Pin Icon -->
                            <div class="w-24 h-24 bg-[#2fc276]/10 border-2 border-[#2fc276] shadow-[0_0_20px_rgba(47,194,118,0.25)] rounded-3xl flex items-center justify-center mb-8 transform group-hover:scale-105 group-hover:rotate-6 transition-all duration-300">
                                <span class="text-5xl">🧭</span>
                            </div>
                            
                            <h2 class="text-3xl font-black uppercase italic tracking-tighter text-white mb-3">Nouvelle Partie</h2>
                            <p class="text-sm text-gray-400 font-bold max-w-sm mb-10 leading-relaxed">
                                Lancez une quête géographique en solo, ou défiez vos amis dans un lobby en direct en mode coopération ou compétition rapide.
                            </p>
                            
                            <!-- Bouncy 3D Action Button -->
                            <Link :href="route('game.setup')" class="btn-3d btn-3d-green w-full py-4 text-lg font-black text-center shadow-[0_5px_0_#1e7d4b]">
                                DÉMARRER L'AVENTURE 🚀
                            </Link>
                        </div>
                    </div>

                    <!-- COLUMN 2: Gamified Stats Console -->
                    <div class="panel-glass p-5 sm:p-8 border border-[#26272F] relative overflow-hidden">
                        <h2 class="text-2xl font-black uppercase italic tracking-tighter text-white mb-8 flex items-center gap-3">
                            <span class="text-[#f3a900]">🏆</span> Vos Performances
                        </h2>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <!-- Games Played widget -->
                            <div class="p-4 sm:p-6 rounded-2xl bg-[#1C1D24] border border-[#26272F] text-center glow-blue transition-all duration-300">
                                <span class="text-[9px] font-black uppercase text-gray-500 tracking-wider block mb-2">Parties Jouées</span>
                                <span class="text-4xl font-black text-[#2c72f6] text-glow-blue block tabular-nums">
                                    {{ stats?.total_games || 0 }}
                                </span>
                            </div>
                            
                            <!-- Riddles Solved widget -->
                            <div class="p-4 sm:p-6 rounded-2xl bg-[#1C1D24] border border-[#26272F] text-center glow-green transition-all duration-300">
                                <span class="text-[9px] font-black uppercase text-gray-500 tracking-wider block mb-2">Énigmes Résolues</span>
                                <span class="text-4xl font-black text-[#2fc276] text-glow-green block tabular-nums">
                                    {{ stats?.riddles_solved || 0 }}
                                </span>
                            </div>
                            
                            <!-- Total Score widget (Highlight) -->
                            <div class="col-span-2 p-5 sm:p-8 rounded-2xl bg-[#1C1D24] border border-[#26272F] text-center glow-yellow mt-2 transition-all duration-300">
                                <span class="text-[10px] font-black uppercase text-gray-400 tracking-widest block mb-3">Score d'Exploration Global</span>
                                <span class="text-5xl font-black text-[#f3a900] text-glow-yellow block tabular-nums drop-shadow-[0_0_15px_rgba(243,169,0,0.4)]">
                                    {{ stats?.total_points || 0 }} <span class="text-2xl">XP</span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-12 flex justify-center">
                    <button
                        type="button"
                        @click="confirmLogout"
                        class="btn-3d btn-3d-red px-8 py-3.5 text-xs font-black uppercase tracking-widest shadow-[0_4px_0_#9e2318]"
                    >
                        🚪 Se déconnecter
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
::selection {
    background: rgba(47, 194, 118, 0.4);
    color: white;
}
</style>
