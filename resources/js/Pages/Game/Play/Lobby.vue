<script setup>
import { ref, onMounted, onUnmounted, computed, watch } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useToast } from 'primevue/usetoast';
import Toast from 'primevue/toast';

const props = defineProps({
    session: Object
});

const page = usePage();
const toast = useToast();

const copyLink = () => {
    const link = `${window.location.origin}/game/lobby/${props.session.lien_token}`;
    navigator.clipboard.writeText(link);
    toast.add({ severity: 'success', summary: 'Lien copié ! 📋', detail: 'Partagez-le avec vos amis.', life: 3000 });
};

const startGame = () => {
    router.post(`/game/lobby/${props.session.lien_token}/start`, {}, {
        onSuccess: () => {
            router.get(`/game/play/${props.session.lien_token}`);
        }
    });
};

let unsubscribeBefore = null;
onMounted(() => {
    // Écouter le canal du lobby via Laravel Echo pour synchroniser les joueurs en temps réel
    window.Echo.channel(`lobby.${props.session.lien_token}`)
        .listen('LobbyUpdated', (e) => {
            // Recharger la session pour mettre à jour la liste des joueurs connectés
            router.reload({ only: ['session'] });
        });

    // Nettoyer le canal dès qu'une navigation commence (ex: déconnexion ou début du jeu)
    unsubscribeBefore = router.on('before', () => {
        window.Echo.leave(`lobby.${props.session.lien_token}`);
    });
});

onUnmounted(() => {
    if (unsubscribeBefore) unsubscribeBefore();
    window.Echo.leave(`lobby.${props.session.lien_token}`);
});

// Le créateur est le premier joueur enregistré de la session (l'hôte)
const isCreator = computed(() => {
    return props.session.players?.[0]?.user_id === page.props.auth.user.id;
});

// Alerte/Toast quand un nouveau joueur rejoint la salle d'attente
watch(() => props.session.players, (newPlayers, oldPlayers) => {
    if (oldPlayers && newPlayers && newPlayers.length > oldPlayers.length) {
        const newPlayer = newPlayers.find(np => !oldPlayers.some(op => op.id === np.id));
        if (newPlayer && newPlayer.user_id !== page.props.auth.user.id) {
            toast.add({ 
                severity: 'info', 
                summary: 'Nouveau joueur ! 👤', 
                detail: `${newPlayer.user.name} a rejoint la salle d'attente.`, 
                life: 4000 
            });
        }
    }
}, { deep: true });

// Rediriger automatiquement le participant si la partie est lancée
watch(() => props.session?.statut, (newStatus) => {
    if (newStatus === 'en_cours' && !isCreator.value) {
        router.get(`/game/play/${props.session.lien_token}`);
    }
}, { immediate: true });
</script>

<template>
    <AuthenticatedLayout title="Salle d'attente">
        <Toast position="top-right" />
        <div class="min-h-screen text-white font-sans py-8 px-4 flex items-center justify-center relative">
            
            <div class="max-w-2xl w-full panel-glass p-5 sm:p-8 border border-[#26272F] relative overflow-hidden">
                <!-- Background ambient lights -->
                <div class="absolute -top-20 -right-20 w-64 h-64 bg-[#2fc276]/10 rounded-full blur-[80px] pointer-events-none -z-10"></div>
                <div class="absolute -bottom-20 -left-20 w-64 h-64 bg-blue-600/10 rounded-full blur-[80px] pointer-events-none -z-10"></div>

                <div class="relative z-10 text-center">
                    <!-- Title block -->
                    <h1 class="text-4xl sm:text-5xl font-black uppercase italic tracking-tighter text-[#2fc276] text-glow-green mb-3">
                        LOBBY DE JEU 🎮
                    </h1>
                    
                    <div class="inline-block bg-[#1C1D24] border border-[#26272F] rounded-full px-5 py-1.5 text-[10px] font-black tracking-widest text-gray-400 mb-8 uppercase">
                        Mode : <span class="text-white">{{ session.type }}</span>
                        <span v-if="session.type === 'challengers'"> ({{ session.challenger_mode === 'reponse_par_membre' ? 'Rapide' : 'Complet' }})</span>
                    </div>

                    <!-- Link Sharing Console -->
                    <div class="bg-[#0D0E12] border border-[#26272F] rounded-2xl p-4 mb-8 flex flex-col sm:flex-row gap-4 items-stretch sm:items-center justify-between">
                        <div class="text-left overflow-hidden">
                            <span class="block text-[8px] text-gray-500 font-black mb-1 uppercase tracking-widest">Lien d'invitation</span>
                            <span class="text-sm font-black text-[#2fc276] truncate w-full block">
                                .../game/lobby/{{ session.lien_token }}
                            </span>
                        </div>
                        <button @click="copyLink" class="btn-3d btn-3d-green px-5 py-3 text-xs shadow-[0_4px_0_#1e7d4b] flex items-center justify-center gap-2 w-full sm:w-auto">
                            📋 <span>Copier</span>
                        </button>
                    </div>

                    <!-- Players Connection Table -->
                    <div class="mb-10 text-left">
                        <div class="flex justify-between items-end mb-4 border-b border-[#26272F] pb-2">
                            <h2 class="text-lg font-black uppercase italic tracking-tighter text-white">Joueurs Connectés</h2>
                            <span class="text-xs font-black text-gray-400 tabular-nums">
                                {{ session.players.length }} / {{ session.max_joueurs }}
                            </span>
                        </div>
                        
                        <ul class="space-y-3">
                            <!-- Joined active players -->
                            <li v-for="player in session.players" :key="player.id" 
                                class="flex items-center gap-4 bg-[#1C1D24] p-4 rounded-xl border border-[#26272F] animate-fade-in-up">
                                <div class="w-12 h-12 rounded-full border-2 border-[#2fc276] bg-[#0D0E12] text-[#2fc276] shadow-[0_0_10px_rgba(47,194,118,0.2)] flex items-center justify-center font-black text-sm">
                                    {{ player.user.name.substring(0, 2).toUpperCase() }}
                                </div>
                                <div class="flex flex-col">
                                    <span class="font-black text-white text-base">{{ player.user.name }}</span>
                                    <span class="text-[8px] font-black uppercase text-[#2fc276] text-glow-green" v-if="player.user_id === $page.props.auth.user.id">Vous</span>
                                </div>
                                <span class="ml-auto text-[9px] font-black bg-[#2fc276]/10 border border-[#2fc276]/20 text-[#2fc276] px-3 py-1 rounded-lg tracking-widest text-glow-green uppercase">PRÊT</span>
                            </li>
                            
                            <!-- Placeholder waiting slots -->
                            <li v-for="i in (session.max_joueurs - session.players.length)" :key="'empty'+i" 
                                class="flex items-center gap-4 bg-transparent p-4 rounded-xl border-2 border-dashed border-[#26272F] opacity-40">
                                <div class="w-12 h-12 rounded-full border-2 border-dashed border-[#26272F] flex items-center justify-center font-black text-sm text-gray-600">?</div>
                                <span class="text-gray-500 font-bold italic text-sm">En attente d'un joueur...</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Creator / Host Launch Controls -->
                    <div v-if="isCreator">
                        <button @click="startGame" 
                            :disabled="session.players.length < session.max_joueurs && session.type !== 'solo'"
                            class="w-full py-4 text-base"
                            :class="session.players.length >= session.max_joueurs || session.type === 'solo' 
                                ? 'btn-3d btn-3d-green shadow-[0_5px_0_#1e7d4b]' 
                                : 'py-4 rounded-xl bg-[#1C1D24] border border-[#26272F] text-gray-500 cursor-not-allowed font-black text-lg text-center'">
                            {{ session.players.length >= session.max_joueurs || session.type === 'solo' ? 'LANCER LA PARTIE ! 🚀' : 'ATTENDRE LES JOUEURS...' }}
                        </button>
                    </div>
                    <!-- Participant Waiting Notice -->
                    <p v-else class="text-gray-400 font-semibold italic text-center text-sm py-4 animate-pulse">
                        ⌛ En attente de l'hôte pour démarrer la partie...
                    </p>
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
