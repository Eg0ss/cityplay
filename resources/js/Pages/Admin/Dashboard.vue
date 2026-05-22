<script setup>
import AdminLayout from './AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { 
    Users, 
    MapPin, 
    Puzzle, 
    Trophy, 
    Rocket,
    Clock
} from 'lucide-vue-next';

defineProps({
    stats: Object,
    recent_places: Array,
});
</script>

<template>
    <Head title="Mission Control" />
    <AdminLayout>
        <div class="space-y-8 lg:space-y-12">
            <!-- Header Section -->
            <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-6">
                <div>
                    <h1 class="text-4xl lg:text-6xl font-black tracking-tighter uppercase italic leading-none dark:text-white text-gray-900">
                        SPACE<span class="text-[#2fc276]"> ADMINISTRATEUR</span>
                    </h1>
                    <p class="text-gray-500 font-bold uppercase tracking-[0.3em] text-[10px] mt-4 flex items-center gap-2">
                        <span class="h-2 w-2 rounded-full bg-[#2fc276] animate-pulse"></span>
                         Cityplay Bénin
                    </p>
                </div>
                <div class="text-left lg:text-right">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest flex items-center lg:justify-end gap-2">
                        <Clock :size="10" />
                        Temps Réel
                    </p>
                    <p class="text-xl lg:text-2xl font-black font-mono tracking-tighter dark:text-white text-gray-900">{{ new Date().toLocaleDateString() }}</p>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid gap-4 lg:gap-6 grid-cols-1 sm:grid-cols-2 lg:grid-cols-4">
                <div class="dark:bg-[#111113]/50 bg-white backdrop-blur-xl p-6 lg:p-8 rounded-[2rem] border dark:border-white/5 border-gray-200 group hover:border-[#2fc276]/30 transition-all duration-500 shadow-sm dark:shadow-none">
                    <div class="flex justify-between items-start mb-6">
                        <span class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 group-hover:text-[#2fc276] transition-colors">Joueurs</span>
                    </div>
                    <div class="text-4xl lg:text-5xl font-black tracking-tighter dark:text-white text-gray-900">{{ stats.users_count }}</div>
                    <div class="mt-4 h-1 w-full bg-gray-100 dark:bg-white/5 rounded-full overflow-hidden">
                        <div class="h-full bg-[#2fc276] w-2/3 shadow-[0_0_10px_#2fc276]"></div>
                    </div>
                </div>

                <div class="dark:bg-[#111113]/50 bg-white backdrop-blur-xl p-6 lg:p-8 rounded-[2rem] border dark:border-white/5 border-gray-200 group hover:border-blue-500/30 transition-all duration-500 shadow-sm dark:shadow-none">
                    <div class="flex justify-between items-start mb-6">
                        <span class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 group-hover:text-blue-500 transition-colors">Lieux</span>
                    </div>
                    <div class="text-4xl lg:text-5xl font-black tracking-tighter text-blue-500">{{ stats.places_count }}</div>
                    <div class="mt-4 h-1 w-full bg-gray-100 dark:bg-white/5 rounded-full overflow-hidden">
                        <div class="h-full bg-blue-500 w-1/2 shadow-[0_0_10px_rgba(59,130,246,0.5)]"></div>
                    </div>
                </div>

                <div class="dark:bg-[#111113]/50 bg-white backdrop-blur-xl p-6 lg:p-8 rounded-[2rem] border dark:border-white/5 border-gray-200 group hover:border-[#7751de]/30 transition-all duration-500 shadow-sm dark:shadow-none">
                    <div class="flex justify-between items-start mb-6">
                        <span class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 group-hover:text-[#7751de] transition-colors">Énigmes</span>
                    </div>
                    <div class="text-4xl lg:text-5xl font-black tracking-tighter text-[#7751de]">{{ stats.riddles_count }}</div>
                    <div class="mt-4 h-1 w-full bg-gray-100 dark:bg-white/5 rounded-full overflow-hidden">
                        <div class="h-full bg-[#7751de] w-3/4 shadow-[0_0_10px_rgba(119,81,222,0.5)]"></div>
                    </div>
                </div>

                <div class="dark:bg-[#111113]/50 bg-white backdrop-blur-xl p-6 lg:p-8 rounded-[2rem] border dark:border-white/5 border-gray-200 group hover:border-[#2fc276]/30 transition-all duration-500 shadow-sm dark:shadow-none">
                    <div class="flex justify-between items-start mb-6">
                        <span class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 group-hover:text-[#2fc276] transition-colors">Succès</span>
                    </div>
                    <div class="text-4xl lg:text-5xl font-black tracking-tighter text-[#2fc276]">{{ stats.solved_count }}%</div>
                    <div class="mt-4 h-1 w-full bg-gray-100 dark:bg-white/5 rounded-full overflow-hidden">
                        <div class="h-full bg-[#2fc276] w-full shadow-[0_0_10px_rgba(47,194,118,0.5)]"></div>
                    </div>
                </div>
            </div>

            <!-- Content Area -->
            <div class="grid gap-6 lg:grid-cols-2">
                <!-- Recent Places -->
                <div class="dark:bg-[#111113]/30 bg-white backdrop-blur-md p-6 lg:p-10 rounded-[2.5rem] border dark:border-white/5 border-gray-200 shadow-sm dark:shadow-none">
                    <div class="flex items-center justify-between mb-8 lg:mb-10">
                        <h3 class="text-lg lg:text-xl font-black uppercase tracking-widest italic dark:text-white text-gray-900">Lieux Récents</h3>
                        <Link :href="route('admin.places.all')" class="text-[8px] lg:text-[10px] font-bold text-[#2fc276] border border-[#2fc276]/20 px-3 py-1 rounded-full uppercase hover:bg-[#2fc276] hover:text-black transition-all">Voir Tout</Link>
                    </div>
                    
                    <div class="space-y-4">
                        <div v-for="place in recent_places" :key="place.id" class="flex items-center justify-between p-4 dark:bg-white/5 bg-gray-50 rounded-2xl border dark:border-white/5 border-gray-100 hover:bg-gray-100 dark:hover:bg-white/10 transition-colors group cursor-pointer">
                            <div class="flex items-center gap-4">
                                <div class="h-10 w-10 lg:h-12 lg:w-12 dark:bg-black/60 bg-white border border-gray-200 dark:border-none rounded-xl flex items-center justify-center text-lg lg:text-xl group-hover:scale-110 transition-transform shadow-sm">
                                    <MapPin :size="20" class="text-[#2fc276]" />
                                </div>
                                <div>
                                    <p class="font-black text-xs lg:text-sm uppercase dark:text-white text-gray-900">{{ place.nom }}</p>
                                    <p class="text-[8px] lg:text-[10px] text-[#2fc276] font-bold uppercase tracking-widest">{{ place.city?.name || 'Inconnu' }}</p>
                                </div>
                            </div>
                            <span class="text-[8px] font-black uppercase text-gray-500">{{ new Date(place.created_at).toLocaleDateString() }}</span>
                        </div>
                    </div>
                </div>

                <!-- Action Shortcut -->
                <div class="bg-gradient-to-br from-[#2fc276] to-[#7751de] p-1 rounded-[2.5rem] group shadow-xl">
                    <div class="dark:bg-[#0A0A0B] bg-white h-full w-full rounded-[2.3rem] p-8 lg:p-10 flex flex-col justify-center items-center text-center">
                        <h3 class="text-2xl lg:text-3xl font-black uppercase italic tracking-tighter mb-4 dark:text-white text-gray-900">Nouveau Secteur</h3>
                        <p class="text-gray-500 dark:text-gray-400 font-medium text-sm max-w-xs mb-8 lg:mb-10">Étendez la matrice en ajoutant une nouvelle destination stratégique.</p>
                        <Link :href="route('admin.cities')" class="bg-gray-900 dark:bg-white text-white dark:text-black px-10 py-4 rounded-2xl font-black uppercase tracking-widest text-xs lg:text-sm hover:scale-105 transition-all shadow-lg flex items-center gap-3">
                            <MapPin :size="16" />
                            Déployer Maintenant
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>