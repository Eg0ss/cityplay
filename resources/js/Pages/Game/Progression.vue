<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

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
    <Head title="Ma Progression - Cityplay" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="space-y-1">
                    <span class="inline-block text-[#87d74e] text-glow-green font-black text-xs tracking-[0.3em] uppercase italic">Tableau d'Honneur</span>
                    <h1 class="text-3xl lg:text-5xl font-black uppercase italic tracking-tighter">
                        Ma <span class="text-[#87d74e]">Progression</span> 📈
                    </h1>
                </div>
                <div class="flex items-center gap-3">
                    <Link :href="route('game.setup')" class="btn-3d btn-3d-green px-6 py-4 rounded-xl text-xs font-black uppercase tracking-widest shadow-[0_4px_0_#5d9933]">
                        🚀 Lancer une partie
                    </Link>
                </div>
            </div>
        </template>

        <div class="space-y-12">
            <!-- XP Progress Stepper Box -->
            <div class="panel-glass p-6 sm:p-8 border border-[#2a245c] relative overflow-hidden">
                <div class="absolute -top-10 -left-10 w-32 h-32 bg-[#87d74e]/5 rounded-full blur-3xl pointer-events-none"></div>
                <div class="absolute -bottom-10 -right-10 w-32 h-32 bg-purple-600/5 rounded-full blur-3xl pointer-events-none"></div>

                <div class="grid grid-cols-1 md:grid-cols-3 items-center gap-6 mb-8">
                    <div>
                        <p class="text-[9px] font-black uppercase tracking-widest text-gray-500 mb-1">Rang Actuel</p>
                        <p class="text-2xl font-black uppercase italic text-[#87d74e] text-glow-green">{{ levelName }}</p>
                    </div>
                    <div class="text-center md:text-left">
                        <p class="text-[9px] font-black uppercase tracking-widest text-gray-500 mb-1">XP Cumulé</p>
                        <p class="text-3xl font-black text-white leading-none">{{ totalPoints }} <span class="text-xs uppercase font-black text-gray-400">XP</span></p>
                    </div>
                    <div class="text-right">
                        <p class="text-[9px] font-black uppercase tracking-widest text-gray-500 mb-1">Prochain Palier</p>
                        <p class="text-lg font-black uppercase italic text-[#ffc628]">{{ nextLevelName }}</p>
                    </div>
                </div>

                <!-- Custom Neon Progress Bar -->
                <div class="space-y-3">
                    <div class="relative w-full h-4 bg-[#10101c] rounded-full border border-[#2a245c] overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-[#7751de] to-[#87d74e] rounded-full transition-all duration-1000 glow-green"
                             :style="{ width: progressPercent + '%' }"></div>
                    </div>
                    <div class="flex justify-between text-[10px] font-black uppercase tracking-widest text-gray-400">
                        <span>{{ xpMin }} XP</span>
                        <span class="text-[#87d74e]">{{ progressPercent }}% complété</span>
                        <span>{{ xpMax }} XP</span>
                    </div>
                </div>
            </div>

            <!-- Stats Grid Breakdown -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                <!-- Played -->
                <div class="panel-glass p-5 border border-[#2a245c] relative overflow-hidden group hover:border-[#4769b0]/50 transition-colors">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-3xl">🎮</span>
                        <span class="text-[8px] font-black uppercase tracking-widest text-gray-500 bg-[#10101c] px-3 py-1 rounded-full border border-[#2a245c]">Total Sessions</span>
                    </div>
                    <p class="text-4xl font-black text-white mb-1">{{ stats.total_games }}</p>
                    <p class="text-xs font-black uppercase tracking-widest text-gray-500">Parties jouées</p>
                </div>

                <!-- Solved -->
                <div class="panel-glass p-5 border border-[#2a245c] relative overflow-hidden group hover:border-[#87d74e]/50 transition-colors">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-3xl">🕵️‍♂️</span>
                        <span class="text-[8px] font-black uppercase tracking-widest text-[#87d74e] bg-[#10101c] px-3 py-1 rounded-full border border-[#87d74e]/30">Résolues</span>
                    </div>
                    <p class="text-4xl font-black text-[#87d74e] text-glow-green mb-1">{{ stats.solved_count }}</p>
                    <p class="text-xs font-black uppercase tracking-widest text-gray-500">Énigmes décryptées</p>
                </div>

                <!-- Failed -->
                <div class="panel-glass p-5 border border-[#2a245c] relative overflow-hidden group hover:border-red-500/50 transition-colors">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-3xl">❌</span>
                        <span class="text-[8px] font-black uppercase tracking-widest text-red-500 bg-[#10101c] px-3 py-1 rounded-full border border-red-500/30">Échecs</span>
                    </div>
                    <p class="text-4xl font-black text-red-500 mb-1">{{ stats.failed_count }}</p>
                    <p class="text-xs font-black uppercase tracking-widest text-gray-500">Énigmes perdues</p>
                </div>
            </div>

            <!-- Achievements & Badges (Stunning Grid Layout) -->
            <div class="space-y-6">
                <div class="space-y-1">
                    <h3 class="text-xl font-black uppercase italic tracking-tighter text-[#ffc628]">🏆 Succès & Badges d'Explorateur</h3>
                    <p class="text-gray-400 text-sm font-medium">Débloquez des insignes spéciaux en résolvant des quêtes à travers le pays.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div v-for="badge in badges" :key="badge.id" 
                         class="panel-glass p-6 border transition-all duration-300 relative group overflow-hidden"
                         :class="badge.unlocked ? 'border-[#87d74e] shadow-[0_0_20px_rgba(135,215,78,0.15)] hover-lift' : 'border-[#2a245c] opacity-40 select-none'">
                        
                        <div class="absolute -top-10 -left-10 w-24 h-24 bg-[#87d74e]/5 rounded-full blur-2xl pointer-events-none" v-if="badge.unlocked"></div>

                        <div class="text-center space-y-4">
                            <div class="h-16 w-16 mx-auto rounded-full bg-[#10101c] border-2 flex items-center justify-center text-3xl transition-transform duration-500"
                                 :class="[
                                     badge.unlocked ? 'border-[#87d74e] shadow-[0_0_15px_rgba(135,215,78,0.25)] group-hover:scale-110' : 'border-[#2a245c]',
                                     badge.unlocked && badge.id === 'benin_legend' ? 'animate-glow-pulse' : ''
                                 ]">
                                {{ badge.title.split(' ')[1] }}
                            </div>
                            
                            <div class="space-y-1">
                                <h4 class="text-sm font-black uppercase tracking-tight" :class="badge.unlocked ? 'text-white' : 'text-gray-400'">
                                    {{ badge.title.split(' ')[0] }}
                                </h4>
                                <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wider leading-relaxed">
                                    {{ badge.description }}
                                </p>
                            </div>

                            <span class="inline-block text-[8px] font-black uppercase tracking-widest py-1.5 px-4 rounded-full"
                                  :class="badge.unlocked ? 'bg-[#87d74e]/20 text-[#87d74e]' : 'bg-[#1c183a] text-gray-500'">
                                {{ badge.unlocked ? '🔓 Débloqué' : '🔒 Verrouillé' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Attempts Chronological List -->
            <div class="space-y-6">
                <div class="space-y-1">
                    <h3 class="text-xl font-black uppercase italic tracking-tighter text-[#7751de]">📜 Chronologie des Décryptages</h3>
                    <p class="text-gray-400 text-sm font-medium">Historique en temps réel de vos 8 derniers tentatives d'énigmes.</p>
                </div>

                <div class="panel-glass border border-[#2a245c] overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-[#10101c] border-b border-[#2a245c] text-gray-500">
                                    <th class="py-4.5 px-6 text-[9px] font-black uppercase tracking-widest">Date & Heure</th>
                                    <th class="py-4.5 px-6 text-[9px] font-black uppercase tracking-widest">Lieu Historique</th>
                                    <th class="py-4.5 px-6 text-[9px] font-black uppercase tracking-widest">Question Résolue</th>
                                    <th class="py-4.5 px-6 text-[9px] font-black uppercase tracking-widest text-center">Résultat</th>
                                    <th class="py-4.5 px-6 text-[9px] font-black uppercase tracking-widest text-right">Points XP</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="attempt in recentAttempts" :key="attempt.id" 
                                    class="border-b border-[#2a245c]/50 hover:bg-[#10101c]/40 transition-colors text-sm font-medium">
                                    <td class="py-4.5 px-6 text-gray-500 font-bold uppercase text-[10px] tracking-wider whitespace-nowrap">{{ attempt.date }}</td>
                                    <td class="py-4.5 px-6 font-black uppercase text-white tracking-tight">{{ attempt.place_name }}</td>
                                    <td class="py-4.5 px-6 text-gray-400 max-w-xs truncate">{{ attempt.riddle_title }}</td>
                                    <td class="py-4.5 px-6 text-center">
                                        <span class="inline-block text-[8px] font-black uppercase tracking-widest py-1 px-3.5 rounded-full"
                                              :class="attempt.status === 'gagne' ? 'bg-[#87d74e]/20 text-[#87d74e] border border-[#87d74e]/30' : 'bg-red-500/20 text-red-500 border border-red-500/30'">
                                            {{ attempt.status === 'gagne' ? 'Succès 🟢' : 'Échec 🔴' }}
                                        </span>
                                    </td>
                                    <td class="py-4.5 px-6 text-right font-black" :class="attempt.status === 'gagne' ? 'text-[#87d74e]' : 'text-gray-500'">
                                        {{ attempt.status === 'gagne' ? '+' + attempt.points_earned : '0' }} XP
                                    </td>
                                </tr>
                                <tr v-if="recentAttempts.length === 0">
                                    <td colspan="5" class="py-12 text-center text-gray-500 uppercase font-black text-xs tracking-widest">
                                        📭 Aucune tentative enregistrée pour le moment.
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

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800;900&display=swap');
.font-sans { font-family: 'Outfit', sans-serif; }
</style>
