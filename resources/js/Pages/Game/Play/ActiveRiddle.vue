<script setup>
import { ref, onMounted, computed, onUnmounted, watch, nextTick } from 'vue';
import {
    Trophy, CheckCircle2, Pause, Play as PlayIcon, LogOut, Skull,
    RotateCcw, AlertTriangle, Compass, Rocket, ChevronRight,
    Lightbulb, PauseCircle, MapPin, Clock
} from 'lucide-vue-next';
import { router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import Toast from 'primevue/toast';
import axios from 'axios';
import { userStatsStore } from '@/store.js';
import { useAudio } from '@/composables/useAudio.js';
import { useGameRealtime } from '@/composables/useGameRealtime.js';
import AudioWidget from '@/Components/AudioWidget.vue';

const {
    playClick, playKey, playCountdown,
    playWin, playLose, playAlreadySolved, playGameStart,
    playBackgroundMusic, stopBackgroundMusic,
    pauseBackgroundMusic, resumeBackgroundMusic,
    initAudioContext,
} = useAudio();

const props = defineProps({ session: Object, gameSteps: Array });

const page    = usePage();
const toast   = useToast();
const confirm = useConfirm();

// ── localStorage ─────────────────────────────────────────────────────────
const storageKey    = `game_state_${props.session.id}`;
const loadState     = () => { try { const s = localStorage.getItem(storageKey); return s ? JSON.parse(s) : null; } catch { return null; } };
const saveState     = () => {
    try {
        localStorage.setItem(storageKey, JSON.stringify({
            currentPlaceIndex: currentPlaceIndex.value,
            modeChoisi:        modeChoisi.value,
            isPlaying:         isPlaying.value,
            timeLeft:          timeLeft.value,
            totalTime:         totalTime.value,
            endTime:           endTime.value,
            isPaused:          isPaused.value,
            decisionState:     decisionState.value,
            transportMode:     transportMode.value,
            timestamp:         Date.now(),
        }));
    } catch {}
};
const clearState = () => { try { localStorage.removeItem(storageKey); } catch {} };
const saved = loadState();

// ── État réactif ──────────────────────────────────────────────────────────
const currentPlaceIndex      = ref(saved?.currentPlaceIndex ?? 0);
const modeChoisi             = ref(saved?.modeChoisi ?? null);
const isPlaying              = ref(saved?.isPlaying ?? false);
const timeLeft               = ref(saved?.timeLeft ?? 0);
const totalTime              = ref(saved?.totalTime ?? 0);
const endTime                = ref(saved?.endTime ?? null);
const isPaused               = ref(saved?.isPaused ?? false);
const userAnswer             = ref('');
const userCoords             = ref({ lat: null, lng: null });
const transportMode          = ref(saved?.transportMode ?? null);
const showTransportSelection = ref(false);
const showHint               = ref(false);
const decisionState          = ref(saved?.decisionState ?? null);
const alreadySolvedMessage   = ref('');
const isNavigating           = ref(false);
const isLoading              = ref(false);
const sessionEndHandled      = ref(false);
let   hintTimeout            = null;
let   timerInterval          = null;
let   recordPromise          = null;

// ── Mode du joueur courant ────────────────────────────────────────────────
const currentPlayer = computed(() =>
    props.session.players?.find(p => p.user_id === page.props.auth.user.id)
);
const globalMode = computed(() => currentPlayer.value?.global_mode ?? 'mixte');

// ── Transports (mode découverte) ──────────────────────────────────────────
const TRANSPORT = {
    pied:    { speed: 5,   margin: 3*60, minTime: 2*60, emoji: '🚶', label: 'À pied',  sub: '~5 km/h'   },
    moto:    { speed: 40,  margin: 5*60, minTime: 3*60, emoji: '🏍️', label: 'Moto',    sub: '~40 km/h'  },
    voiture: { speed: 60,  margin: 5*60, minTime: 3*60, emoji: '🚗', label: 'Voiture', sub: '~60 km/h'  },
    avion:   { speed: 250, margin:10*60, minTime: 5*60, emoji: '✈️', label: 'Avion',   sub: '~250 km/h' },
};

// ── Temps réel ────────────────────────────────────────────────────────────
const {
    lockedRiddles, realtimeAttempts, realtimePlayers,
    sessionEnded: realtimeSessionEnded, riddleNotification,
    subscribe: subscribeRealtime, unsubscribe: unsubscribeRealtime,
} = useGameRealtime(props.session.lien_token, page.props.auth.user.id);

props.gameSteps?.forEach(step => {
    if (step.game_riddle?.statut === 'verrouille') {
        lockedRiddles.value[step.game_riddle.id] = {
            locked_by_player_id: step.game_riddle.locked_by_player_id,
            locked_by_name:      step.game_riddle.locked_by_name,
        };
    }
});

const localSession = ref({
    attempts: props.session.attempts || [],
    players:  props.session.players  || [],
    statut:   props.session.statut,
});

// ── Computed ──────────────────────────────────────────────────────────────
const currentGameRiddleId   = computed(() => props.gameSteps?.[currentPlaceIndex.value]?.game_riddle?.id ?? null);
const isRiddleLocked        = computed(() => !!lockedRiddles.value[currentGameRiddleId.value]);
const riddleLockedBy        = computed(() => lockedRiddles.value[currentGameRiddleId.value]?.locked_by_name ?? null);
const currentPlace          = computed(() => props.gameSteps?.[currentPlaceIndex.value]);
const currentRiddle         = computed(() => currentPlace.value?.riddle);
const riddleImages          = computed(() => currentRiddle.value?.images || []);
const hasNextPlace          = computed(() => (props.gameSteps?.length ?? 0) > currentPlaceIndex.value + 1);
const totalCount            = computed(() => props.gameSteps?.length || 0);
const currentNumber         = computed(() => Math.min(currentPlaceIndex.value + 1, totalCount.value));
const riddlePoints          = computed(() => props.session.level === 'difficile' ? 300 : props.session.level === 'intermediaire' ? 200 : 100);

const parsedMcqOptions = computed(() => {
    if (!currentRiddle.value?.mcq_options) return [];
    try {
        const opts = currentRiddle.value.mcq_options;
        return typeof opts === 'string' ? JSON.parse(opts) : opts;
    } catch { return []; }
});

// En mode difficile, pas de MCQ → saisie libre
// En mode facile/intermédiaire avec MCQ → boutons
const isFreeInput = computed(() =>
    props.session.level === 'difficile' || parsedMcqOptions.value.length === 0
);

const sessionLeaderboard = computed(() =>
    localSession.value.players?.map(p => ({
        user_id: p.user_id,
        name:    p.user?.name || 'Joueur',
        points:  localSession.value.attempts
            ?.filter(a => a.user_id === p.user_id && a.status === 'gagne')
            ?.reduce((s, a) => s + (a.points_earned || 0), 0) || 0,
    })).sort((a, b) => b.points - a.points)
);

const distanceToPlace = computed(() => {
    if (!userCoords.value.lat || !currentPlace.value) return null;
    const R = 6371, { lat: lat1, lng: lon1 } = userCoords.value;
    const lat2 = currentPlace.value.latitude, lon2 = currentPlace.value.longitude;
    const dLat = (lat2-lat1)*Math.PI/180, dLon = (lon2-lon1)*Math.PI/180;
    const a = Math.sin(dLat/2)**2 + Math.cos(lat1*Math.PI/180)*Math.cos(lat2*Math.PI/180)*Math.sin(dLon/2)**2;
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
});

const recommendedTransport = computed(() => {
    const m = distanceToPlace.value ? distanceToPlace.value * 1000 : null;
    if (!m) return '📡 Calcul GPS...';
    if (m < 200)  return '🚶 À pied (tout proche !)';
    if (m < 1000) return '🏃 Marche rapide';
    if (m < 3000) return '🚴 Vélo / trottinette';
    return '🚗 Véhicule motorisé';
});

// ── Indice flash 500ms ────────────────────────────────────────────────────
const triggerHint = () => {
    if (showHint.value) return;
    playClick();
    showHint.value = true;
    if (hintTimeout) clearTimeout(hintTimeout);
    hintTimeout = setTimeout(() => { showHint.value = false; }, 500);
};

// ── Lock navigation en jeu ────────────────────────────────────────────────
let navLocked = false;
const handlePopState = () => {
    if (!sessionEndHandled.value) {
        window.history.pushState(null, '', window.location.href);
        toast.add({ severity: 'warn', summary: '🚫 Navigation bloquée', detail: 'Utilisez "Pause" ou "Sauvegarder" pour quitter.', life: 3000 });
    }
};
const lockNav   = () => { if (navLocked) return; navLocked = true; window.history.pushState(null, '', window.location.href); window.addEventListener('popstate', handlePopState); };
const unlockNav = () => { navLocked = false; window.removeEventListener('popstate', handlePopState); };

// ── Fin de session ────────────────────────────────────────────────────────
const finishSession = (msg) => {
    if (sessionEndHandled.value) return;
    sessionEndHandled.value = true;
    clearInterval(timerInterval); timerInterval = null;
    stopBackgroundMusic();
    unlockNav();
    clearState();
    toast.add({ severity: 'success', summary: '🏆 Session terminée !', detail: msg || 'Bravo, aventure complète !', life: 3000 });
    router.get(route('game.dashboard'));
};

// ── Timer (découverte uniquement) ─────────────────────────────────────────
const startChrono = () => {
    if (timerInterval) clearInterval(timerInterval);
    timerInterval = setInterval(() => {
        if (isPaused.value) return;
        timeLeft.value = Math.max(0, Math.ceil((endTime.value - Date.now()) / 1000));
        saveState();
        if (timeLeft.value <= 5 && timeLeft.value > 0) playCountdown();
        if (timeLeft.value <= 0) {
            clearInterval(timerInterval); timerInterval = null;
            // Temps écoulé → perte automatique de cette énigme et passage à la suivante
            handleDiscoveryTimeout();
        }
    }, 100);
};

const calcChronoDiscovery = (transport) => {
    const cfg  = TRANSPORT[transport || transportMode.value || 'pied'] || TRANSPORT.pied;
    const dist = distanceToPlace.value;
    if (!dist) return Math.max(cfg.minTime, cfg.margin + Math.round(1/cfg.speed*3600));
    return Math.max(cfg.minTime, Math.round((dist/cfg.speed)*3600) + cfg.margin);
};

// ── Démarrer l'énigme ─────────────────────────────────────────────────────
const startRiddle = (mode) => {
    initAudioContext();
    // Ne pas relancer la musique — useAudio.js la continue si elle tourne déjà
    playGameStart();
    modeChoisi.value             = mode;
    isPlaying.value              = true;
    decisionState.value          = null;
    userAnswer.value             = '';
    showHint.value               = false;
    isLoading.value              = false;
    showTransportSelection.value = false;

    if (mode === 'decouverte') {
        totalTime.value = calcChronoDiscovery();
        timeLeft.value  = totalTime.value;
        endTime.value   = Date.now() + totalTime.value * 1000;
        startChrono();
    }
    // Gaming : pas de chrono, pas de totalTime
};

// ── Logique de lancement selon globalMode ─────────────────────────────────
const launchNextRiddle = () => {
    decisionState.value = null;
    userAnswer.value    = '';
    showHint.value      = false;

    if (globalMode.value === 'gaming') {
        startRiddle('gaming');
    } else if (globalMode.value === 'decouverte') {
        transportMode.value          = null;
        showTransportSelection.value = true;
        isPlaying.value              = false;
    } else {
        // mixte : choix du mode avant chaque énigme
        modeChoisi.value             = null;
        isPlaying.value              = false;
        showTransportSelection.value = false;
    }
};

// ── Win / Lose / Timeout découverte ──────────────────────────────────────
const handleWin = async () => {
    clearInterval(timerInterval); timerInterval = null;
    decisionState.value = 'win';
    playWin();
    userStatsStore.addPoints(riddlePoints.value);
    await recordAttempt('gagne', riddlePoints.value);
};

const handleLose = async () => {
    clearInterval(timerInterval); timerInterval = null;
    decisionState.value = 'lose';
    playLose();
    await recordAttempt('perdu', 0);
};

// Temps écoulé en découverte → perte auto + passage suivant
const handleDiscoveryTimeout = async () => {
    decisionState.value = 'timeout_decouverte';
    playLose();
    await recordAttempt('perdu', 0);
    // Auto-avance après 2 secondes
    setTimeout(() => {
        if (decisionState.value === 'timeout_decouverte') goToNextPlace();
    }, 2000);
};

// Mauvaise position GPS → perte + passage suivant
const handleDiscoveryWrongPosition = async (distanceM, marginM) => {
    toast.add({
        severity: 'error',
        summary: 'Trop loin 📍',
        detail: `Vous êtes à ~${Math.round(distanceM)}m du lieu (marge : ${marginM}m).`,
        life: 4000,
    });
    clearInterval(timerInterval); timerInterval = null;
    decisionState.value = 'lose_discovery';
    playLose();
    await recordAttempt('perdu', 0);
};

// ── Enregistrement backend ────────────────────────────────────────────────
const recordAttempt = (status, pts) => {
    isLoading.value = true;
    recordPromise = (async () => {
        try {
            const needsLock = (props.session.type === 'participants') ||
                (props.session.type === 'challengers' && props.session.challenger_mode === 'reponse_par_membre');

            if (needsLock) {
                lockedRiddles.value[currentGameRiddleId.value] = {
                    locked_by_user_id: page.props.auth.user.id,
                    locked_by_name:    page.props.auth.user.name,
                };
                try {
                    const lr = await axios.post('/game/play/lock', {
                        session_id: props.session.id,
                        riddle_id:  currentRiddle.value.id,
                    });
                    if (!lr.data.locked) {
                        const msg = lr.data.message ?? 'Un joueur a verrouillé cette énigme !';
                        toast.add({ severity: 'warn', summary: 'Trop tard ! 🔒', detail: msg, life: 5000 });
                        decisionState.value = 'already_solved';
                        alreadySolvedMessage.value = msg;
                        return false;
                    }
                } catch (le) {
                    if (le.response?.status === 409) {
                        const msg = le.response.data?.message ?? 'Un joueur a déjà verrouillé.';
                        toast.add({ severity: 'warn', summary: 'Trop tard ! 🔒', detail: msg, life: 5000 });
                        decisionState.value = 'already_solved';
                        alreadySolvedMessage.value = msg;
                        return false;
                    }
                    throw le;
                }
            }

            const res = await axios.post('/game/play/record', {
                session_id:       props.session.id,
                riddle_id:        currentRiddle.value.id,
                status,
                points:           pts,
                mode_choisi:      modeChoisi.value || 'gaming',
                transport_mode:   transportMode.value ?? null,
                temps_resolution: modeChoisi.value === 'decouverte' ? (totalTime.value - timeLeft.value) : 0,
            });

            if (res.data?.session_finished) { finishSession(res.data?.message); return false; }
            if (res.data?.already_solved) {
                toast.add({ severity: 'warn', summary: 'Déjà clôturée ⚠️', detail: res.data.message, life: 5000 });
                decisionState.value = 'already_solved';
                alreadySolvedMessage.value = res.data.message;
                return false;
            }

            await router.reload({ only: ['session'] });
            return true;
        } catch (e) {
            console.error('Erreur backend:', e);
            return false;
        } finally {
            isLoading.value = false;
            recordPromise   = null;
        }
    })();
    return recordPromise;
};

// ── Navigation suivant ────────────────────────────────────────────────────
const goToNextPlace = () => {
    if (isNavigating.value) return;
    isNavigating.value = true;
    playClick();

    if (props.session.statut === 'termine') { finishSession(); return; }

    if (hasNextPlace.value) {
        currentPlaceIndex.value++;
        nextTick(() => { isNavigating.value = false; launchNextRiddle(); });
    } else {
        finishSession('Tu as complété toutes les énigmes ! 🎉');
    }
};

// ── Gaming : soumission ───────────────────────────────────────────────────
const submitQcm = (opt) => {
    if (isLoading.value) return;
    playClick();
    userAnswer.value = opt;
    checkGamingAnswer();
};
const checkGamingAnswer = async () => {
    if (isLoading.value) return;
    if (userAnswer.value.trim().toLowerCase() === currentRiddle.value.reponse.trim().toLowerCase()) {
        await handleWin();
    } else {
        await handleLose();
    }
};

// ── Découverte : validation GPS ───────────────────────────────────────────
const submitDiscovery = async () => {
    if (isLoading.value) return;
    if (!distanceToPlace.value) {
        toast.add({ severity: 'warn', summary: 'GPS requis', detail: 'Signal introuvable.', life: 4000 });
        return;
    }
    const margin = (currentPlace.value?.marge_validation_gps || currentPlace.value?.rayon_marge || 50);
    const distM  = distanceToPlace.value * 1000;
    if (distM <= margin) {
        await handleWin();
    } else {
        // Mauvaise position → perte + passage au lieu suivant automatiquement
        await handleDiscoveryWrongPosition(distM, margin);
    }
};

// ── Pause ─────────────────────────────────────────────────────────────────
const togglePause = () => {
    playClick();
    isPaused.value = !isPaused.value;
    if (isPaused.value) {
        pauseBackgroundMusic();
    } else {
        endTime.value = Date.now() + timeLeft.value * 1000;
        resumeBackgroundMusic();
    }
};

// ── Sauvegarder et quitter (mode découverte uniquement) ───────────────────
// Différent de "Abandonner" : la session est mise en pause, réapparaît
// dans le dashboard, le joueur peut reprendre exactement là où il était.
const saveAndLeave = async () => {
    playClick();
    if (modeChoisi.value !== 'decouverte' && globalMode.value === 'gaming') {
        toast.add({ severity: 'warn', summary: 'Mode gaming', detail: 'La sauvegarde mid-session n\'est disponible qu\'en mode découverte.', life: 4000 });
        return;
    }
    try {
        isPaused.value = true;
        pauseBackgroundMusic();
        saveState(); // Sauvegarde l'état local (index, temps restant, transport, etc.)
        await axios.post('/game/play/pause', { session_id: props.session.id });
        clearInterval(timerInterval); timerInterval = null;
        unlockNav();
        sessionEndHandled.value = true;
        router.get(route('game.dashboard'));
    } catch {
        toast.add({ severity: 'error', summary: 'Erreur', detail: 'Impossible de sauvegarder.', life: 3000 });
        isPaused.value = false;
    }
};

// ── Abandonner (définitif) ────────────────────────────────────────────────
const forfeitSession = () => {
    playClick();
    confirm.require({
        message:     'Abandonner ? Votre progression sera perdue définitivement.',
        header:      'Abandonner ⚠️',
        rejectLabel: 'Annuler',
        acceptLabel: 'Abandonner',
        rejectClass: 'p-button-secondary p-button-outlined text-gray-300 border-gray-600 px-4 py-2 rounded-lg mr-2',
        acceptClass: 'p-button-danger bg-red-600 text-white px-4 py-2 rounded-lg',
        accept: () => {
            clearState();
            unlockNav();
            stopBackgroundMusic();
            router.get(route('game.dashboard'));
        },
    });
};

// ── Utilitaires ───────────────────────────────────────────────────────────
const formatTime = (s) => `${Math.floor(s/60).toString().padStart(2,'0')}:${(s%60).toString().padStart(2,'0')}`;

const handleVisibility = () => {
    if (document.visibilityState === 'visible' && isPlaying.value && !isPaused.value
        && modeChoisi.value === 'decouverte' && !decisionState.value) {
        timeLeft.value = Math.max(0, Math.ceil((endTime.value - Date.now()) / 1000));
        if (timeLeft.value <= 0) handleDiscoveryTimeout();
    }
};

let watchId = null, unsubBefore = null;

// ── Lifecycle ─────────────────────────────────────────────────────────────
onMounted(() => {
    if (props.session.statut === 'termine') { finishSession(); return; }

    // Reprendre un état sauvegardé (session reprise depuis dashboard)
    if (saved?.isPlaying && saved?.modeChoisi === 'decouverte' && saved?.endTime) {
        const rem = Math.max(0, Math.floor((saved.endTime - Date.now()) / 1000));
        if (rem > 0) {
            timeLeft.value  = rem;
            totalTime.value = saved.totalTime;
            endTime.value   = saved.endTime;
            isPlaying.value = true;
            if (!isPaused.value) startChrono();
        } else {
            handleDiscoveryTimeout();
        }
    } else if (saved?.isPlaying && saved?.modeChoisi === 'gaming') {
        isPlaying.value = true;
    } else {
        launchNextRiddle();
    }

    playBackgroundMusic('game');
    document.addEventListener('visibilitychange', handleVisibility);
    subscribeRealtime();
    lockNav();

    unsubBefore = router.on('before', () => {
        unsubscribeRealtime();
        if (modeChoisi.value !== 'decouverte') stopBackgroundMusic();
        clearInterval(timerInterval); timerInterval = null;
        if (watchId) { navigator.geolocation.clearWatch(watchId); watchId = null; }
    });

    if (navigator.geolocation) {
        watchId = navigator.geolocation.watchPosition(
            p => { userCoords.value.lat = p.coords.latitude; userCoords.value.lng = p.coords.longitude; },
            () => {},
            { enableHighAccuracy: true }
        );
    }
});

onUnmounted(() => {
    if (hintTimeout) clearTimeout(hintTimeout);
    if (!sessionEndHandled.value) saveState();
    else clearState();
    unlockNav();
    if (unsubBefore) unsubBefore();
    unsubscribeRealtime();
    document.removeEventListener('visibilitychange', handleVisibility);
    if (modeChoisi.value !== 'decouverte') stopBackgroundMusic();
    if (watchId) navigator.geolocation.clearWatch(watchId);
    clearInterval(timerInterval);
});

// ── Watchers ──────────────────────────────────────────────────────────────
watch([currentPlaceIndex, modeChoisi, isPlaying, timeLeft, isPaused, decisionState], saveState, { deep: true });
watch(realtimeAttempts, v => { if (v.length) localSession.value.attempts = v; }, { deep: true });
watch(realtimePlayers,  v => { if (v.length) localSession.value.players  = v; }, { deep: true });
watch(realtimeSessionEnded, v => { if (v && !sessionEndHandled.value) finishSession(); });
watch(riddleNotification, notif => {
    if (!notif || notif.game_riddle_id !== currentGameRiddleId.value || decisionState.value) return;
    playAlreadySolved();
    clearInterval(timerInterval);
    decisionState.value = 'already_solved';
    alreadySolvedMessage.value = notif.message;
    toast.add({ severity: 'warn', summary: 'Énigme verrouillée 🔒', detail: notif.message, life: 5000 });
});
watch(() => props.session, s => {
    if (s) { localSession.value.attempts = s.attempts || []; localSession.value.players = s.players || []; }
}, { deep: true });
watch(() => props.session.statut, (s, p) => {
    if (s === 'termine' && p !== 'termine') finishSession();
});
</script>

<template>
    <AuthenticatedLayout title="En Jeu" :hideBottomNav="true">
        <Toast position="top-right" />

        <div class="flex flex-col gap-3 -mx-3 sm:-mx-6 lg:-mx-10 -mt-4 sm:-mt-6 px-3 sm:px-6 pt-2 pb-4">

            <!-- SCOREBOARD (multi) -->
            <div v-if="session.type !== 'solo'" class="panel-glass px-3 py-2.5 border border-[#26272F]">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-1.5">
                        <Trophy :size="13" class="text-[#f3a900]" />
                        <span class="text-[8px] font-black uppercase tracking-widest text-gray-400">Live</span>
                    </div>
                    <!-- Points du joueur courant affichés en temps réel -->
                    <span class="text-[9px] font-black text-[#87d74e]">
                        {{ userStatsStore.points }} XP
                    </span>
                </div>
                <div class="flex gap-2 overflow-x-auto pb-0.5">
                    <div v-for="(p, i) in sessionLeaderboard" :key="p.user_id"
                        class="flex items-center gap-2 bg-[#1C1D24] px-2.5 py-1.5 rounded-lg border border-[#26272F] shrink-0"
                        :class="p.user_id === page.props.auth.user.id ? 'border-[#2fc276]/40' : ''">
                        <span class="text-[9px] font-black text-gray-500">{{ i+1 }}</span>
                        <span class="text-[10px] font-black" :class="p.user_id === page.props.auth.user.id ? 'text-[#2fc276]' : 'text-white'">{{ p.name.split(' ')[0] }}</span>
                        <span class="text-[8px] font-black text-gray-500">{{ p.points }} XP</span>
                    </div>
                </div>
            </div>

            <!-- CHOIX MODE (mixte : avant chaque énigme) -->
            <div v-if="globalMode === 'mixte' && !modeChoisi && !decisionState"
                class="panel-glass p-4 sm:p-6 border border-[#26272F] animate-fade-in-up">
                <div class="text-center mb-4">
                    <span class="text-[9px] font-black tracking-widest text-[#87d74e] uppercase block mb-1">
                        Énigme {{ currentNumber }} / {{ totalCount }}
                    </span>
                    <h2 class="text-xl font-black uppercase italic tracking-tighter text-white">
                        Comment jouer <span class="text-[#87d74e]">cette énigme ?</span>
                    </h2>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-[#1C1D24] p-4 rounded-2xl border-2 border-[#f3a900]/30 flex flex-col items-center gap-3">
                        <span class="text-3xl">🗺️</span>
                        <div class="text-center">
                            <p class="text-sm font-black text-[#f3a900] uppercase">Découverte</p>
                            <p class="text-[9px] text-gray-400 mt-0.5">Validation GPS sur place.</p>
                        </div>
                        <button @click="() => { playClick(); transportMode = null; showTransportSelection = true; modeChoisi = 'decouverte_pending'; }"
                            class="btn-3d btn-3d-yellow w-full py-2.5 text-[9px] shadow-[0_4px_0_#9e6f00]">
                            Choisir transport
                        </button>
                    </div>
                    <div class="bg-[#1C1D24] p-4 rounded-2xl border-2 border-[#2c72f6]/30 flex flex-col items-center gap-3">
                        <span class="text-3xl">🎮</span>
                        <div class="text-center">
                            <p class="text-sm font-black text-[#2c72f6] uppercase">Gaming</p>
                            <p class="text-[9px] text-gray-400 mt-0.5">Répondez sans se déplacer.</p>
                        </div>
                        <button @click="startRiddle('gaming')" :disabled="isRiddleLocked"
                            class="btn-3d btn-3d-blue w-full py-2.5 text-[9px] shadow-[0_4px_0_#1344a1]">
                            C'est parti !
                        </button>
                    </div>
                </div>
            </div>

            <!-- SÉLECTION TRANSPORT (découverte) -->
            <div v-if="showTransportSelection && !decisionState"
                class="panel-glass p-4 border border-[#26272F] animate-fade-in-up">
                <div class="text-center mb-4">
                    <h2 class="text-base font-black uppercase italic tracking-tighter text-white">
                        Comment vous <span class="text-[#f3a900]">déplacez-vous ?</span>
                    </h2>
                    <p class="text-[9px] text-gray-400 mt-0.5">Le chrono s'adapte à la distance + vitesse.</p>
                </div>
                <div class="grid grid-cols-2 gap-2.5">
                    <div v-for="(cfg, key) in TRANSPORT" :key="key"
                        @click="() => { playClick(); transportMode = key; showTransportSelection = false; startRiddle('decouverte'); }"
                        class="cursor-pointer bg-[#1C1D24] p-3.5 rounded-2xl border-2 border-[#f3a900]/20 hover:border-[#f3a900] active:scale-95 transition-all flex flex-col items-center gap-1.5">
                        <span class="text-3xl">{{ cfg.emoji }}</span>
                        <p class="text-[10px] font-black text-white uppercase">{{ cfg.label }}</p>
                        <p class="text-[8px] text-gray-500 font-bold">{{ cfg.sub }}</p>
                    </div>
                </div>
                <button v-if="globalMode === 'mixte'" @click="showTransportSelection = false; modeChoisi = null;"
                    class="mt-3 w-full text-[9px] text-gray-500 hover:text-white transition-colors font-bold uppercase tracking-widest py-2">
                    ← Changer de mode
                </button>
            </div>

            <!-- ZONE ÉNIGME ACTIVE -->
            <div v-if="isPlaying && currentRiddle && !showTransportSelection"
                class="panel-glass border border-[#26272F] relative overflow-hidden animate-fade-in-up">

                <!-- OVERLAY PAUSE -->
                <div v-if="isPaused"
                    class="absolute inset-0 bg-[#0D0E12]/97 backdrop-blur-md z-20 flex flex-col items-center justify-center rounded-[24px]">
                    <PauseCircle :size="52" class="text-[#f3a900] mb-4 animate-pulse" />
                    <h2 class="text-2xl font-black uppercase italic text-[#f3a900] mb-2">En Pause</h2>

                    <!-- En découverte : option sauvegarder & quitter -->
                    <p v-if="modeChoisi === 'decouverte'" class="text-xs text-gray-400 text-center mb-5 max-w-xs">
                        Vous pouvez sauvegarder et reprendre cette aventure plus tard depuis votre dashboard.
                    </p>

                    <div class="flex flex-col gap-2 w-full max-w-xs">
                        <button @click="togglePause"
                            class="btn-3d btn-3d-yellow w-full py-3 text-xs shadow-[0_5px_0_#9e6f00]">
                            ▶ REPRENDRE
                        </button>
                        <button v-if="modeChoisi === 'decouverte'" @click="saveAndLeave"
                            class="btn-3d btn-3d-blue w-full py-2.5 text-[9px] shadow-[0_4px_0_#1344a1] flex items-center justify-center gap-1.5">
                            <Clock :size="13" /> Sauvegarder & quitter
                        </button>
                        <button @click="forfeitSession"
                            class="w-full py-2 text-[9px] font-black uppercase tracking-widest text-red-400 hover:text-red-300 transition-colors">
                            Abandonner définitivement
                        </button>
                    </div>
                </div>

                <!-- OVERLAY DÉCISION -->
                <div v-if="decisionState && !isPaused"
                    class="absolute inset-0 bg-[#0D0E12]/97 backdrop-blur-md z-30 flex flex-col items-center justify-center p-5 rounded-[24px] text-center">

                    <!-- WIN -->
                    <template v-if="decisionState === 'win'">
                        <div class="relative mb-3">
                            <div class="absolute inset-0 bg-[#2fc276]/20 blur-xl rounded-full"></div>
                            <Trophy :size="52" class="text-[#2fc276] relative animate-bounce" />
                        </div>
                        <h2 class="text-2xl font-black text-[#2fc276] text-glow-green uppercase italic mb-1">Résolu !</h2>
                        <p class="text-xs text-gray-400 mb-1">
                            +<span class="text-[#f3a900] font-black text-lg">{{ riddlePoints }} XP</span>
                        </p>
                        <!-- Info lieu + itinéraire -->
                        <div class="w-full max-w-sm bg-white/5 border border-white/10 rounded-2xl p-3 mb-4 text-left">
                            <p class="text-[8px] font-black uppercase text-[#2fc276] mb-1">📍 {{ currentPlace?.nom }}</p>
                            <p class="text-[10px] text-gray-300 leading-relaxed italic line-clamp-3">
                                "{{ currentPlace?.verified_description || 'Un lieu emblématique à découvrir !' }}"
                            </p>
                            <a :href="`https://www.google.com/maps/dir/?api=1&destination=${currentPlace?.latitude},${currentPlace?.longitude}`"
                               target="_blank" @click.stop="playClick"
                               class="mt-2 flex items-center justify-center gap-1.5 w-full py-1.5 bg-white/5 hover:bg-white/10 border border-white/10 rounded-xl text-[8px] font-black uppercase text-white transition-all">
                                <Compass :size="11" /> Itinéraire GPS
                            </a>
                        </div>
                        <button @click="goToNextPlace" :disabled="isNavigating"
                            class="btn-3d btn-3d-green w-full max-w-sm py-3.5 text-sm shadow-[0_5px_0_#1e7d4b] flex items-center justify-center gap-2 disabled:opacity-50">
                            <RotateCcw v-if="isNavigating" :size="16" class="animate-spin" />
                            <template v-else>{{ hasNextPlace ? 'Énigme suivante →' : '🏁 Terminer !' }}</template>
                        </button>
                    </template>

                    <!-- LOSE (gaming : mauvaise réponse) -->
                    <template v-if="decisionState === 'lose'">
                        <div class="relative mb-3">
                            <div class="absolute inset-0 bg-red-500/20 blur-xl rounded-full"></div>
                            <Skull :size="52" class="text-red-500 relative animate-pulse" />
                        </div>
                        <h2 class="text-2xl font-black text-red-400 uppercase italic mb-1">Mauvaise réponse</h2>
                        <p class="text-xs text-gray-400 mb-1">La bonne réponse était :</p>
                        <p class="text-base font-black text-white bg-white/5 border border-white/10 rounded-xl px-4 py-2 mb-4">
                            {{ currentRiddle.reponse }}
                        </p>
                        <div class="flex flex-col gap-2 w-full max-w-sm">
                            <button @click="goToNextPlace" :disabled="isNavigating"
                                class="btn-3d btn-3d-yellow w-full py-3 text-xs shadow-[0_4px_0_#9e6f00] text-black flex items-center justify-center gap-1.5">
                                <template v-if="!isNavigating">{{ hasNextPlace ? 'Suivant →' : '🏁 Terminer !' }}</template>
                                <template v-else><RotateCcw :size="14" class="animate-spin" /></template>
                            </button>
                            <button @click="forfeitSession"
                                class="w-full py-2 text-[9px] font-black uppercase tracking-widest text-red-400 hover:text-red-300 transition-colors">
                                Abandonner
                            </button>
                        </div>
                    </template>

                    <!-- LOSE DÉCOUVERTE (mauvaise position ou timeout) -->
                    <template v-if="decisionState === 'lose_discovery' || decisionState === 'timeout_decouverte'">
                        <AlertTriangle :size="52" class="text-[#f3a900] mb-3" />
                        <h2 class="text-xl font-black text-[#f3a900] uppercase italic mb-1">
                            {{ decisionState === 'timeout_decouverte' ? 'Temps écoulé ⏱️' : 'Lieu raté 📍' }}
                        </h2>
                        <p class="text-xs text-gray-400 mb-3">
                            {{ decisionState === 'timeout_decouverte'
                                ? 'Vous n\'avez pas pu valider votre présence à temps.'
                                : 'Vous n\'étiez pas au bon endroit.' }}
                        </p>

                        <!-- Option "Voir l'endroit" uniquement en solo -->
                        <div v-if="session.type === 'solo'" class="w-full max-w-sm bg-white/5 border border-white/10 rounded-2xl p-3 mb-4 text-left">
                            <p class="text-[8px] font-black uppercase text-[#f3a900] mb-1">💡 Le lieu mystère</p>
                            <p class="text-[10px] text-gray-300 italic line-clamp-3">
                                {{ currentPlace?.verified_description || currentPlace?.nom }}
                            </p>
                            <a :href="`https://www.google.com/maps/search/?api=1&query=${currentPlace?.latitude},${currentPlace?.longitude}`"
                               target="_blank" @click.stop="playClick"
                               class="mt-2 flex items-center justify-center gap-1.5 w-full py-1.5 bg-white/5 hover:bg-white/10 border border-white/10 rounded-xl text-[8px] font-black uppercase text-white transition-all">
                                <Compass :size="11" /> Voir sur la carte
                            </a>
                        </div>

                        <!-- En coop/versus : pas d'info sur le lieu (sera dispo dans l'historique une fois toute l'équipe a répondu) -->
                        <p v-else class="text-[9px] text-gray-500 italic mb-4 max-w-xs">
                            L'emplacement sera visible dans votre historique une fois que tous les joueurs auront répondu à cette énigme.
                        </p>

                        <div class="flex flex-col gap-2 w-full max-w-sm">
                            <button @click="goToNextPlace" :disabled="isNavigating"
                                class="btn-3d btn-3d-blue w-full py-3 text-sm shadow-[0_5px_0_#1344a1] flex items-center justify-center gap-2">
                                <template v-if="!isNavigating">{{ hasNextPlace ? 'Lieu suivant →' : '🏁 Terminer !' }}</template>
                                <template v-else><RotateCcw :size="14" class="animate-spin" /></template>
                            </button>
                            <button @click="forfeitSession"
                                class="w-full py-2 text-[9px] font-black uppercase tracking-widest text-red-400 hover:text-red-300 transition-colors">
                                Abandonner
                            </button>
                        </div>
                    </template>

                    <!-- ALREADY SOLVED -->
                    <template v-if="decisionState === 'already_solved'">
                        <AlertTriangle :size="52" class="text-[#f3a900] mb-3" />
                        <h2 class="text-xl font-black text-[#f3a900] uppercase italic mb-1">Déjà clôturée</h2>
                        <p class="text-xs text-gray-400 mb-5 max-w-xs">{{ alreadySolvedMessage || 'Un coéquipier a déjà répondu.' }}</p>
                        <button @click="goToNextPlace" :disabled="isNavigating"
                            class="btn-3d btn-3d-blue w-full max-w-sm py-3 text-sm shadow-[0_5px_0_#1344a1] flex items-center justify-center gap-2">
                            <template v-if="!isNavigating">{{ hasNextPlace ? 'Suivant →' : '🏁 Terminer !' }}</template>
                            <template v-else><RotateCcw :size="14" class="animate-spin" /></template>
                        </button>
                    </template>
                </div>

                <!-- CONTENU ÉNIGME -->
                <div class="p-3 sm:p-5">

                    <!-- HUD -->
                    <div class="flex items-center gap-2 mb-3">
                        <div class="flex items-center gap-1.5 bg-[#1C1D24] border border-[#26272F] px-2.5 py-1.5 rounded-xl shrink-0">
                            <MapPin :size="11" class="text-[#2fc276]" />
                            <span class="text-[10px] font-black text-white tabular-nums">{{ currentNumber }}/{{ totalCount }}</span>
                        </div>

                        <span class="flex-1 text-center text-[9px] font-black bg-[#2fc276]/10 border border-[#2fc276]/20 text-[#2fc276] px-2.5 py-1.5 rounded-xl truncate">
                            {{ currentPlace?.nom }}
                        </span>

                        <!-- Points courants toujours affichés -->
                        <div class="bg-[#1C1D24] border border-[#26272F] px-2.5 py-1.5 rounded-xl shrink-0 flex items-center gap-1">
                            <Trophy :size="11" class="text-[#f3a900]" />
                            <span class="text-[10px] font-black text-[#f3a900] tabular-nums">{{ userStatsStore.points }}</span>
                        </div>

                        <!-- Chrono découverte -->
                        <div v-if="modeChoisi === 'decouverte'"
                            class="flex items-center gap-1.5 bg-[#1C1D24] border border-[#26272F] px-2.5 py-1.5 rounded-xl shrink-0">
                            <span class="w-1.5 h-1.5 rounded-full animate-ping" :class="timeLeft < 30 ? 'bg-red-500' : 'bg-[#2fc276]'"></span>
                            <span class="text-[11px] font-mono font-black tabular-nums" :class="timeLeft < 30 ? 'text-red-400' : 'text-white'">{{ formatTime(timeLeft) }}</span>
                        </div>
                    </div>

                    <!-- Boutons contrôle -->
                    <div class="flex gap-2 mb-4 justify-end">
                        <button @click="togglePause"
                            class="flex items-center gap-1 px-3 py-1.5 rounded-lg border border-[#26272F] bg-[#1C1D24] text-[9px] font-black uppercase text-[#f3a900] hover:border-[#f3a900]/40 transition-all">
                            <Pause v-if="!isPaused" :size="12" /><PlayIcon v-else :size="12" />
                            {{ isPaused ? 'Reprendre' : 'Pause' }}
                        </button>
                        <!-- Sauvegarder & quitter visible uniquement en découverte -->
                        <button v-if="modeChoisi === 'decouverte'" @click="saveAndLeave"
                            class="flex items-center gap-1 px-3 py-1.5 rounded-lg border border-[#2c72f6]/30 bg-[#1C1D24] text-[9px] font-black uppercase text-[#2c72f6] hover:bg-[#2c72f6]/10 transition-all">
                            <Clock :size="12" /> Sauvegarder
                        </button>
                        <button @click="forfeitSession"
                            class="flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-red-500/20 bg-[#1C1D24] text-[9px] font-black uppercase text-red-400 hover:bg-red-500/10 transition-all">
                            <LogOut :size="12" />
                        </button>
                    </div>

                    <!-- Texte énigme — la réponse n'est JAMAIS visible ici -->
                    <div class="text-center mb-4 px-1">
                        <p class="text-base sm:text-lg text-white font-black italic leading-relaxed">
                            "{{ currentRiddle.description }}"
                        </p>

                        <!-- Indice flash 500ms -->
                        <transition name="hint-flash">
                            <div v-if="showHint"
                                class="inline-flex flex-col items-center mt-3 p-3 bg-amber-500/15 border border-amber-500/40 rounded-2xl w-full max-w-sm">
                                <span class="text-[7px] font-black uppercase tracking-widest text-amber-500/70 mb-1">⚡ Indice</span>
                                <p class="text-sm font-black text-amber-200 italic">
                                    {{ currentRiddle.hints?.[0]?.content || '🤫 Pas d\'indice disponible.' }}
                                </p>
                            </div>
                        </transition>

                        <!-- Images -->
                        <div v-if="riddleImages.length > 0" class="flex gap-2 mt-3 overflow-x-auto pb-1 justify-center">
                            <img v-for="(img, idx) in riddleImages" :key="idx"
                                :src="img.image_path.startsWith('http') ? img.image_path : `/storage/${img.image_path}`"
                                class="h-28 sm:h-36 w-auto rounded-xl border-2 border-white/10 object-cover shrink-0"
                                alt="Visuel" />
                        </div>
                    </div>

                    <!-- Lock coop -->
                    <div v-if="isRiddleLocked && !decisionState"
                        class="flex items-center gap-2 bg-yellow-500/10 border border-yellow-500/30 rounded-xl px-3 py-2 mb-3 text-yellow-400 text-xs font-semibold">
                        🔒 {{ riddleLockedBy }} répond en ce moment...
                    </div>

                    <!-- ACTION DÉCOUVERTE -->
                    <div v-if="modeChoisi === 'decouverte'" class="space-y-2.5">
                        <div class="bg-[#1C1D24] border border-[#26272F] p-3 rounded-xl text-center">
                            <p class="text-[8px] font-black uppercase text-gray-500 tracking-wider mb-0.5">Transport conseillé</p>
                            <span class="text-xs font-black text-white">{{ recommendedTransport }}</span>
                        </div>
                        <button @click="submitDiscovery" :disabled="isLoading"
                            class="btn-3d btn-3d-green w-full py-4 text-sm font-black shadow-[0_6px_0_#1e7d4b] disabled:opacity-50">
                            📍 JE SUIS SUR PLACE — VALIDER
                        </button>
                        <button @click="triggerHint"
                            class="w-full flex items-center justify-center gap-2 py-2.5 rounded-xl border border-amber-500/20 bg-amber-500/5 text-[9px] font-black uppercase tracking-widest text-amber-400 hover:bg-amber-500/10 transition-all active:scale-95">
                            <Lightbulb :size="13" /> Indice (0,5 s)
                        </button>
                    </div>

                    <!-- ACTION GAMING -->
                    <div v-if="modeChoisi === 'gaming'" class="space-y-2.5">
                        <!-- MCQ -->
                        <div v-if="!isFreeInput" class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            <button v-for="opt in parsedMcqOptions" :key="opt"
                                @click="submitQcm(opt)" :disabled="isLoading"
                                class="btn-3d btn-3d-blue py-4 text-xs sm:text-sm shadow-[0_4px_0_#1344a1] text-center disabled:opacity-50 mcq-option">
                                {{ opt }}
                            </button>
                        </div>
                        <!-- Saisie libre (difficile ou pas de MCQ) -->
                        <div v-else class="space-y-2">
                            <input v-model="userAnswer" type="text"
                                placeholder="Votre réponse..."
                                @keydown="playKey"
                                @keydown.enter="checkGamingAnswer"
                                class="w-full bg-[#0D0E12] border-2 border-[#26272F] focus:border-[#2fc276] focus:outline-none rounded-xl p-3 text-base text-center text-white font-black uppercase tracking-widest transition-colors">
                            <button @click="checkGamingAnswer" :disabled="!userAnswer.trim() || isLoading"
                                class="btn-3d btn-3d-green w-full py-3.5 text-sm shadow-[0_5px_0_#1e7d4b] flex items-center justify-center gap-2 disabled:opacity-50">
                                SOUMETTRE <Rocket :size="15" />
                            </button>
                        </div>
                        <!-- Indice -->
                        <button @click="triggerHint"
                            class="w-full flex items-center justify-center gap-2 py-2.5 rounded-xl border border-amber-500/20 bg-amber-500/5 text-[9px] font-black uppercase tracking-widest text-amber-400 hover:bg-amber-500/10 transition-all active:scale-95">
                            <Lightbulb :size="13" /> Indice (0,5 s)
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <AudioWidget />
    </AuthenticatedLayout>
</template>

<style scoped>
.animate-fade-in-up { animation: fadeInUp 0.3s ease-out forwards; }
@keyframes fadeInUp { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }
.hint-flash-enter-active { transition: opacity 0.15s ease, transform 0.15s ease; }
.hint-flash-leave-active { transition: opacity 0.2s ease; }
.hint-flash-enter-from   { opacity: 0; transform: translateY(-4px); }
.hint-flash-leave-to     { opacity: 0; }
</style>