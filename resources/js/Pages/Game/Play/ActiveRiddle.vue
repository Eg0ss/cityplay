<script setup>
import { ref, onMounted, computed, onUnmounted, watch, nextTick } from 'vue';
import { 
    Trophy, CheckCircle2, Pause, Play, LogOut, Map as MapIcon,
    MapPin, Gamepad2, Skull, RotateCcw, AlertTriangle,
    Compass, Rocket, Zap, ChevronRight, X, Target
} from 'lucide-vue-next';
import { Head, router, usePage } from '@inertiajs/vue3';
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
    playBackgroundMusic, stopBackgroundMusic, pauseBackgroundMusic, resumeBackgroundMusic,
    initAudioContext,
} = useAudio();

const props = defineProps({
    session: Object,
    gameSteps: Array
});

const page = usePage();
const toast = useToast();
const confirm = useConfirm();

// ── Storage ───────────────────────────────────────────────────────────
const storageKey = `game_state_${props.session.id}`;
const loadGameState = () => { try { const s = localStorage.getItem(storageKey); return s ? JSON.parse(s) : null; } catch { return null; } };
const saveGameState = () => {
    try {
        localStorage.setItem(storageKey, JSON.stringify({
            currentPlaceIndex: currentPlaceIndex.value,
            modeChoisi: modeChoisi.value,
            isPlaying: isPlaying.value,
            timeLeft: timeLeft.value,
            totalTime: totalTime.value,
            endTime: endTime.value,
            isPaused: isPaused.value,
            decisionState: decisionState.value,
            transportMode: transportMode.value,
            timestamp: Date.now(),
        }));
    } catch {}
};
const clearGameState = () => { try { localStorage.removeItem(storageKey); } catch {} };

const savedState = loadGameState();

// ── État local ────────────────────────────────────────────────────────
const currentPlaceIndex = ref(savedState?.currentPlaceIndex ?? 0);
const modeChoisi        = ref(savedState?.modeChoisi ?? null);
const isPlaying         = ref(savedState?.isPlaying ?? false);
const timeLeft          = ref(savedState?.timeLeft ?? 0);
const totalTime         = ref(savedState?.totalTime ?? 0);
const endTime           = ref(savedState?.endTime ?? null);
const isPaused          = ref(savedState?.isPaused ?? false);
const userAnswer        = ref('');
const userCoords        = ref({ lat: null, lng: null });
const transportMode     = ref(savedState?.transportMode ?? null);
const showTransportSelection = ref(false);
const showFlashHint     = ref(false);
const decisionState     = ref(savedState?.decisionState ?? null);
const alreadySolvedMessage = ref('');
const isNavigating      = ref(false);
const isLoading         = ref(false);
const sessionEndHandled = ref(false);
let recordPromise = null;
let timerInterval = null;

const TRANSPORT_CONFIG = {
    pied:    { speed: 5,   margin: 3*60, minTime: 2*60, emoji: '🚶', label: 'À pied',  sub: '~5 km/h'   },
    moto:    { speed: 40,  margin: 5*60, minTime: 3*60, emoji: '🏍️', label: 'Moto',    sub: '~40 km/h'  },
    voiture: { speed: 60,  margin: 5*60, minTime: 3*60, emoji: '🚗', label: 'Voiture', sub: '~60 km/h'  },
    avion:   { speed: 250, margin:10*60, minTime: 5*60, emoji: '✈️', label: 'Avion',   sub: '~250 km/h' },
};

// ── Temps réel ────────────────────────────────────────────────────────
const {
    lockedRiddles, realtimeAttempts, realtimePlayers,
    sessionEnded: realtimeSessionEnded, riddleNotification,
    subscribe: subscribeRealtime, unsubscribe: unsubscribeRealtime,
} = useGameRealtime(props.session.lien_token, page.props.auth.user.id);

const localSessionData = ref({
    attempts: props.session.attempts || [],
    players:  props.session.players  || [],
    statut:   props.session.statut,
});

// Init lockedRiddles depuis le serveur
props.gameSteps?.forEach((step) => {
    if (step.game_riddle?.statut === 'verrouille') {
        lockedRiddles.value[step.game_riddle.id] = {
            locked_by_player_id: step.game_riddle.locked_by_player_id,
            locked_by_name:      step.game_riddle.locked_by_name,
        };
    }
});

// ── Computed ──────────────────────────────────────────────────────────
const currentGameRiddleId = computed(() => props.gameSteps?.[currentPlaceIndex.value]?.game_riddle?.id ?? null);
const isCurrentRiddleLocked = computed(() => !!lockedRiddles.value[currentGameRiddleId.value]);
const currentRiddleLockedBy = computed(() => lockedRiddles.value[currentGameRiddleId.value]?.locked_by_name ?? null);
const currentPlace = computed(() => props.gameSteps?.[currentPlaceIndex.value]);
const currentRiddle = computed(() => currentPlace.value?.riddle);
const riddleImages = computed(() => currentRiddle.value?.images || []);

const parsedMcqOptions = computed(() => {
    if (!currentRiddle.value?.mcq_options) return [];
    try { return typeof currentRiddle.value.mcq_options === 'string' ? JSON.parse(currentRiddle.value.mcq_options) : currentRiddle.value.mcq_options; }
    catch { return []; }
});

const riddlePoints = computed(() => {
    const l = props.session.level;
    return l === 'facile' ? 100 : l === 'intermediaire' ? 200 : 300;
});

const hasMoreRiddlesForPlace = computed(() =>
    props.gameSteps?.some((step, idx) => idx > currentPlaceIndex.value && step.id === currentPlace.value?.id)
);
const hasNextPlace = computed(() => props.gameSteps?.length > currentPlaceIndex.value + 1);

const participantsTeamTarget = computed(() => {
    if (props.session.type !== 'participants') return 0;
    const inPlay = props.session.game_riddles?.length ?? 0;
    return Math.min(Number(props.session.riddles_count) || 0, inPlay || 999);
});

const participantsTeamAnswered = computed(() => {
    if (props.session.type !== 'participants') return 0;
    const ids = new Set();
    for (const att of localSessionData.value.attempts || []) {
        const gid = (att.game_riddle || att.gameRiddle)?.id;
        if (gid) ids.add(gid);
    }
    return ids.size;
});

const totalGamePlacesCount = computed(() => {
    if (props.session.type === 'participants' && participantsTeamTarget.value > 0)
        return participantsTeamTarget.value;
    return props.gameSteps?.length || 0;
});

const currentRiddleNumber = computed(() => {
    if (props.session.type === 'participants')
        return Math.min(participantsTeamAnswered.value + 1, participantsTeamTarget.value || 1);
    return Math.min(currentPlaceIndex.value + 1, props.gameSteps?.length || 0);
});

const sessionLeaderboard = computed(() =>
    localSessionData.value.players?.map(player => ({
        id: player.id,
        user_id: player.user_id,
        name: player.user?.name || 'Joueur',
        points: localSessionData.value.attempts
            ?.filter(a => a.user_id === player.user_id && a.status === 'gagne')
            ?.reduce((s, a) => s + (a.points_earned || 0), 0) || 0
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
    if (!m) return 'Calcul GPS...';
    if (m < 200) return '🚶 À pied (tout proche !)';
    if (m < 1000) return '🏃 Marche rapide';
    if (m < 3000) return '🚴 Vélo / trottinette';
    return '🚗 Véhicule motorisé';
});

const strokeDasharray = 402.12;
const strokeDashoffset = computed(() => totalTime.value === 0 ? 0 : strokeDasharray * (1 - timeLeft.value/totalTime.value));
const compassRotation = computed(() => totalTime.value === 0 ? 0 : (timeLeft.value/totalTime.value) * 360);

const currentPlayer = computed(() => props.session.players?.find(p => p.user_id === page.props.auth.user.id));

// ── Navigation lock ───────────────────────────────────────────────────
let navLocked = false;
const handlePopState = () => {
    if (!sessionEndHandled.value) {
        window.history.pushState(null, '', window.location.href);
        toast.add({ severity: 'warn', summary: 'Navigation bloquée 🚫', detail: 'Utilisez Pause ou Quitter pour gérer votre partie.', life: 3000 });
    }
};
const lockNavigation = () => {
    if (navLocked) return;
    navLocked = true;
    window.history.pushState(null, '', window.location.href);
    window.addEventListener('popstate', handlePopState);
};

// ── Session finish ────────────────────────────────────────────────────
const finishSessionRedirect = (message) => {
    if (sessionEndHandled.value) return;
    sessionEndHandled.value = true;
    clearInterval(timerInterval); timerInterval = null;
    isPaused.value = false;
    stopBackgroundMusic();
    lockNavigation();
    window.removeEventListener('popstate', handlePopState);
    clearGameState();
    toast.add({ severity: 'success', summary: '🏆 Session terminée !', detail: message || 'Bravo à toute l\'équipe !', life: 3000 });
    router.get(route('game.dashboard'));
};

// ── Sélection énigme participants ─────────────────────────────────────
const selectFirstUnattemptedRiddle = () => {
    if (props.session.type !== 'participants') return;
    for (let i = 0; i < props.gameSteps.length; i++) {
        const riddle = props.gameSteps[i].riddle;
        const attempted = localSessionData.value.attempts?.some(att =>
            (att.game_riddle || att.gameRiddle)?.riddle_id === riddle.id
        );
        if (!attempted) { currentPlaceIndex.value = i; return; }
    }
    currentPlaceIndex.value = props.gameSteps.length;
};

// ── Backend calls ─────────────────────────────────────────────────────
const recordAttemptOnBackend = (status, pointsEarned) => {
    isLoading.value = true;
    recordPromise = (async () => {
        try {
            const needsLock = (props.session.type === 'participants') ||
                (props.session.type === 'challengers' && props.session.challenger_mode === 'reponse_par_membre');

            if (needsLock) {
                lockedRiddles.value[currentGameRiddleId.value] = {
                    locked_by_user_id: page.props.auth.user.id,
                    locked_by_name: page.props.auth.user.name,
                    locked_at: new Date().toISOString(),
                };
                try {
                    const lockRes = await axios.post('/game/play/lock', {
                        session_id: props.session.id, riddle_id: currentRiddle.value.id,
                    });
                    if (!lockRes.data.locked) {
                        const msg = lockRes.data.message ?? 'Un joueur a verrouillé cette énigme !';
                        toast.add({ severity: 'warn', summary: 'Trop tard ! 🔒', detail: msg, life: 5000 });
                        decisionState.value = 'already_solved';
                        alreadySolvedMessage.value = msg;
                        return false;
                    }
                } catch (lockErr) {
                    if (lockErr.response?.status === 409) {
                        const msg = lockErr.response.data?.message ?? 'Un joueur a déjà verrouillé cette énigme.';
                        toast.add({ severity: 'warn', summary: 'Trop tard ! 🔒', detail: msg, life: 5000 });
                        decisionState.value = 'already_solved';
                        alreadySolvedMessage.value = msg;
                        return false;
                    }
                    throw lockErr;
                }
            }

            const response = await axios.post('/game/play/record', {
                session_id: props.session.id, riddle_id: currentRiddle.value.id,
                status, points: pointsEarned,
                mode_choisi: modeChoisi.value || 'gaming',
                temps_resolution: totalTime.value - timeLeft.value,
            });

            if (response.data?.session_finished) {
                if (response.data?.success) await router.reload({ only: ['session'] });
                else finishSessionRedirect(response.data?.message);
                return false;
            }
            if (response.data?.already_solved) {
                toast.add({ severity: 'warn', summary: 'Déjà clôturée ⚠️', detail: response.data.message, life: 5000 });
                decisionState.value = 'already_solved';
                alreadySolvedMessage.value = response.data.message;
                return false;
            }
            await router.reload({ only: ['session'] });
            return true;
        } catch (e) { console.error('Erreur backend:', e); return false; }
        finally { isLoading.value = false; recordPromise = null; }
    })();
    return recordPromise;
};

// ── Timer ─────────────────────────────────────────────────────────────
const startChrono = () => {
    if (timerInterval) clearInterval(timerInterval);
    timerInterval = setInterval(() => {
        if (!isPaused.value) {
            timeLeft.value = Math.max(0, Math.ceil((endTime.value - Date.now()) / 1000));
            saveGameState();
            if (timeLeft.value <= 0) {
                clearInterval(timerInterval); timerInterval = null;
                isPlaying.value = false;
                if (modeChoisi.value === 'gaming') handleLose();
            } else if (timeLeft.value <= 5 && modeChoisi.value === 'gaming') playCountdown();
        }
    }, 100);
};

const calculateChronoTimeForDiscovery = (transport) => {
    const mode = transport || transportMode.value || 'pied';
    const cfg = TRANSPORT_CONFIG[mode] || TRANSPORT_CONFIG.pied;
    const dist = distanceToPlace.value;
    if (!dist) return Math.max(cfg.minTime, cfg.margin + Math.round(1/cfg.speed*3600));
    return Math.max(cfg.minTime, Math.round((dist/cfg.speed)*3600) + cfg.margin);
};

const startRiddle = (mode) => {
    initAudioContext(); playGameStart();
    isLoading.value = false;
    modeChoisi.value = mode;
    isPlaying.value = true;
    decisionState.value = null;
    if (mode === 'gaming') {
        const l = props.session.level;
        totalTime.value = l === 'facile' ? 60 : l === 'intermediaire' ? 30 : 20;
    } else {
        totalTime.value = calculateChronoTimeForDiscovery();
    }
    timeLeft.value = totalTime.value;
    endTime.value = Date.now() + (totalTime.value * 1000);
    userAnswer.value = '';
    startChrono();
};

const triggerHint = () => {
    if (showFlashHint.value) return;
    playClick(); showFlashHint.value = true;
    setTimeout(() => { showFlashHint.value = false; }, 1500);
};

const togglePause = () => {
    playClick();
    isPaused.value = !isPaused.value;
    if (isPaused.value) { pauseBackgroundMusic(); }
    else { endTime.value = Date.now() + (timeLeft.value * 1000); resumeBackgroundMusic(); }
};

const handleWin = async () => {
    clearInterval(timerInterval);
    decisionState.value = 'win';
    playWin();
    userStatsStore.addPoints(riddlePoints.value);
    await recordAttemptOnBackend('gagne', riddlePoints.value);
};

const handleLose = async () => {
    clearInterval(timerInterval);
    decisionState.value = 'lose';
    playLose();
    await recordAttemptOnBackend('perdu', 0);
};

const submitDiscovery = async () => {
    if (isLoading.value) return;
    if (!distanceToPlace.value) {
        toast.add({ severity: 'warn', summary: 'GPS requis', detail: 'Signal GPS introuvable pour valider.', life: 4000 });
        return;
    }
    const margin = currentPlace.value?.marge_validation_gps || currentPlace.value?.rayon_marge || 50;
    if (distanceToPlace.value * 1000 <= margin) { await handleWin(); }
    else {
        toast.add({ severity: 'error', summary: 'Trop loin 📍',
            detail: `Vous êtes à ~${Math.round(distanceToPlace.value*1000)}m (marge : ${margin}m).`, life: 6000 });
    }
};

const submitQcm = (option) => {
    if (isLoading.value) return;
    playClick(); userAnswer.value = option; submitGaming();
};
const submitGaming = async () => {
    if (isLoading.value) return;
    playClick();
    if (userAnswer.value.trim().toLowerCase() === currentRiddle.value.reponse.trim().toLowerCase()) await handleWin();
    else await handleLose();
};

const goToNextPlace = () => {
    if (isNavigating.value) return;
    isNavigating.value = true;
    isLoading.value = false;
    playClick();
    if (recordPromise) recordPromise.catch(e => console.error(e));
    if (props.session.statut === 'termine') { finishSessionRedirect(); return; }

    const nextRiddleLogic = () => {
        const player = props.session.players?.find(p => p.user_id === page.props.auth.user.id);
        if (player && (player.global_mode === 'gaming' || player.global_mode === 'decouverte')) {
            if (player.global_mode === 'decouverte') { transportMode.value = null; showTransportSelection.value = true; }
            else startRiddle(player.global_mode);
        } else { isPlaying.value = false; decisionState.value = null; }
        isNavigating.value = false;
    };

    if (props.session.type === 'participants') {
        selectFirstUnattemptedRiddle();
        decisionState.value = null;
        if (currentPlaceIndex.value >= props.gameSteps.length) {
            lockNavigation();
            router.get(route('game.dashboard'));
        } else { nextTick(() => nextRiddleLogic()); }
    } else {
        if (hasNextPlace.value) {
            currentPlaceIndex.value++;
            decisionState.value = null;
            nextTick(() => nextRiddleLogic());
        } else {
            toast.add({ severity: 'success', summary: '🏆 Terminé !', detail: 'Aventure complète !', life: 2000 });
            router.get(route('game.dashboard'));
        }
    }
};

const forfeitSession = () => {
    playClick();
    confirm.require({
        message: 'Abandonner cette session ? Votre progression sera perdue.',
        header: 'Abandonner ⚠️',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Rester',
        acceptLabel: 'Abandonner',
        rejectClass: 'p-button-secondary p-button-outlined text-gray-300 border-gray-600 px-4 py-2 rounded-lg mr-2',
        acceptClass: 'p-button-danger bg-red-600 text-white px-4 py-2 rounded-lg',
        accept: () => {
            playClick();
            clearGameState();
            lockNavigation();
            router.get(route('game.dashboard'));
        }
    });
};

const formatTime = (s) => `${Math.floor(s/60).toString().padStart(2,'0')}:${(s%60).toString().padStart(2,'0')}`;
const handleVisibilityChange = () => {
    if (document.visibilityState === 'visible' && isPlaying.value && !isPaused.value && !decisionState.value) {
        timeLeft.value = Math.max(0, Math.ceil((endTime.value - Date.now()) / 1000));
        if (timeLeft.value <= 0) { clearInterval(timerInterval); handleLose(); }
    }
};

let watchId = null, unsubscribeBefore = null;

onMounted(() => {
    if (props.session.statut === 'termine') { finishSessionRedirect(); return; }
    if (savedState?.isPlaying && savedState?.endTime) {
        const remaining = Math.max(0, Math.floor((savedState.endTime - Date.now()) / 1000));
        if (remaining > 0) {
            timeLeft.value = remaining; totalTime.value = savedState.totalTime;
            endTime.value = savedState.endTime; isPlaying.value = true;
            isPaused.value = savedState.isPaused;
            if (!isPaused.value) startChrono();
        } else { timeLeft.value = 0; isPlaying.value = false; if (modeChoisi.value === 'gaming') handleLose(); }
    }
    playBackgroundMusic('game');
    document.addEventListener('visibilitychange', handleVisibilityChange);
    subscribeRealtime();
    unsubscribeBefore = router.on('before', () => {
        unsubscribeRealtime(); stopBackgroundMusic();
        if (timerInterval) { clearInterval(timerInterval); timerInterval = null; }
        if (watchId) { navigator.geolocation.clearWatch(watchId); watchId = null; }
    });
    if (props.session.type === 'participants') selectFirstUnattemptedRiddle();
    const player = props.session.players?.find(p => p.user_id === page.props.auth.user.id);
    if (player && (player.global_mode === 'gaming' || player.global_mode === 'decouverte')) {
        if (!(savedState?.isPlaying)) {
            if (player.global_mode === 'decouverte' && !transportMode.value) showTransportSelection.value = true;
            else startRiddle(player.global_mode);
        }
    }
    if (navigator.geolocation) {
        watchId = navigator.geolocation.watchPosition(
            (p) => { userCoords.value.lat = p.coords.latitude; userCoords.value.lng = p.coords.longitude; },
            (e) => { if (e.code !== 1) console.warn('GPS:', e); },
            { enableHighAccuracy: true }
        );
    }
});

onUnmounted(() => {
    if (props.session.statut !== 'termine' && !sessionEndHandled.value) saveGameState();
    else clearGameState();
    window.removeEventListener('popstate', handlePopState);
    if (unsubscribeBefore) unsubscribeBefore();
    unsubscribeRealtime();
    document.removeEventListener('visibilitychange', handleVisibilityChange);
    stopBackgroundMusic();
    if (watchId) navigator.geolocation.clearWatch(watchId);
    clearInterval(timerInterval);
});

// ── Watchers ──────────────────────────────────────────────────────────
watch([currentPlaceIndex, modeChoisi, isPlaying, timeLeft, isPaused, decisionState], saveGameState, { deep: true });
watch(realtimeAttempts, (v) => { if (v.length) localSessionData.value.attempts = v; }, { deep: true });
watch(realtimePlayers, (v) => { if (v.length) localSessionData.value.players = v; }, { deep: true });
watch(realtimeSessionEnded, (ended) => { if (ended && !sessionEndHandled.value) finishSessionRedirect(); });
watch(riddleNotification, (notif) => {
    if (!notif || notif.game_riddle_id !== currentGameRiddleId.value || decisionState.value) return;
    playAlreadySolved(); decisionState.value = 'already_solved';
    clearInterval(timerInterval); alreadySolvedMessage.value = notif.message;
    toast.add({ severity: 'warn', summary: 'Énigme verrouillée 🔒', detail: notif.message, life: 5000 });
});
watch(() => localSessionData.value.attempts, (newAttempts) => {
    if (!newAttempts || !currentRiddle.value) return;
    if (props.session.type === 'participants' || (props.session.type === 'challengers' && props.session.challenger_mode === 'reponse_par_membre')) {
        const att = newAttempts.find(a => (a.game_riddle || a.gameRiddle)?.riddle_id === currentRiddle.value.id);
        if (att && !decisionState.value && att.user_id !== page.props.auth.user.id) {
            decisionState.value = 'already_solved';
            clearInterval(timerInterval);
            alreadySolvedMessage.value = att.status === 'gagne'
                ? `${att.user?.name || 'Un joueur'} a résolu cette énigme ! 🟢`
                : `${att.user?.name || 'Un joueur'} a échoué. Énigme verrouillée. 🔴`;
        }
    }
}, { deep: true });
watch(() => props.session, (s) => {
    if (s) { localSessionData.value.attempts = s.attempts || []; localSessionData.value.players = s.players || []; localSessionData.value.statut = s.statut; }
}, { deep: true });
watch(() => props.session.statut, (s, p) => { if (s === 'termine' && p !== 'termine') finishSessionRedirect(); });
</script>

<template>
    <AuthenticatedLayout title="En Jeu">
        <Toast position="top-right" />

        <!-- GAME ZONE — plein écran, pas de padding superflu -->
        <div class="flex flex-col gap-3 -mx-3 sm:-mx-6 lg:-mx-10 -mt-4 sm:-mt-6 lg:-mt-8 px-3 sm:px-6 pt-2 pb-2">

            <!-- ── SCOREBOARD (Coop/Versus uniquement) ──────────── -->
            <div v-if="session.type !== 'solo'" class="panel-glass px-3 py-2.5 border border-[#26272F] animate-fade-in-up">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-1.5">
                        <Trophy :size="13" class="text-[#f3a900]" />
                        <span class="text-[8px] font-black uppercase tracking-widest text-gray-400">Classement</span>
                    </div>
                    <span class="text-[7px] bg-[#2fc276]/10 border border-[#2fc276]/20 text-[#2fc276] px-2 py-0.5 rounded-full font-black uppercase animate-pulse">Live</span>
                </div>
                <div class="flex gap-2 overflow-x-auto pb-0.5">
                    <div v-for="(p, idx) in sessionLeaderboard" :key="p.id"
                        class="flex items-center gap-2 bg-[#1C1D24] px-2.5 py-1.5 rounded-lg border border-[#26272F] shrink-0"
                        :class="p.user_id === page.props.auth.user.id ? 'border-[#2fc276]/40' : ''">
                        <span class="text-[9px] font-black text-gray-500">{{ idx+1 }}</span>
                        <span class="text-[10px] font-black" :class="p.user_id === page.props.auth.user.id ? 'text-[#2fc276]' : 'text-white'">{{ p.name.split(' ')[0] }}</span>
                        <span class="text-[8px] font-black text-gray-500">{{ p.points }} XP</span>
                    </div>
                </div>
                <p v-if="session.type === 'participants' && participantsTeamTarget > 0"
                    class="mt-1.5 text-center text-[8px] font-black uppercase tracking-widest text-[#4769b0]">
                    Équipe : {{ participantsTeamAnswered }} / {{ participantsTeamTarget }} énigmes
                </p>
            </div>

            <!-- ── MODE SELECTION (Mixte) ──────────────────────── -->
            <div v-if="!isPlaying && !showTransportSelection" class="panel-glass p-4 sm:p-6 border border-[#26272F] animate-fade-in-up">
                <h2 class="text-lg font-black uppercase italic tracking-tighter text-white text-center mb-4">
                    Choisissez votre <span class="text-[#2fc276]">mode</span>
                </h2>
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-[#1C1D24] p-4 rounded-2xl border-2 border-[#f3a900]/30 flex flex-col items-center gap-3">
                        <span class="text-3xl">🗺️</span>
                        <div class="text-center">
                            <p class="text-sm font-black text-[#f3a900] uppercase">Découverte</p>
                            <p class="text-[9px] text-gray-400 mt-0.5">Validation GPS sur place.</p>
                        </div>
                        <button @click="() => { playClick(); transportMode = null; showTransportSelection = true; }"
                            class="btn-3d btn-3d-yellow w-full py-2.5 text-[9px] shadow-[0_4px_0_#9e6f00]">
                            Choisir transport
                        </button>
                    </div>
                    <div class="bg-[#1C1D24] p-4 rounded-2xl border-2 border-[#2c72f6]/30 flex flex-col items-center gap-3">
                        <span class="text-3xl">🎮</span>
                        <div class="text-center">
                            <p class="text-sm font-black text-[#2c72f6] uppercase">Gaming</p>
                            <p class="text-[9px] text-gray-400 mt-0.5">Répondez depuis chez vous.</p>
                        </div>
                        <button @click="startRiddle('gaming')" :disabled="isLoading || isCurrentRiddleLocked"
                            class="btn-3d btn-3d-blue w-full py-2.5 text-[9px] shadow-[0_4px_0_#1344a1]">
                            C'est parti !
                        </button>
                    </div>
                </div>
            </div>

            <!-- ── TRANSPORT SELECTION ──────────────────────────── -->
            <div v-if="showTransportSelection" class="panel-glass p-4 border border-[#26272F] animate-fade-in-up">
                <h2 class="text-base font-black uppercase italic tracking-tighter text-white text-center mb-1">
                    Comment vous <span class="text-[#f3a900]">déplacez-vous</span> ?
                </h2>
                <p class="text-[9px] text-gray-400 text-center mb-4">Le chrono sera adapté à la distance et votre transport.</p>
                <div class="grid grid-cols-2 gap-2.5">
                    <div v-for="(cfg, key) in TRANSPORT_CONFIG" :key="key"
                        @click="() => { playClick(); transportMode = key; showTransportSelection = false; startRiddle('decouverte'); }"
                        class="cursor-pointer bg-[#1C1D24] p-3.5 rounded-2xl border-2 border-[#f3a900]/20 hover:border-[#f3a900] active:scale-95 transition-all flex flex-col items-center gap-1.5">
                        <span class="text-3xl">{{ cfg.emoji }}</span>
                        <p class="text-[10px] font-black text-white uppercase">{{ cfg.label }}</p>
                        <p class="text-[8px] text-gray-500 font-bold">{{ cfg.sub }}</p>
                    </div>
                </div>
                <button @click="showTransportSelection = false" class="mt-3 w-full text-[9px] text-gray-500 hover:text-white transition-colors font-bold uppercase tracking-widest py-2">
                    ← Retour
                </button>
            </div>

            <!-- ── ACTIVE RIDDLE ZONE ──────────────────────────── -->
            <div v-if="isPlaying && currentRiddle" class="panel-glass border border-[#26272F] relative overflow-hidden animate-fade-in-up">

                <!-- OVERLAY : Pause -->
                <div v-if="isPaused" class="absolute inset-0 bg-[#0D0E12]/97 backdrop-blur-md z-20 flex flex-col items-center justify-center rounded-[24px]">
                    <Pause :size="56" class="text-[#f3a900] mb-4 animate-pulse" />
                    <h2 class="text-2xl font-black uppercase italic tracking-tighter text-[#f3a900] text-glow-yellow mb-6">En Pause</h2>
                    <button @click="togglePause" class="btn-3d btn-3d-yellow px-8 py-3 text-xs text-[#0A0B0E] font-black shadow-[0_5px_0_#9e6f00]">REPRENDRE</button>
                </div>

                <!-- OVERLAY : États de décision -->
                <div v-if="decisionState && !isPaused" class="absolute inset-0 bg-[#0D0E12]/97 backdrop-blur-md z-30 flex flex-col items-center justify-center p-4 rounded-[24px] text-center">
                    
                    <!-- WIN -->
                    <template v-if="decisionState === 'win'">
                        <div class="relative mb-3">
                            <div class="absolute inset-0 bg-[#2fc276]/20 blur-xl rounded-full"></div>
                            <Trophy :size="52" class="text-[#2fc276] relative animate-bounce" />
                        </div>
                        <h2 class="text-2xl font-black text-[#2fc276] text-glow-green uppercase italic mb-1">Résolu !</h2>
                        <p class="text-xs text-gray-400 mb-3">+<span class="text-[#f3a900] font-black">{{ riddlePoints }} XP</span> gagnés !</p>
                        <!-- Info lieu -->
                        <div class="w-full max-w-sm bg-white/5 border border-white/10 rounded-2xl p-3 mb-4 text-left">
                            <p class="text-[8px] font-black uppercase text-gray-500 mb-1">📍 {{ currentPlace?.nom }}</p>
                            <p class="text-[10px] text-gray-300 leading-relaxed italic line-clamp-2">
                                "{{ currentPlace?.verified_description || 'Un lieu emblématique à découvrir.' }}"
                            </p>
                            <a :href="`https://www.google.com/maps/dir/?api=1&destination=${currentPlace?.latitude},${currentPlace?.longitude}`"
                               target="_blank" @click="playClick"
                               class="mt-2 flex items-center justify-center gap-1.5 w-full py-1.5 bg-white/5 hover:bg-white/10 border border-white/10 rounded-xl text-[8px] font-black uppercase text-white transition-all">
                                <Compass :size="11" /> Itinéraire GPS
                            </a>
                        </div>
                        <button @click="goToNextPlace" :disabled="isNavigating"
                            class="btn-3d btn-3d-green w-full max-w-sm py-3.5 text-sm shadow-[0_5px_0_#1e7d4b] disabled:opacity-50 flex items-center justify-center gap-2">
                            <RotateCcw v-if="isNavigating" :size="16" class="animate-spin" />
                            <CheckCircle2 v-else :size="16" />
                            {{ hasNextPlace ? 'Lieu suivant →' : 'Terminer !' }}
                        </button>
                    </template>

                    <!-- LOSE -->
                    <template v-if="decisionState === 'lose'">
                        <div class="relative mb-3">
                            <div class="absolute inset-0 bg-red-500/20 blur-xl rounded-full"></div>
                            <Skull :size="52" class="text-red-500 relative animate-pulse" />
                        </div>
                        <h2 class="text-2xl font-black text-red-400 text-glow-red uppercase italic mb-1">Échec</h2>
                        <p class="text-xs text-gray-400 mb-3">Temps écoulé ou mauvaise réponse.</p>
                        <div class="w-full max-w-sm bg-white/5 border border-white/10 rounded-2xl p-3 mb-4 text-left">
                            <p class="text-[8px] font-black uppercase text-gray-500 mb-1">💡 Le saviez-vous ?</p>
                            <p class="text-[10px] text-gray-300 leading-relaxed italic line-clamp-2">
                                "{{ currentPlace?.verified_description || 'Un lieu emblématique à découvrir.' }}"
                            </p>
                        </div>
                        <div class="flex flex-col gap-2 w-full max-w-sm">
                            <button v-if="hasMoreRiddlesForPlace"
                                @click="async () => { if(isNavigating) return; isNavigating.value=true; const ni=props.gameSteps.findIndex((s,i)=>i>currentPlaceIndex.value&&s.id===currentPlace?.id); if(ni!==-1){currentPlaceIndex.value=ni;decisionState.value=null;const p=props.session.players?.find(p=>p.user_id===page.props.auth.user.id);if(p?.global_mode==='gaming')startRiddle('gaming');else if(p?.global_mode==='decouverte'){transportMode=null;showTransportSelection.value=true;}else isPlaying.value=false;}isNavigating.value=false; }"
                                :disabled="isNavigating"
                                class="btn-3d btn-3d-blue w-full py-3 text-xs shadow-[0_4px_0_#1344a1] flex items-center justify-center gap-1.5">
                                <RotateCcw :size="14" /> Autre énigme ici
                            </button>
                            <button @click="goToNextPlace" :disabled="isNavigating"
                                class="btn-3d btn-3d-yellow w-full py-3 text-xs shadow-[0_4px_0_#9e6f00] text-black flex items-center justify-center gap-1.5">
                                <template v-if="!isNavigating">
                                    {{ hasNextPlace ? 'Lieu suivant' : 'Terminer !' }}
                                    <ChevronRight :size="14" />
                                </template>
                                <template v-else><RotateCcw :size="14" class="animate-spin" /> Chargement...</template>
                            </button>
                            <button @click="forfeitSession" :disabled="isLoading"
                                class="btn-3d btn-3d-red w-full py-2 text-[9px] shadow-[0_3px_0_#9e2318] opacity-60 hover:opacity-100 flex items-center justify-center gap-1.5">
                                <LogOut :size="12" /> Abandonner
                            </button>
                        </div>
                    </template>

                    <!-- ALREADY SOLVED -->
                    <template v-if="decisionState === 'already_solved'">
                        <AlertTriangle :size="52" class="text-[#f3a900] mb-3" />
                        <h2 class="text-xl font-black text-[#f3a900] text-glow-yellow uppercase italic mb-1">Déjà clôturée</h2>
                        <p class="text-xs text-gray-400 mb-5 max-w-xs">{{ alreadySolvedMessage || 'Un coéquipier a déjà répondu à cette énigme.' }}</p>
                        <div class="flex flex-col gap-2 w-full max-w-sm">
                            <button @click="goToNextPlace" :disabled="isNavigating"
                                class="btn-3d btn-3d-blue w-full py-3 text-sm shadow-[0_5px_0_#1344a1] flex items-center justify-center gap-2">
                                <template v-if="!isNavigating">
                                    {{ hasNextPlace ? 'Suivant' : 'Terminer !' }} <ChevronRight :size="14" />
                                </template>
                                <template v-else><RotateCcw :size="14" class="animate-spin" /> Chargement...</template>
                            </button>
                            <button @click="forfeitSession" class="btn-3d btn-3d-red w-full py-2 text-[9px] shadow-[0_3px_0_#9e2318] flex items-center justify-center gap-1.5">
                                <LogOut :size="12" /> Quitter
                            </button>
                        </div>
                    </template>
                </div>

                <!-- CONTENT : Énigme active -->
                <div class="p-3 sm:p-5">

                    <!-- HUD ligne -->
                    <div class="flex items-center justify-between gap-2 mb-3">
                        <!-- Progression -->
                        <div class="flex items-center gap-2 bg-[#1C1D24] border border-[#26272F] px-3 py-1.5 rounded-xl">
                            <MapPin :size="11" class="text-[#2fc276] shrink-0" />
                            <div class="leading-none">
                                <p class="text-[7px] font-black uppercase text-gray-500 tracking-widest">
                                    <template v-if="session.type === 'participants'">Équipe</template>
                                    <template v-else>Énigme</template>
                                </p>
                                <p class="text-[11px] font-black text-white">
                                    <template v-if="session.type === 'participants'">{{ participantsTeamAnswered }}/{{ participantsTeamTarget }}</template>
                                    <template v-else>{{ currentRiddleNumber }}/{{ totalGamePlacesCount }}</template>
                                </p>
                            </div>
                        </div>

                        <!-- Lieu -->
                        <span class="flex-1 text-center text-[9px] font-black bg-[#2fc276]/10 border border-[#2fc276]/20 text-[#2fc276] px-2.5 py-1.5 rounded-xl truncate text-glow-green">
                            {{ currentPlace?.nom }}
                        </span>

                        <!-- Chrono découverte -->
                        <div v-if="modeChoisi === 'decouverte'"
                            class="flex items-center gap-1.5 bg-[#1C1D24] border border-[#26272F] px-2.5 py-1.5 rounded-xl">
                            <span class="w-2 h-2 rounded-full animate-ping shrink-0" :class="timeLeft < 30 ? 'bg-red-500' : 'bg-[#2fc276]'"></span>
                            <span class="text-[11px] font-mono font-black tabular-nums" :class="timeLeft < 30 ? 'text-red-400' : 'text-white'">{{ formatTime(timeLeft) }}</span>
                        </div>
                    </div>

                    <!-- Controls pause/hint/quitter -->
                    <div class="flex gap-2 mb-3 justify-end">
                        <button @click="triggerHint" :disabled="showFlashHint"
                            class="flex items-center gap-1 px-3 py-1.5 rounded-lg border border-amber-500/30 bg-amber-500/10 text-[9px] font-black uppercase tracking-widest text-amber-400 hover:bg-amber-500/20 transition-all disabled:opacity-40">
                            <Zap :size="12" /> Indice
                        </button>
                        <button @click="togglePause"
                            class="flex items-center gap-1 px-3 py-1.5 rounded-lg border border-[#26272F] bg-[#1C1D24] text-[9px] font-black uppercase tracking-widest text-[#f3a900] hover:border-[#f3a900]/40 transition-all">
                            <Pause v-if="!isPaused" :size="12" /><Play v-else :size="12" />
                            {{ isPaused ? 'Reprendre' : 'Pause' }}
                        </button>
                        <button @click="forfeitSession" :disabled="isLoading"
                            class="flex items-center gap-1 px-3 py-1.5 rounded-lg border border-red-500/20 bg-[#1C1D24] text-[9px] font-black uppercase tracking-widest text-red-400 hover:bg-red-500/10 transition-all disabled:opacity-40">
                            <LogOut :size="12" />
                        </button>
                    </div>

                    <!-- Chrono gaming (boussole ring) -->
                    <div v-if="modeChoisi === 'gaming'" class="flex justify-center mb-4">
                        <div class="relative w-24 h-24 flex items-center justify-center bg-[#0D0E12] rounded-full border-4 border-[#26272F]">
                            <svg class="absolute inset-0 w-full h-full -rotate-90">
                                <circle cx="48" cy="48" r="42" stroke="#1C1D24" stroke-width="6" fill="transparent"/>
                                <circle cx="48" cy="48" r="42" stroke="url(#neonGrad)" stroke-width="6" fill="transparent"
                                    :stroke-dasharray="264" :stroke-dashoffset="264*(1-timeLeft/totalTime)"
                                    stroke-linecap="round" class="transition-all duration-700 ease-linear"/>
                                <defs>
                                    <linearGradient id="neonGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                        <stop offset="0%" stop-color="#2fc276"/>
                                        <stop offset="100%" stop-color="#2c72f6"/>
                                    </linearGradient>
                                </defs>
                            </svg>
                            <div class="text-center z-10">
                                <div class="text-xl font-black font-mono text-white tabular-nums">{{ timeLeft }}<span class="text-[10px] text-gray-500">s</span></div>
                            </div>
                        </div>
                    </div>

                    <!-- Texte de l'énigme -->
                    <div class="text-center mb-4 px-1">
                        <p class="text-base sm:text-lg text-white font-black italic leading-relaxed text-glow-green">
                            "{{ currentRiddle.description }}"
                        </p>

                        <!-- Indice flash -->
                        <transition name="fade">
                            <div v-if="showFlashHint"
                                class="inline-flex flex-col items-center mt-3 p-3 bg-amber-500/10 border border-amber-500/30 rounded-2xl animate-bounce">
                                <span class="text-[7px] font-black uppercase tracking-widest text-amber-500/70 mb-0.5">Indice</span>
                                <p class="text-sm font-black text-white italic">
                                    {{ currentRiddle.hints?.[0]?.content || currentRiddle.reponse }}
                                </p>
                            </div>
                        </transition>

                        <!-- Images -->
                        <div v-if="riddleImages.length > 0" class="flex gap-2 mt-3 overflow-x-auto pb-1 justify-center">
                            <img v-for="(img, idx) in riddleImages" :key="idx"
                                :src="img.image_path.startsWith('http') ? img.image_path : `/storage/${img.image_path}`"
                                class="h-28 sm:h-36 w-auto rounded-xl border-2 border-white/10 object-cover shrink-0"
                                alt="Visuel de l'énigme" />
                        </div>
                    </div>

                    <!-- Lock indicator -->
                    <div v-if="isCurrentRiddleLocked && !decisionState"
                        class="flex items-center gap-2 bg-yellow-500/10 border border-yellow-500/30 rounded-xl px-3 py-2 mb-3 text-yellow-400 text-xs font-semibold">
                        <span class="text-base">🔒</span>
                        <span>{{ currentRiddleLockedBy }} répond en ce moment...</span>
                    </div>

                    <!-- ACTION : Découverte -->
                    <div v-if="modeChoisi === 'decouverte'" class="space-y-3">
                        <div class="bg-[#1C1D24] border border-[#26272F] p-3 rounded-xl text-center">
                            <p class="text-[8px] font-black uppercase text-gray-500 tracking-wider mb-1">Transport conseillé</p>
                            <span class="text-xs font-black text-white">{{ recommendedTransport }}</span>
                        </div>
                        <button @click="submitDiscovery" :disabled="isLoading"
                            class="btn-3d btn-3d-green w-full py-4 text-sm font-black shadow-[0_6px_0_#1e7d4b] tracking-widest disabled:opacity-50">
                            📍 JE SUIS SUR PLACE
                        </button>
                    </div>

                    <!-- ACTION : Gaming -->
                    <div v-if="modeChoisi === 'gaming'" class="space-y-3">
                        <!-- Texte libre (difficile ou pas de MCQ) -->
                        <div v-if="session.level === 'difficile' || parsedMcqOptions.length === 0" class="space-y-2">
                            <input v-model="userAnswer" type="text" placeholder="Votre réponse..."
                                @keydown="playKey" @keydown.enter="submitGaming"
                                class="w-full bg-[#0D0E12] border-2 border-[#26272F] focus:border-[#2fc276] focus:outline-none rounded-xl p-3 text-base text-center text-white font-black uppercase tracking-widest transition-colors">
                            <button @click="submitGaming" :disabled="!userAnswer || isLoading"
                                class="btn-3d btn-3d-green w-full py-3.5 text-sm shadow-[0_5px_0_#1e7d4b] flex items-center justify-center gap-2 disabled:opacity-50">
                                SOUMETTRE <Rocket :size="15" />
                            </button>
                        </div>

                        <!-- MCQ -->
                        <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            <button v-for="option in parsedMcqOptions" :key="option"
                                @click="submitQcm(option)"
                                :disabled="isLoading"
                                class="btn-3d btn-3d-blue py-4 text-xs sm:text-sm shadow-[0_4px_0_#1344a1] text-center disabled:opacity-50 mcq-option">
                                {{ option }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Toast mobile overrides via CSS global -->
    </AuthenticatedLayout>
</template>

<style scoped>
.animate-fade-in-up {
    animation: fadeInUp 0.3s ease-out forwards;
}
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(10px); }
    to   { opacity: 1; transform: translateY(0); }
}
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to       { opacity: 0; }
</style>
