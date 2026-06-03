<script setup>
import { ref, onMounted, onUnmounted, computed, watch } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useToast } from 'primevue/usetoast';
import Toast from 'primevue/toast';
import { useAudio } from '@/composables/useAudio.js';
import AudioWidget from '@/Components/AudioWidget.vue';
import { Copy, Rocket, Users, Clock } from 'lucide-vue-next';

const {
    playClick, playNotification,
    playBackgroundMusic, stopBackgroundMusic,
    initAudioContext,
} = useAudio();

const props = defineProps({ session: Object });
const page = usePage();
const toast = useToast();

const isStarting = ref(false);

const copyLink = () => {
    const link = `${window.location.origin}/game/lobby/${props.session.lien_token}`;
    navigator.clipboard.writeText(link).then(() => {
        toast.add({ severity: 'success', summary: '📋 Lien copié !', detail: 'Partage-le avec tes équipiers.', life: 2500 });
    });
};

const copyLinkWithSound = () => { playClick(); copyLink(); };

const startGame = () => {
    if (isStarting.value) return;
    isStarting.value = true;
    playClick();
    router.post(`/game/lobby/${props.session.lien_token}/start`, {}, {
        onSuccess: () => {
            stopBackgroundMusic();
            router.get(`/game/play/${props.session.lien_token}`);
        },
        onError: () => { isStarting.value = false; }
    });
};

let unsubscribeBefore = null;
onMounted(() => {
    playBackgroundMusic('lobby');
    if (window.Echo) {
        window.Echo.channel(`lobby.${props.session.lien_token}`)
            .listen('.App\\Events\\LobbyUpdated', () => {
                router.reload({ only: ['session'] });
            });
    }
    unsubscribeBefore = router.on('before', () => {
        if (window.Echo) window.Echo.leave(`lobby.${props.session.lien_token}`);
        stopBackgroundMusic();
    });
});

onUnmounted(() => {
    if (unsubscribeBefore) unsubscribeBefore();
    if (window.Echo) window.Echo.leave(`lobby.${props.session.lien_token}`);
    stopBackgroundMusic();
});

const isCreator = computed(() =>
    props.session.players?.[0]?.user_id === page.props.auth.user.id
);

const canStart = computed(() =>
    props.session.players.length >= props.session.max_joueurs || props.session.type === 'solo'
);

watch(() => props.session.players, (newPlayers, oldPlayers) => {
    if (oldPlayers && newPlayers.length > oldPlayers.length) {
        const newPlayer = newPlayers.find(np => !oldPlayers.some(op => op.id === np.id));
        if (newPlayer && newPlayer.user_id !== page.props.auth.user.id) {
            playNotification();
            toast.add({
                severity: 'info',
                summary: '👤 Nouveau joueur !',
                detail: `${newPlayer.user.name} a rejoint la salle.`,
                life: 3000
            });
        }
    }
}, { deep: true });

watch(() => props.session?.statut, (newStatus) => {
    if (newStatus === 'en_cours' && !isCreator.value) {
        router.get(`/game/play/${props.session.lien_token}`);
    }
}, { immediate: true });
</script>

<template>
    <AuthenticatedLayout title="Lobby">
        <Toast position="top-right" />

        <!-- Conteneur centré, pas de scroll forcé sur phone -->
        <div class="flex items-start justify-center min-h-[calc(100dvh-8rem)]">
            <div class="w-full max-w-lg animate-fade-in-up">

                <!-- Header -->
                <div class="text-center mb-4">
                    <div class="inline-flex items-center gap-2 bg-[#1C1D24] border border-[#26272F] rounded-full px-4 py-1.5 mb-3">
                        <span class="w-2 h-2 rounded-full bg-[#2fc276] animate-pulse"></span>
                        <span class="text-[8px] font-black tracking-widest text-gray-400 uppercase">
                            Mode : <span class="text-white">{{ session.type }}</span>
                            <span v-if="session.type === 'challengers'"> · {{ session.challenger_mode === 'reponse_par_membre' ? 'Rapide' : 'Complet' }}</span>
                        </span>
                    </div>
                    <h1 class="text-3xl sm:text-4xl font-black uppercase italic tracking-tighter text-[#2fc276] text-glow-green">
                        LOBBY 🎮
                    </h1>
                </div>

                <!-- Panel principal -->
                <div class="panel-glass p-4 sm:p-6 border border-[#26272F] space-y-4">

                    <!-- Lien d'invitation -->
                    <div class="bg-[#0D0E12] border border-[#26272F] rounded-xl p-3 flex items-center gap-3">
                        <div class="flex-1 min-w-0">
                            <p class="text-[7px] text-gray-500 font-black uppercase tracking-widest mb-0.5">Lien d'invitation</p>
                            <p class="text-xs font-black text-[#2fc276] truncate">.../lobby/{{ session.lien_token }}</p>
                        </div>
                        <button @click="copyLinkWithSound"
                            class="btn-3d btn-3d-green px-3 py-2.5 text-[9px] shadow-[0_3px_0_#1e7d4b] flex items-center gap-1.5 shrink-0">
                            <Copy :size="12" /> Copier
                        </button>
                    </div>

                    <!-- Joueurs -->
                    <div>
                        <div class="flex justify-between items-center mb-2.5">
                            <div class="flex items-center gap-2">
                                <Users :size="14" class="text-gray-400" />
                                <span class="text-[9px] font-black uppercase tracking-widest text-gray-400">Joueurs</span>
                            </div>
                            <span class="text-xs font-black text-white tabular-nums">
                                {{ session.players.length }}<span class="text-gray-500"> / {{ session.max_joueurs }}</span>
                            </span>
                        </div>

                        <ul class="space-y-2">
                            <!-- Joueurs connectés -->
                            <li v-for="player in session.players" :key="player.id"
                                class="flex items-center gap-3 bg-[#1C1D24] px-3 py-2.5 rounded-xl border border-[#26272F] animate-fade-in-up">
                                <div class="w-9 h-9 rounded-full border-2 border-[#2fc276] bg-[#0D0E12] text-[#2fc276] flex items-center justify-center font-black text-[11px] shrink-0">
                                    {{ player.user.name.substring(0, 2).toUpperCase() }}
                                </div>
                                <span class="font-black text-white text-sm flex-1 truncate">{{ player.user.name }}</span>
                                <span v-if="player.user_id === $page.props.auth.user.id"
                                    class="text-[7px] font-black bg-[#2fc276]/10 border border-[#2fc276]/20 text-[#2fc276] px-2 py-0.5 rounded-full uppercase shrink-0">
                                    Vous
                                </span>
                                <span class="text-[7px] font-black bg-[#2fc276]/10 border border-[#2fc276]/20 text-[#2fc276] px-2 py-0.5 rounded-full uppercase shrink-0">✓ Prêt</span>
                            </li>

                            <!-- Slots vides -->
                            <li v-for="i in (session.max_joueurs - session.players.length)" :key="'e'+i"
                                class="flex items-center gap-3 px-3 py-2.5 rounded-xl border-2 border-dashed border-[#26272F] opacity-40">
                                <div class="w-9 h-9 rounded-full border-2 border-dashed border-[#26272F] flex items-center justify-center text-gray-600 font-black text-sm shrink-0">?</div>
                                <span class="text-gray-500 font-bold italic text-xs">En attente...</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Actions -->
                    <div v-if="isCreator">
                        <button @click="startGame"
                            :disabled="!canStart || isStarting"
                            class="w-full py-4 text-sm font-black"
                            :class="canStart && !isStarting
                                ? 'btn-3d btn-3d-green shadow-[0_5px_0_#1e7d4b]'
                                : 'rounded-xl bg-[#1C1D24] border border-[#26272F] text-gray-500 cursor-not-allowed uppercase tracking-widest'">
                            <template v-if="isStarting">
                                <span class="flex items-center justify-center gap-2">
                                    <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                    Démarrage...
                                </span>
                            </template>
                            <template v-else-if="canStart">
                                <span class="flex items-center justify-center gap-2">
                                    <Rocket :size="16" /> LANCER LA PARTIE !
                                </span>
                            </template>
                            <template v-else>
                                <span class="flex items-center justify-center gap-2">
                                    <Clock :size="14" /> ATTENDRE LES JOUEURS ({{ session.players.length }}/{{ session.max_joueurs }})
                                </span>
                            </template>
                        </button>
                    </div>

                    <p v-else class="text-gray-400 font-semibold italic text-center text-xs py-2 animate-pulse flex items-center justify-center gap-2">
                        <Clock :size="14" /> En attente de l'hôte pour démarrer...
                    </p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.animate-fade-in-up {
    animation: fadeInUp 0.3s ease-out forwards;
}
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(8px); }
    to   { opacity: 1; transform: translateY(0); }
}
</style>
