<script setup>
import AdminLayout from './AdminLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    stats: Object,
});
</script>

<template>
    <Head title="Mission Control" />
    <AdminLayout>
        <div class="space-y-12">
            <!-- Header Section -->
            <div class="flex items-end justify-between">
                <div>
                    <h1 class="text-6xl font-black tracking-tighter uppercase italic leading-none">
                        Mission <span class="text-[#FF9F1C]">Control</span>
                    </h1>
                    <p class="text-gray-500 font-bold uppercase tracking-[0.3em] text-xs mt-4 flex items-center gap-2">
                        <span class="h-2 w-2 rounded-full bg-green-500 animate-pulse"></span>
                        Système Opérationnel • Cityplay Bénin
                    </p>
                </div>
                <div class="text-right">
                    <p class="text-[10px] font-bold text-gray-600 uppercase tracking-widest">Temps Réel</p>
                    <p class="text-2xl font-black font-mono tracking-tighter">05.15.2026</p>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid gap-6 md:grid-cols-4">
                <div class="bg-[#111113]/50 backdrop-blur-xl p-8 rounded-[2rem] border border-white/5 group hover:border-[#FF9F1C]/30 transition-all duration-500">
                    <div class="flex justify-between items-start mb-6">
                        <span class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 group-hover:text-[#FF9F1C] transition-colors">Joueurs</span>
                        <span class="text-xl">👥</span>
                    </div>
                    <div class="text-5xl font-black tracking-tighter">{{ stats.users_count }}</div>
                    <div class="mt-4 h-1 w-full bg-white/5 rounded-full overflow-hidden">
                        <div class="h-full bg-[#FF9F1C] w-2/3 shadow-[0_0_10px_#FF9F1C]"></div>
                    </div>
                </div>

                <div class="bg-[#111113]/50 backdrop-blur-xl p-8 rounded-[2rem] border border-white/5 group hover:border-blue-500/30 transition-all duration-500">
                    <div class="flex justify-between items-start mb-6">
                        <span class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 group-hover:text-blue-500 transition-colors">Lieux</span>
                        <span class="text-xl">📍</span>
                    </div>
                    <div class="text-5xl font-black tracking-tighter text-blue-500">{{ stats.places_count }}</div>
                    <div class="mt-4 h-1 w-full bg-white/5 rounded-full overflow-hidden">
                        <div class="h-full bg-blue-500 w-1/2 shadow-[0_0_10px_rgba(59,130,246,0.5)]"></div>
                    </div>
                </div>

                <div class="bg-[#111113]/50 backdrop-blur-xl p-8 rounded-[2rem] border border-white/5 group hover:border-purple-500/30 transition-all duration-500">
                    <div class="flex justify-between items-start mb-6">
                        <span class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 group-hover:text-purple-500 transition-colors">Énigmes</span>
                        <span class="text-xl">🧩</span>
                    </div>
                    <div class="text-5xl font-black tracking-tighter text-purple-500">{{ stats.riddles_count }}</div>
                    <div class="mt-4 h-1 w-full bg-white/5 rounded-full overflow-hidden">
                        <div class="h-full bg-purple-500 w-3/4 shadow-[0_0_10px_rgba(168,85,247,0.5)]"></div>
                    </div>
                </div>

                <div class="bg-[#111113]/50 backdrop-blur-xl p-8 rounded-[2rem] border border-white/5 group hover:border-green-500/30 transition-all duration-500">
                    <div class="flex justify-between items-start mb-6">
                        <span class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 group-hover:text-green-500 transition-colors">Succès</span>
                        <span class="text-xl">🏆</span>
                    </div>
                    <div class="text-5xl font-black tracking-tighter text-green-500">{{ stats.solved_count }}%</div>
                    <div class="mt-4 h-1 w-full bg-white/5 rounded-full overflow-hidden">
                        <div class="h-full bg-green-500 w-full shadow-[0_0_10px_rgba(34,197,94,0.5)]"></div>
                    </div>
                </div>
            </div>

            <!-- Content Area -->
            <div class="grid gap-6 md:grid-cols-2">
                <!-- Activity Log -->
                <div class="bg-[#111113]/30 backdrop-blur-md p-10 rounded-[2.5rem] border border-white/5">
                    <div class="flex items-center justify-between mb-10">
                        <h3 class="text-xl font-black uppercase tracking-widest italic">Activité Récente</h3>
                        <span class="text-[10px] font-bold text-gray-600 border border-white/10 px-3 py-1 rounded-full uppercase">Live Feed</span>
                    </div>
                    
                    <div class="space-y-4">
                        <div v-for="i in 4" :key="i" class="flex items-center justify-between p-4 bg-white/5 rounded-2xl border border-white/5 hover:bg-white/10 transition-colors group cursor-pointer">
                            <div class="flex items-center gap-4">
                                <div class="h-12 w-12 bg-gray-900 rounded-xl flex items-center justify-center text-xl group-hover:scale-110 transition-transform">
                                    {{ i % 2 === 0 ? '🏁' : '🆕' }}
                                </div>
                                <div>
                                    <p class="font-black text-sm uppercase">{{ i % 2 === 0 ? 'Partie Terminée' : 'Nouvel Explorateur' }}</p>
                                    <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest">Il y a {{ i * 5 }} minutes</p>
                                </div>
                            </div>
                            <div class="text-[#FF9F1C] font-mono font-bold text-sm">+ {{ i * 100 }} XP</div>
                        </div>
                    </div>
                </div>

                <!-- Action Shortcut -->
                <div class="bg-gradient-to-br from-[#FF9F1C] to-[#FFBF69] p-1 rounded-[2.5rem] group shadow-[0_20px_50px_rgba(255,159,28,0.2)]">
                    <div class="bg-[#0A0A0B] h-full w-full rounded-[2.3rem] p-10 flex flex-col justify-center items-center text-center">
                        <div class="h-24 w-24 bg-[#FF9F1C] rounded-[2rem] flex items-center justify-center text-4xl mb-8 shadow-[0_0_30px_#FF9F1C] group-hover:scale-110 transition-transform duration-500">
                            🚀
                        </div>
                        <h3 class="text-3xl font-black uppercase italic tracking-tighter mb-4">Prêt à créer ?</h3>
                        <p class="text-gray-400 font-medium max-w-xs mb-10">Ajoutez de nouveaux lieux et défiez vos joueurs avec des énigmes épiques.</p>
                        <Link :href="route('admin.places')" class="bg-white text-black px-10 py-4 rounded-2xl font-black uppercase tracking-widest text-sm hover:bg-[#FF9F1C] hover:text-black transition-all shadow-[0_10px_20px_rgba(255,255,255,0.1)]">
                            Nouveau Lieu
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>