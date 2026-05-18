<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    session: Object
});

const copyLink = () => {
    const link = `${window.location.origin}/game/lobby/${props.session.lien_token}`;
    navigator.clipboard.writeText(link);
    alert('Lien copié dans le presse-papier ! Partagez-le avec vos amis.');
};

const startGame = () => {
    router.post(route('game.start', { token: props.session.lien_token }));
};

// Polling simulation for new players (Normally done via Echo/Pusher)
let pollInterval;
onMounted(() => {
    pollInterval = setInterval(() => {
        router.reload({ only: ['session'] });
    }, 5000);
});

onUnmounted(() => {
    clearInterval(pollInterval);
});

const isCreator = true; // In reality, check if auth user is the creator
</script>

<template>
    <AuthenticatedLayout title="Salle d'attente">
        <div class="min-h-screen bg-gray-900 text-white font-sans py-12 px-4 flex items-center justify-center">
            <div class="max-w-2xl w-full bg-gray-800 rounded-2xl shadow-[0_0_50px_rgba(0,0,0,0.6)] border border-gray-700 p-8 relative overflow-hidden">
                <!-- Background animations -->
                <div class="absolute -top-20 -right-20 w-64 h-64 bg-blue-600/20 rounded-full blur-3xl animate-pulse"></div>
                <div class="absolute -bottom-20 -left-20 w-64 h-64 bg-purple-600/20 rounded-full blur-3xl animate-pulse" style="animation-delay: 1s"></div>

                <div class="relative z-10 text-center">
                    <h1 class="text-4xl font-black mb-2 text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-purple-500">
                        Salle d'attente
                    </h1>
                    <p class="text-gray-400 mb-8">
                        Mode : <strong class="uppercase text-white">{{ session.type }}</strong>
                        <span v-if="session.type === 'challengers'">({{ session.challenger_mode === 'reponse_par_membre' ? 'Rapide' : 'Complet' }})</span>
                    </p>

                    <!-- Link Sharing -->
                    <div class="bg-gray-900 border border-gray-700 rounded-xl p-4 mb-8 flex items-center justify-between">
                        <div class="text-left overflow-hidden">
                            <span class="block text-xs text-gray-500 font-bold mb-1 uppercase">Lien d'invitation</span>
                            <span class="text-sm text-blue-400 truncate w-full block">.../game/lobby/{{ session.lien_token }}</span>
                        </div>
                        <button @click="copyLink" class="ml-4 p-3 bg-gray-800 hover:bg-gray-700 rounded-lg transition-colors border border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            📋 Copier
                        </button>
                    </div>

                    <!-- Players List -->
                    <div class="mb-10 text-left">
                        <div class="flex justify-between items-end mb-4 border-b border-gray-700 pb-2">
                            <h2 class="text-xl font-bold">Joueurs Connectés</h2>
                            <span class="text-sm text-gray-400">{{ session.players.length }} / {{ session.max_joueurs }}</span>
                        </div>
                        
                        <ul class="space-y-3">
                            <li v-for="player in session.players" :key="player.id" 
                                class="flex items-center gap-4 bg-gray-900/50 p-3 rounded-lg border border-gray-800 animate-fade-in-up">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center font-bold shadow-inner">
                                    {{ player.user.name.charAt(0).toUpperCase() }}
                                </div>
                                <span class="font-semibold">{{ player.user.name }}</span>
                                <span v-if="player.user_id === $page.props.auth.user.id" class="text-xs bg-blue-500/20 text-blue-400 px-2 py-1 rounded border border-blue-500/30">Vous</span>
                                <span class="ml-auto text-xs text-green-400 font-bold tracking-wider">PRÊT</span>
                            </li>
                            
                            <!-- Placeholder pour les joueurs manquants -->
                            <li v-for="i in (session.max_joueurs - session.players.length)" :key="'empty'+i" 
                                class="flex items-center gap-4 bg-gray-900/20 p-3 rounded-lg border border-gray-800/50 border-dashed opacity-50">
                                <div class="w-10 h-10 rounded-full bg-gray-800 flex items-center justify-center">?</div>
                                <span class="text-gray-500 italic">En attente d'un joueur...</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Action Button -->
                    <button v-if="isCreator" @click="startGame" 
                        :disabled="session.players.length < 2 && session.type !== 'solo'"
                        class="w-full py-4 rounded-xl font-black text-lg transition-all duration-300 transform shadow-lg relative overflow-hidden group"
                        :class="session.players.length >= 2 || session.type === 'solo' 
                            ? 'bg-gradient-to-r from-green-500 to-emerald-600 text-white hover:scale-105 hover:shadow-[0_0_20px_rgba(16,185,129,0.5)]' 
                            : 'bg-gray-800 text-gray-500 cursor-not-allowed border border-gray-700'">
                        <span class="relative z-10">{{ session.players.length >= session.max_joueurs ? 'LANCER LA PARTIE !' : 'ATTENDRE D\'AUTRES JOUEURS...' }}</span>
                        <div class="absolute inset-0 bg-white/20 translate-y-full group-hover:translate-y-0 transition-transform duration-300 ease-in-out"></div>
                    </button>
                    <p v-else class="text-gray-400 italic">En attente de l'hôte pour démarrer la partie...</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.animate-fade-in-up {
    animation: fadeInUp 0.4s ease-out forwards;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
