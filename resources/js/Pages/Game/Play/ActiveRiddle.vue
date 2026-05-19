<script setup>
import { ref, onMounted, computed, onUnmounted, watch } from 'vue';
import { 
    Trophy, 
    CheckCircle2, 
    Pause, 
    Play, 
    LogOut, 
    Map as MapIcon, 
    MapPin, 
    Gamepad2, 
    Skull, 
    RotateCcw, 
    AlertTriangle, 
    Compass, 
    Rocket,
    Clock,
    Target,
    Zap,
    History,
    X,
    ChevronRight
} from 'lucide-vue-next';
import { Head, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import Toast from 'primevue/toast';
import axios from 'axios';
import { userStatsStore } from '@/store.js';
import { useAudio } from '@/composables/useAudio.js';
import AudioWidget from '@/Components/AudioWidget.vue';

const {
    playClick, playKey, playTick, playCountdown,
    playWin, playLose, playAlreadySolved, playGameStart,
    playBackgroundMusic, stopBackgroundMusic, pauseBackgroundMusic, resumeBackgroundMusic,
    initAudioContext,
} = useAudio();

const props = defineProps({
    session: Object,
    placesWithRiddles: Array
});

const page = usePage();
const toast = useToast();
const confirm = useConfirm();

// État local du jeu
const currentPlaceIndex = ref(0);
const currentRiddleInPlaceIndex = ref(0);
const modeChoisi = ref(null); // Pour le mode 'mixte'
const isPlaying = ref(false);
const timeLeft = ref(0);
const totalTime = ref(0);
const isPaused = ref(false);
const userAnswer = ref('');
const userCoords = ref({ lat: null, lng: null });

// État des indices
const riddleHints = ref([]);
const showHintsModal = ref(false);
const isFetchingHints = ref(false);

const fetchHints = async () => {
    if (riddleHints.value.length > 0) {
        showHintsModal.value = true;
        return;
    }

    try {
        isFetchingHints.value = true;
        const response = await axios.get(route('game.hints', { riddleId: currentRiddle.value.id }));
        if (response.data.success) {
            riddleHints.value = response.data.hints;
            showHintsModal.value = true;
        }
    } catch (e) {
        console.error("Erreur lors de la récupération des indices:", e);
        toast.add({ severity: 'error', summary: 'Erreur', detail: 'Impossible de charger les indices.', life: 3000 });
    } finally {
        isFetchingHints.value = false;
    }
};

// Réinitialiser les indices quand on change d'énigme
watch([currentPlaceIndex, currentRiddleInPlaceIndex], () => {
    riddleHints.value = [];
    showHintsModal.value = false;
});

// État de décision intermédiaire ('win', 'lose', 'already_solved' ou null)
const decisionState = ref(null);
const alreadySolvedMessage = ref('');

let timerInterval = null;
const sessionEndHandled = ref(false);

// État local replicateur pour les mises à jour temps réel sans reload
const localSessionData = ref({
    attempts: props.session.attempts || [],
    players: props.session.players || [],
    statut: props.session.statut,
});

// Classement de la session en direct
const sessionLeaderboard = computed(() => {
    return localSessionData.value.players?.map(player => {
        const sessionPoints = localSessionData.value.attempts
            ?.filter(att => att.user_id === player.user_id && att.status === 'gagne')
            ?.reduce((sum, att) => sum + (att.points_earned || 0), 0) || 0;
            
        return {
            id: player.id,
            user_id: player.user_id,
            name: player.user?.name || 'Joueur',
            points: sessionPoints
        };
    }).sort((a, b) => b.points - a.points);
});

// Mode participants : objectif d'énigmes résolues par l'équipe (distinct game_riddle gagnées)
const participantsTeamTarget = computed(() => {
    if (props.session.type !== 'participants') return 0;
    const riddlesCount = Math.max(0, Number(props.session.riddles_count) || 0);
    const inPlay = props.session.game_riddles?.length ?? 0;
    if (inPlay <= 0) return riddlesCount;
    return Math.min(riddlesCount, inPlay);
});

const participantsTeamAnswered = computed(() => {
    if (props.session.type !== 'participants') return 0;
    const ids = new Set();
    for (const att of localSessionData.value.attempts || []) {
        const gr = att.game_riddle || att.gameRiddle;
        const gid = gr?.id;
        if (gid) ids.add(gid);
    }
    return ids.size;
});

const finishSessionRedirect = (message) => {
    if (sessionEndHandled.value) return;
    sessionEndHandled.value = true;
    if (timerInterval) {
        clearInterval(timerInterval);
        timerInterval = null;
    }
    isPaused.value = false;
    stopBackgroundMusic();
    toast.add({
        severity: 'success',
        summary: 'Session terminée ! 🏆',
        detail: message || 'L\'équipe a atteint l\'objectif d\'énigmes. Bravo !',
        life: 5000,
    });
    setTimeout(() => {
        router.get(route('game.dashboard'));
    }, 1800);
};

// Trouver la première énigme non tentée dans le mode participants
const selectFirstUnattemptedRiddle = () => {
    if (props.session.type !== 'participants') return;
    
    for (let pIdx = 0; pIdx < props.placesWithRiddles.length; pIdx++) {
        const place = props.placesWithRiddles[pIdx];
        for (let rIdx = 0; rIdx < (place.riddles?.length || 0); rIdx++) {
            const riddle = place.riddles[rIdx];
            const attempted = localSessionData.value.attempts?.some(att => {
                const gameRiddle = att.game_riddle || att.gameRiddle;
                return gameRiddle?.riddle_id === riddle.id;
            });
            if (!attempted) {
                currentPlaceIndex.value = pIdx;
                currentRiddleInPlaceIndex.value = rIdx;
                return;
            }
        }
    }
    
    // Si toutes les énigmes ont été tentées, on affiche l'écran de fin
    currentPlaceIndex.value = props.placesWithRiddles.length;
};

const currentPlace = computed(() => {
    return props.placesWithRiddles?.[currentPlaceIndex.value];
});

const currentRiddle = computed(() => {
    return currentPlace.value?.riddles?.[currentRiddleInPlaceIndex.value];
});

const parsedMcqOptions = computed(() => {
    if (!currentRiddle.value?.mcq_options) return [];
    try {
        return typeof currentRiddle.value.mcq_options === 'string'
            ? JSON.parse(currentRiddle.value.mcq_options)
            : currentRiddle.value.mcq_options;
    } catch (e) {
        return [];
    }
});

// Points à gagner selon le niveau de difficulté
const riddlePoints = computed(() => {
    const level = props.session.level;
    if (level === 'facile') return 100;
    if (level === 'intermediaire') return 200;
    return 300;
});

// Vérification de la disponibilité d'autres énigmes pour ce lieu
const hasMoreRiddlesForPlace = computed(() => {
    return currentPlace.value?.riddles?.length > currentRiddleInPlaceIndex.value + 1;
});

// Vérification de la présence d'un lieu suivant
const hasNextPlace = computed(() => {
    return props.placesWithRiddles?.length > currentPlaceIndex.value + 1;
});

// Total des énigmes affichées (lieux) — en participants, l'objectif affiché suit la config session
const totalGamePlacesCount = computed(() => {
    if (props.session.type === 'participants' && participantsTeamTarget.value > 0) {
        return participantsTeamTarget.value;
    }
    return props.placesWithRiddles?.length || 0;
});

const currentRiddleNumber = computed(() => {
    if (props.session.type !== 'participants') {
        return Math.min(currentPlaceIndex.value + 1, props.placesWithRiddles?.length || 0);
    }
    return Math.min(participantsTeamAnswered.value + 1, participantsTeamTarget.value || 1);
});

// Propriétés de la boussole
const strokeDasharray = 402.12;
const strokeDashoffset = computed(() => {
    if (totalTime.value === 0) return 0;
    const ratio = timeLeft.value / totalTime.value;
    return strokeDasharray * (1 - ratio);
});

const compassRotation = computed(() => {
    if (totalTime.value === 0) return 0;
    const ratio = timeLeft.value / totalTime.value;
    return ratio * 360;
});

// Enregistrement des résultats en DB via route Laravel
const recordAttemptOnBackend = async (status, pointsEarned) => {
    try {
        const response = await axios.post('/game/play/record', {
            session_id: props.session.id,
            riddle_id: currentRiddle.value.id,
            status: status,
            points: pointsEarned,
            mode_choisi: modeChoisi.value || 'gaming',
            temps_resolution: totalTime.value - timeLeft.value
        });

        if (response.data?.session_finished) {
            if (response.data?.success) {
                await router.reload({ only: ['session'] });
            } else {
                finishSessionRedirect(response.data?.message);
            }
            return false;
        }
        
        if (response.data && response.data.already_solved) {
            toast.add({ 
                severity: 'warn', 
                summary: 'Énigme déjà clôturée ⚠️', 
                detail: response.data.message, 
                life: 6000 
            });
            decisionState.value = 'already_solved'; 
            alreadySolvedMessage.value = response.data.message;
            return false;
        }
        
        // Force Inertia to update the local session state immediately!
        await router.reload({ only: ['session'] });
        return true;
    } catch (e) {
        console.error("Erreur d'enregistrement backend:", e);
        return false;
    }
};

// Géolocalisation en temps réel pour le mode découverte
let watchId = null;
let unsubscribeBefore = null;

onMounted(() => {
    if (props.session.statut === 'termine') {
        finishSessionRedirect();
        return;
    }

    // Démarrer la musique de fond dès le montage (sans interaction requise via autoplay)
    playBackgroundMusic('game');

    // Écouter le canal du jeu via Laravel Echo (seulement si disponible)
    if (window.Echo) {
        window.Echo.channel(`game.${props.session.lien_token}`)
            .listen('.App\\Events\\GameUpdated', (e) => {
                // Mise à jour directe des données sans reload complet
                if (e.session) {
                    // Mettre à jour les tentatives et les joueurs en doux
                    if (e.session.attempts) {
                        localSessionData.value.attempts = e.session.attempts;
                    }
                    if (e.session.players) {
                        localSessionData.value.players = e.session.players;
                    }
                    if (e.session.statut) {
                        localSessionData.value.statut = e.session.statut;
                        
                        // Vérifier si la session est terminée
                        if (e.session.statut === 'termine' && props.session.statut !== 'termine') {
                            finishSessionRedirect();
                        }
                    }
                }
            });
    }

    // Nettoyer tous les canaux, observateurs et timers dès qu'une navigation démarre
    unsubscribeBefore = router.on('before', () => {
        if (window.Echo) {
            window.Echo.leave(`game.${props.session.lien_token}`);
        }
        stopBackgroundMusic();
        if (timerInterval) {
            clearInterval(timerInterval);
            timerInterval = null;
        }
        if (watchId) {
            navigator.geolocation.clearWatch(watchId);
            watchId = null;
        }
    });

    // Dans le mode participants, se positionner sur la première énigme non tentée
    if (props.session.type === 'participants') {
        selectFirstUnattemptedRiddle();
    }

    // Tenter de récupérer le mode global du joueur
    const player = props.session.players?.find(p => p.user_id === page.props.auth.user.id);
    if (player && (player.global_mode === 'gaming' || player.global_mode === 'decouverte')) {
        startRiddle(player.global_mode);
    }

    if (navigator.geolocation) {
        watchId = navigator.geolocation.watchPosition(
            (position) => {
                userCoords.value.lat = position.coords.latitude;
                userCoords.value.lng = position.coords.longitude;
            },
            (error) => console.warn("Erreur de suivi GPS:", error),
            { enableHighAccuracy: true }
        );
    }
});

onUnmounted(() => {
    if (unsubscribeBefore) {
        unsubscribeBefore();
    }
    if (window.Echo) {
        window.Echo.leave(`game.${props.session.lien_token}`);
    }
    stopBackgroundMusic();
    if (watchId) navigator.geolocation.clearWatch(watchId);
    clearInterval(timerInterval);
});

// Détecter si l'énigme active actuelle (currentRiddle) a été clôturée par un autre joueur
watch(() => localSessionData.value.attempts, (newAttempts) => {
    if (props.session.type === 'participants' && newAttempts && currentRiddle.value) {
        // Rechercher une tentative pour l'énigme active
        const activeAttempt = newAttempts.find(att => {
            const gameRiddle = att.game_riddle || att.gameRiddle;
            return gameRiddle?.riddle_id === currentRiddle.value.id;
        });
        
        if (activeAttempt && !decisionState.value) {
            // Ignorer si c'est la tentative locale (déjà gérée par les handlers de QCM / GPS)
            if (activeAttempt.user_id === page.props.auth.user.id) {
                return;
            }
            
            // Si c'est une tentative faite par un autre participant
            decisionState.value = 'already_solved';
            clearInterval(timerInterval);
            
            if (activeAttempt.status === 'gagne') {
                alreadySolvedMessage.value = `${activeAttempt.user?.name || 'Un participant'} a résolu cette énigme ! Progression partagée. 🟢`;
            } else {
                alreadySolvedMessage.value = `${activeAttempt.user?.name || 'Un participant'} a tenté et échoué sur cette énigme. 🔴`;
            }
        }
    }
}, { deep: true });

watch(
    () => props.session.statut,
    (statut, prev) => {
        if (statut !== 'termine') return;
        if (prev === 'termine') return;
        finishSessionRedirect();
    }
);

// Calculer la distance en kilomètres par rapport au lieu cible (Formule de Haversine)
const distanceToPlace = computed(() => {
    if (!userCoords.value.lat || !userCoords.value.lng || !currentPlace.value) return null;
    
    const lat1 = userCoords.value.lat;
    const lon1 = userCoords.value.lng;
    const lat2 = currentPlace.value.latitude;
    const lon2 = currentPlace.value.longitude;

    const R = 6371; // Rayon de la Terre en km
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    
    const a = 
        Math.sin(dLat/2) * Math.sin(dLat/2) +
        Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * 
        Math.sin(dLon/2) * Math.sin(dLon/2);
        
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    return R * c; // Distance en km
});

// Moyen de transport recommandé
const recommendedTransport = computed(() => {
    const dist = distanceToPlace.value;
    if (dist === null) return 'Calcul...';
    const meters = dist * 1000;
    if (meters < 200) return '🚶 À pied (Tout proche !)';
    if (meters < 1000) return '🏃 Marche rapide / Course';
    if (meters < 3000) return '🚴 Vélo / Trottinette';
    return '🚗 Voiture / Transports en commun';
});

// Temps recommandé en secondes basé sur la distance
const calculateChronoTimeForDiscovery = () => {
    const dist = distanceToPlace.value;
    if (dist === null) return 600; // 10 minutes par défaut
    const meters = dist * 1000;
    
    if (meters < 200) return 180; // 3 min
    if (meters < 1000) return 480; // 8 min
    if (meters < 3000) return 900; // 15 min
    return 1800; // 30 min max
};

// Initialisation d'une énigme
const startRiddle = (mode) => {
    // Initialiser l'AudioContext au premier geste utilisateur
    initAudioContext();
    playGameStart();

    modeChoisi.value = mode;
    isPlaying.value = true;
    decisionState.value = null;

    if (mode === 'gaming') {
        const riddleLevel = props.session.level;
        totalTime.value = riddleLevel === 'facile' ? 60 : riddleLevel === 'intermediaire' ? 30 : 20;
    } else {
        totalTime.value = calculateChronoTimeForDiscovery();
    }
    
    timeLeft.value = totalTime.value;
    startChrono();
};

const startChrono = () => {
    if (timerInterval) clearInterval(timerInterval);
    timerInterval = setInterval(() => {
        if (!isPaused.value && timeLeft.value > 0) {
            timeLeft.value--;
            // Sons d'alerte chrono
            if (timeLeft.value <= 5 && timeLeft.value > 0) {
                playCountdown();
            } else if (timeLeft.value <= 10 && timeLeft.value > 5) {
                playTick();
            }
        } else if (timeLeft.value <= 0) {
            clearInterval(timerInterval);
            handleLose();
        }
    }, 1000);
};

const togglePause = () => {
    playClick();
    isPaused.value = !isPaused.value;
    if (isPaused.value) {
        pauseBackgroundMusic();
        toast.add({ severity: 'warn', summary: 'Chrono suspendu', detail: 'La pause est active.', life: 2500 });
    } else {
        resumeBackgroundMusic();
        toast.add({ severity: 'info', summary: 'Reprise', detail: 'Le chrono a repris.', life: 2000 });
    }
};

const handleWin = async () => {
    clearInterval(timerInterval);
    decisionState.value = 'win';
    playWin();
    toast.add({ severity: 'success', summary: 'Bonne réponse ! 🎯', detail: `La réponse était bien : ${currentRiddle.value.reponse}`, life: 4000 });
    userStatsStore.addPoints(riddlePoints.value);
    await recordAttemptOnBackend('gagne', riddlePoints.value);
};

const handleLose = async () => {
    clearInterval(timerInterval);
    decisionState.value = 'lose';
    playLose();
    toast.add({ severity: 'error', summary: 'Échec sur cette énigme', detail: 'Votre choix ou le temps écoulé a mené à un échec.', life: 5000 });
    await recordAttemptOnBackend('perdu', 0);
};

const submitDiscovery = async () => {
    if (!distanceToPlace.value) {
        toast.add({ severity: 'warn', summary: 'Signal GPS faible', detail: 'Impossible de valider sans votre position GPS.', life: 4000 });
        return;
    }

    const margin = currentPlace.value?.marge_validation_gps || currentPlace.value?.rayon_marge || 50;
    const distanceInMeters = distanceToPlace.value * 1000;

    if (distanceInMeters <= margin) {
        await handleWin();
    } else {
        toast.add({ 
            severity: 'error', 
            summary: 'Lieu trop éloigné', 
            detail: `Vous êtes à environ ${Math.round(distanceInMeters)}m de la cible (marge requise : ${margin}m).`, 
            life: 6000 
        });
    }
};

const submitQcm = (option) => {
    playClick();
    userAnswer.value = option;
    submitGaming();
};

const submitGaming = async () => {
    playClick();
    const answer = userAnswer.value.trim().toLowerCase();
    const correctAnswer = currentRiddle.value.reponse.trim().toLowerCase();

    if (answer === correctAnswer) {
        await handleWin();
    } else {
        await handleLose();
    }
};

// Choix : Passer au lieu suivant
const goToNextPlace = () => {
    playClick();
    if (props.session.statut === 'termine') {
        finishSessionRedirect();
        return;
    }
    if (props.session.type === 'participants') {
        selectFirstUnattemptedRiddle();
        decisionState.value = null;
        isPlaying.value = false;
        
        const player = props.session.players?.find(p => p.user_id === page.props.auth.user.id);
        if (player && (player.global_mode === 'gaming' || player.global_mode === 'decouverte')) {
            startRiddle(player.global_mode);
        }
        
        if (currentPlaceIndex.value >= props.placesWithRiddles.length) {
            toast.add({ severity: 'success', summary: 'Session terminée ! 🏆', detail: 'Toutes les énigmes ont été clôturées par la session !', life: 6000 });
            setTimeout(() => {
                router.get(route('game.dashboard'));
            }, 3000);
        }
    } else {
        if (hasNextPlace.value) {
            currentPlaceIndex.value++;
            currentRiddleInPlaceIndex.value = 0;
            decisionState.value = null;
            isPlaying.value = false;
            
            const player = props.session.players?.find(p => p.user_id === page.props.auth.user.id);
            if (player && (player.global_mode === 'gaming' || player.global_mode === 'decouverte')) {
                startRiddle(player.global_mode);
            }
        } else {
            toast.add({ severity: 'success', summary: 'Partie terminée ! 🏆', detail: 'Vous avez complété l\'aventure ! En route vers le Dashboard.', life: 6000 });
            setTimeout(() => {
                router.get(route('game.dashboard'));
            }, 3000);
        }
    }
};

// Choix : Autre énigme pour le même lieu
const loadAnotherRiddle = () => {
    if (hasMoreRiddlesForPlace.value) {
        currentRiddleInPlaceIndex.value++;
        decisionState.value = null;
        isPlaying.value = false;
        
        const player = props.session.players?.find(p => p.user_id === page.props.auth.user.id);
        if (player && (player.global_mode === 'gaming' || player.global_mode === 'decouverte')) {
            startRiddle(player.global_mode);
        }
    } else {
        toast.add({ severity: 'warn', summary: 'Énigmes épuisées', detail: 'Plus d\'énigmes de ce niveau pour ce lieu.', life: 4000 });
    }
};

// Choix : Perdre la session carrément
const forfeitSession = () => {
    confirm.require({
        message: 'Êtes-vous absolument sûr de vouloir abandonner cette session ? Toute votre progression pour cette partie sera perdue.',
        header: 'Abandonner la partie ⚠️',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Rester',
        acceptLabel: 'Abandonner',
        rejectClass: 'p-button-secondary p-button-outlined text-gray-300 border-gray-600 hover:bg-gray-850 px-4 py-2 rounded-lg mr-2',
        acceptClass: 'p-button-danger bg-red-600 border-red-600 text-white hover:bg-red-500 px-4 py-2 rounded-lg',
        accept: () => {
            toast.add({ severity: 'info', summary: 'Session abandonnée', detail: 'Retour au tableau de bord...', life: 3000 });
            setTimeout(() => {
                router.get(route('game.dashboard'));
            }, 2000);
        }
    });
};

const formatTime = (seconds) => {
    const m = Math.floor(seconds / 60).toString().padStart(2, '0');
    const s = (seconds % 60).toString().padStart(2, '0');
    return `${m}:${s}`;
};
</script>

<template>
    <AuthenticatedLayout title="En Jeu">
        <Toast position="top-right" />
        <div class="min-h-screen text-white font-sans flex flex-col relative overflow-hidden">
            
            <!-- Main Game Play Area -->
            <main class="relative z-10 flex-1 flex flex-col items-center justify-center p-4">
                <div class="max-w-3xl w-full">
                    
                    <!-- Live Competitive Scoreboard (Lobby Mode support) -->
                    <div v-if="session.type !== 'solo'" class="mb-6 panel-glass p-5 border border-[#26272F] animate-fade-in-up">
                        <div class="flex items-center justify-between mb-4 border-b border-[#26272F] pb-2">
                            <div class="flex items-center gap-2">
                                <span class="text-xl">🏆</span>
                                <h3 class="text-xs font-black uppercase tracking-widest text-gray-400">Classement en Direct</h3>
                            </div>
                            <span class="text-[8px] bg-[#2fc276]/10 border border-[#2fc276]/20 text-[#2fc276] px-2.5 py-0.5 rounded-lg font-black uppercase tracking-widest animate-pulse text-glow-green">En Direct</span>
                        </div>
                        
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <div v-for="(p, idx) in sessionLeaderboard" :key="p.id" 
                                class="flex items-center gap-3 bg-[#1C1D24] p-3 rounded-xl border border-[#26272F]"
                                :class="p.user_id === page.props.auth.user.id ? 'border-[#2fc276]/40 bg-[#2fc276]/5 glow-green' : ''">
                                <div class="w-8 h-8 rounded-full border-2 border-[#2fc276] bg-[#0D0E12] text-[#2fc276] flex items-center justify-center font-black text-xs">
                                    {{ idx + 1 }}
                                </div>
                                <div class="overflow-hidden">
                                    <p class="text-xs font-black truncate" :class="p.user_id === page.props.auth.user.id ? 'text-[#2fc276]' : 'text-white'">{{ p.name }}</p>
                                    <p class="text-[9px] font-black text-gray-500 uppercase">{{ p.points }} XP</p>
                                </div>
                            </div>
                        </div>
                        <p
                            v-if="session.type === 'participants' && participantsTeamTarget > 0"
                            class="mt-4 text-center text-[10px] font-black uppercase tracking-widest text-[#4769b0]"
                        >
                            Objectif coop : {{ participantsTeamAnswered }} / {{ participantsTeamTarget }} énigmes clôturées par l'équipe
                        </p>
                    </div>
                    
                    <!-- Contrôles pause / quitter (accessibles pendant toute l'énigme, hors overlays) -->
                    <div
                        v-if="isPlaying && currentRiddle"
                        class="w-full flex flex-wrap justify-end gap-2 mb-3 animate-fade-in-up"
                    >
                        <button
                            type="button"
                            @click="fetchHints"
                            :disabled="isFetchingHints"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-amber-500/30 bg-amber-500/10 text-xs font-black uppercase tracking-widest text-amber-500 hover:bg-amber-500/20 transition-all disabled:opacity-50"
                        >
                            <Zap v-if="!isFetchingHints" :size="16" />
                            <RotateCcw v-else :size="16" class="animate-spin" />
                            Indice
                        </button>
                        <button
                            type="button"
                            @click="togglePause"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-[#26272F] bg-[#1C1D24] text-xs font-black uppercase tracking-widest text-[#f3a900] hover:border-[#f3a900]/40 transition-all"
                        >
                            <Pause v-if="!isPaused" :size="16" />
                            <Play v-else :size="16" />
                            <span v-if="!isPaused">Pause</span>
                            <span v-else>Reprendre</span>
                        </button>
                        <button
                            type="button"
                            @click="forfeitSession"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-red-500/30 bg-[#1C1D24] text-xs font-black uppercase tracking-widest text-red-400 hover:bg-red-500/10 transition-all"
                        >
                            <LogOut :size="16" />
                            Quitter la partie
                        </button>
                    </div>

                    <!-- Game Mode Header HUD -->
                    <div v-if="isPlaying" class="flex flex-wrap items-center gap-3 mb-6 animate-fade-in-up">
                        <div class="flex items-center gap-2 px-4 py-2 bg-white/5 rounded-xl border border-white/5">
                            <MapIcon :size="12" class="text-[#2fc276]" />
                            <span class="text-[9px] font-black uppercase tracking-widest text-gray-400">Secteur: {{ currentPlace?.nom }}</span>
                        </div>
                        <div class="flex items-center gap-2 px-4 py-2 bg-[#2fc276]/10 rounded-xl border border-[#2fc276]/20">
                            <Gamepad2 :size="12" class="text-[#2fc276]" />
                            <span class="text-[9px] font-black uppercase tracking-widest text-[#2fc276]">{{ modeChoisi }} Mode</span>
                        </div>
                        <div v-if="session.type !== 'solo'" class="flex items-center gap-2 px-4 py-2 bg-purple-500/10 rounded-xl border border-purple-500/20">
                            <Target :size="12" class="text-purple-400" />
                            <span class="text-[9px] font-black uppercase tracking-widest text-purple-400">Type: {{ session.type }}</span>
                        </div>
                    </div>

                    <!-- Mode Selection Screen (If Mixte is chosen) -->
                    <div v-if="!isPlaying" class="text-center animate-fade-in-up py-10">
                        <h2 class="text-3xl font-black uppercase italic tracking-tighter text-white mb-10">
                            CHOISISSEZ VOTRE <span class="text-[#2fc276]">MODE</span> DE RÉSOLUTION
                        </h2>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 max-w-2xl mx-auto">
                            <!-- Decouverte Adventure Card -->
                            <div class="bg-[#1C1D24] p-8 rounded-3xl border-2 border-[#f3a900]/30 hover:border-[#f3a900] hover:bg-[#f3a900]/5 transition-all duration-300 relative group flex flex-col items-center">
                                <MapIcon :size="64" class="mb-4 text-[#f3a900] transform group-hover:scale-105 group-hover:rotate-6 transition-transform" />
                                <h3 class="text-xl font-black text-[#f3a900] text-glow-yellow uppercase tracking-tight mb-2">Découverte</h3>
                                <p class="text-xs text-gray-400 font-semibold mb-6">Validez vos coordonnées GPS physiques sur place.</p>
                                <button @click="startRiddle('decouverte')" class="btn-3d btn-3d-yellow w-full py-3 text-xs shadow-[0_4px_0_#9e6f00] flex items-center justify-center gap-2">
                                    <MapPin :size="14" />
                                    C'est parti !
                                </button>
                            </div>
                            
                            <!-- Gaming couch Card -->
                            <div class="bg-[#1C1D24] p-8 rounded-3xl border-2 border-[#2c72f6]/30 hover:border-[#2c72f6] hover:bg-[#2c72f6]/5 transition-all duration-300 relative group flex flex-col items-center">
                                <Gamepad2 :size="64" class="mb-4 text-[#2c72f6] transform group-hover:scale-105 group-hover:-rotate-6 transition-transform" />
                                <h3 class="text-xl font-black text-[#2c72f6] text-glow-blue uppercase tracking-tight mb-2">Gaming</h3>
                                <p class="text-xs text-gray-400 font-semibold mb-6">Répondez intellectuellement depuis chez vous.</p>
                                <button @click="startRiddle('gaming')" class="btn-3d btn-3d-blue w-full py-3 text-xs shadow-[0_4px_0_#1344a1] flex items-center justify-center gap-2">
                                    <Zap :size="14" />
                                    C'est parti !
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Active Riddle Board Console -->
                    <div v-if="isPlaying && currentRiddle" class="panel-glass p-4 sm:p-8 border border-[#26272F] relative overflow-hidden animate-fade-in-up shadow-2xl">
                        
                        <!-- Overlay intermediate states (Celebration/Failed/Solved) -->
                        <div v-if="decisionState" class="absolute inset-0 bg-[#0D0E12]/95 backdrop-blur-md z-30 flex flex-col items-center justify-center p-4 sm:p-8 text-center rounded-3xl">
                            
                            <!-- Win state overlay -->
                            <template v-if="decisionState === 'win'">
                                <div class="relative mb-6">
                                    <div class="absolute inset-0 bg-[#2fc276]/20 blur-2xl rounded-full"></div>
                                    <Trophy :size="80" class="text-[#2fc276] relative animate-bounce" />
                                </div>
                                <h2 class="text-4xl font-black text-[#2fc276] text-glow-green uppercase italic tracking-tighter mb-2">Énigme Résolue !</h2>
                                <p class="text-sm text-gray-400 font-bold mb-10 max-w-md">
                                    Vous avez résolu cette énigme avec brio et empochez <span class="text-[#f3a900] text-glow-yellow font-black">{{ riddlePoints }} XP</span> !
                                </p>
                                
                                <div class="flex flex-col gap-4 w-full max-w-xs">
                                    <button @click="goToNextPlace" class="btn-3d btn-3d-green w-full py-4 text-sm shadow-[0_5px_0_#1e7d4b]">
                                        <CheckCircle2 :size="18" class="mr-2 inline" />
                                        {{ hasNextPlace ? 'Passer au lieu suivant' : 'Terminer l\'aventure !' }}
                                    </button>
                                    <button type="button" @click="forfeitSession" class="btn-3d btn-3d-red w-full py-3 text-xs shadow-[0_4px_0_#9e2318]">
                                        <LogOut :size="14" class="mr-2 inline" />
                                        Quitter la partie
                                    </button>
                                </div>
                            </template>

                            <!-- Lose state overlay -->
                            <template v-if="decisionState === 'lose'">
                                <div class="relative mb-6">
                                    <div class="absolute inset-0 bg-red-500/20 blur-2xl rounded-full"></div>
                                    <Skull :size="80" class="text-red-500 relative animate-pulse" />
                                </div>
                                <h2 class="text-4xl font-black text-[#ea4335] text-glow-red uppercase italic tracking-tighter mb-2">Échec de l'énigme</h2>
                                <p class="text-sm text-gray-400 font-bold mb-10 max-w-md">Le chrono s'est écoulé ou vous avez soumis une mauvaise réponse. Choisissez votre destin :</p>
                                
                                <div class="flex flex-col gap-4 w-full max-w-xs">
                                     <button @click="fetchHints" class="btn-3d btn-3d-yellow w-full py-4 text-sm shadow-[0_5px_0_#9e6f00] flex items-center justify-center gap-2">
                                         <Zap :size="18" />
                                         Voir un indice
                                     </button>
                                     <button v-if="hasMoreRiddlesForPlace" @click="loadAnotherRiddle" class="btn-3d btn-3d-blue w-full py-4 text-sm shadow-[0_5px_0_#1344a1] flex items-center justify-center gap-2">
                                         <RotateCcw :size="18" />
                                         Autre énigme sur ce lieu
                                     </button>
                                    <button @click="goToNextPlace" class="btn-3d btn-3d-yellow w-full py-3 text-xs shadow-[0_4px_0_#9e6f00] text-black flex items-center justify-center gap-2">
                                        Passer au lieu suivant
                                        <ChevronRight :size="18" />
                                    </button>
                                    <button @click="forfeitSession" class="btn-3d btn-3d-red w-full py-3 text-xs shadow-[0_4px_0_#9e2318] flex items-center justify-center gap-2">
                                        <LogOut :size="18" />
                                        Abandonner la partie
                                    </button>
                                </div>
                            </template>

                            <!-- Already Solved state overlay -->
                            <template v-if="decisionState === 'already_solved'">
                                <AlertTriangle :size="80" class="text-[#f3a900] mb-6" />
                                <h2 class="text-4xl font-black text-[#f3a900] text-glow-yellow uppercase italic tracking-tighter mb-2">Énigme Clôturée</h2>
                                <p class="text-sm text-gray-400 font-bold mb-10 max-w-md">{{ alreadySolvedMessage || 'Un coéquipier ou challenger a déjà répondu avec succès.' }}</p>
                                
                                <div class="flex flex-col gap-4 w-full max-w-xs">
                                    <button @click="goToNextPlace" class="btn-3d btn-3d-blue w-full py-4 text-sm shadow-[0_5px_0_#1344a1] flex items-center justify-center gap-2">
                                        Passer au lieu suivant
                                        <ChevronRight :size="18" />
                                    </button>
                                    <button type="button" @click="forfeitSession" class="btn-3d btn-3d-red w-full py-3 text-xs shadow-[0_4px_0_#9e2318] flex items-center justify-center gap-2">
                                        <LogOut :size="14" />
                                        Quitter la partie
                                    </button>
                                </div>
                            </template>
                        </div>

                        <!-- Paused overlay -->
                        <div v-if="isPaused" class="absolute inset-0 bg-[#0D0E12]/95 backdrop-blur-md z-20 flex flex-col items-center justify-center rounded-3xl">
                            <Pause :size="80" class="text-[#f3a900] mb-6 animate-pulse" />
                            <h2 class="text-3xl font-black uppercase italic tracking-tighter text-[#f3a900] text-glow-yellow mb-8">Jeu en Pause</h2>
                            <button @click="togglePause" class="btn-3d btn-3d-yellow px-8 py-3.5 text-xs text-[#0A0B0E] font-black shadow-[0_5px_0_#9e6f00]">REPRENDRE</button>
                        </div>

                        <!-- Hints overlay -->
                        <div v-if="showHintsModal" class="absolute inset-0 bg-[#0D0E12]/98 backdrop-blur-xl z-40 flex flex-col p-6 sm:p-10 rounded-3xl border-2 border-amber-500/20">
                            <div class="flex items-center justify-between mb-8">
                                <div class="flex items-center gap-3">
                                    <Zap :size="24" class="text-amber-500 animate-pulse" />
                                    <h2 class="text-2xl font-black uppercase italic tracking-tighter text-white">Protocole d'aide</h2>
                                </div>
                                <button @click="showHintsModal = false" class="h-10 w-10 rounded-xl bg-white/5 flex items-center justify-center hover:bg-white/10 transition-all">
                                    <X :size="20" />
                                </button>
                            </div>

                            <div v-if="riddleHints.length > 0" class="flex-1 overflow-y-auto space-y-6 pr-2 custom-scrollbar">
                                <div v-for="(hint, index) in riddleHints" :key="hint.id" class="p-6 rounded-2xl bg-white/5 border border-white/5 space-y-3 animate-fade-in-up" :style="{ animationDelay: (index * 0.1) + 's' }">
                                    <div class="flex items-center justify-between">
                                        <span class="text-[8px] font-black uppercase tracking-[0.3em] text-amber-500">INDICE #{{ index + 1 }} • {{ hint.difficulty_level }}</span>
                                        <span class="text-[8px] font-black uppercase tracking-widest text-gray-500 italic">{{ hint.type }}</span>
                                    </div>
                                    
                                    <div v-if="hint.type === 'image'" class="rounded-xl overflow-hidden border border-white/10">
                                        <img :src="hint.content" class="w-full h-auto object-cover max-h-48" alt="Indice visuel" />
                                    </div>
                                    <p v-else class="text-sm font-bold leading-relaxed text-gray-200 italic">
                                        "{{ hint.content }}"
                                    </p>
                                </div>
                            </div>
                            
                            <div v-else class="flex-1 flex flex-col items-center justify-center text-center space-y-4">
                                <AlertTriangle :size="48" class="text-gray-600" />
                                <p class="text-gray-500 font-black uppercase tracking-widest text-xs">Aucun indice disponible pour cette énigme.</p>
                            </div>

                            <div class="mt-8 pt-6 border-t border-white/5">
                                <button @click="showHintsModal = false" class="w-full btn-3d btn-3d-yellow py-4 text-xs font-black shadow-[0_5px_0_#9e6f00] text-black">RETOURNER À L'ÉNIGME</button>
                            </div>
                        </div>

                        <!-- Game Header Details -->
                        <div class="flex flex-col sm:flex-row gap-4 justify-between items-stretch sm:items-center mb-8 border-b border-[#26272F] pb-6">
                            <div class="flex flex-col gap-1 text-center sm:text-left">
                                <span class="text-[8px] font-black uppercase tracking-widest text-gray-500">Progression</span>
                                <div class="flex items-center justify-center sm:justify-start gap-2 flex-wrap">
                                    <span class="text-sm font-black text-white">
                                        <template v-if="session.type === 'participants' && participantsTeamTarget > 0">
                                            Équipe {{ participantsTeamAnswered }} / {{ participantsTeamTarget }}
                                        </template>
                                        <template v-else>
                                            Lieu {{ currentRiddleNumber }} / {{ totalGamePlacesCount }}
                                        </template>
                                    </span>
                                    <span class="text-[9px] font-black bg-[#2fc276]/10 border border-[#2fc276]/20 text-[#2fc276] px-2.5 py-0.5 rounded-lg tracking-wider text-glow-green uppercase">
                                        {{ currentPlace?.nom }}
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Chrono (Découverte) -->
                            <div v-if="modeChoisi === 'decouverte'" class="flex items-center justify-center sm:justify-start gap-4">
                                <div class="flex items-center gap-2 bg-[#1C1D24] border border-[#26272F] px-4 py-2 rounded-xl">
                                    <span class="w-2.5 h-2.5 rounded-full animate-ping" :class="timeLeft < 30 ? 'bg-red-500' : 'bg-[#2fc276]'"></span>
                                    <span class="text-sm font-mono font-black tabular-nums" :class="timeLeft < 30 ? 'text-red-400' : 'text-white'">
                                        {{ formatTime(timeLeft) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Chrono Boussole Ring (Gaming mode) -->
                        <div v-if="modeChoisi === 'gaming'" class="flex flex-col items-center justify-center mb-8">
                            <div class="relative w-36 h-36 flex items-center justify-center bg-[#0D0E12] rounded-full border-4 border-[#26272F] shadow-[0_0_20px_rgba(47,194,118,0.15)] backdrop-blur">
                                <!-- SVG Ring -->
                                <svg class="absolute inset-0 w-full h-full transform -rotate-90">
                                    <circle
                                        cx="72"
                                        cy="72"
                                        r="64"
                                        stroke="#1C1D24"
                                        stroke-width="8"
                                        fill="transparent"
                                    />
                                    <circle
                                        cx="72"
                                        cy="72"
                                        r="64"
                                        stroke="url(#neonGradient)"
                                        stroke-width="8"
                                        fill="transparent"
                                        :stroke-dasharray="strokeDasharray"
                                        :stroke-dashoffset="strokeDashoffset"
                                        stroke-linecap="round"
                                        class="transition-all duration-1000 ease-linear"
                                    />
                                    <defs>
                                        <linearGradient id="neonGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                            <stop offset="0%" stop-color="#2fc276" />
                                            <stop offset="100%" stop-color="#2c72f6" />
                                        </linearGradient>
                                    </defs>
                                </svg>
                                
                                <!-- Compass Details -->
                                <div class="text-center z-10 flex flex-col items-center">
                                    <Compass :size="24" class="mb-1 text-[#2fc276] transition-transform duration-1000 ease-linear" :style="{ transform: `rotate(${compassRotation}deg)` }" />
                                    <div class="text-3xl font-black font-mono tabular-nums text-white text-glow-green">
                                        {{ timeLeft }}s
                                    </div>
                                    <div class="text-[8px] uppercase tracking-widest text-gray-500 font-black">
                                        Chrono
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Riddle Quote Text Box -->
                        <div class="text-center mb-10 px-4">
                            <h2 class="text-xl sm:text-2xl text-white font-black italic leading-relaxed text-glow-green">
                                "{{ currentRiddle.description }}"
                            </h2>
                        </div>

                        <!-- COLUMN ACTION : Découverte (Physical Validation) -->
                        <div v-if="modeChoisi === 'decouverte'" class="space-y-6">
                            <div class="bg-[#1C1D24] border border-[#26272F] p-5 rounded-2xl text-center">
                                <p class="text-xs font-black uppercase text-gray-500 tracking-wider mb-2">Transport Conseillé</p>
                                <span class="inline-block px-4 py-2 bg-[#0D0E12] rounded-xl border border-[#26272F] text-xs font-black text-white">
                                    {{ recommendedTransport }}
                                </span>
                            </div>
                            
                            <button @click="submitDiscovery" class="btn-3d btn-3d-green w-full py-5 text-lg font-black shadow-[0_6px_0_#1e7d4b] tracking-widest">
                                📍 JE SUIS SUR PLACE (VALIDER GPS)
                            </button>
                        </div>

                        <!-- COLUMN ACTION : Gaming (Couch Selection) -->
                        <div v-if="modeChoisi === 'gaming'" class="space-y-6">
                            <!-- Difficult Text input answer -->
                            <div v-if="session.level === 'difficile'" class="space-y-4">
                                <label class="block text-xs font-black uppercase tracking-widest text-gray-500 text-center">Quel est le nom de ce lieu ?</label>
                                <input v-model="userAnswer" type="text" placeholder="Entrez la réponse exacte..." 
                                    @keydown="playKey"
                                    @keydown.enter="submitGaming"
                                    class="w-full bg-[#0D0E12] border-2 border-[#26272F] focus:border-[#2fc276] focus:ring-0 rounded-2xl p-4.5 text-lg text-center text-white font-black uppercase tracking-widest transition-colors">
                                <button @click="submitGaming" :disabled="!userAnswer" class="btn-3d btn-3d-green w-full py-4 text-sm shadow-[0_5px_0_#1e7d4b] flex items-center justify-center gap-2">
                                    SOUMETTRE LA RÉPONSE
                                    <Rocket :size="18" />
                                </button>
                            </div>
                            
                            <!-- Easy/Intermediate MCQ grids -->
                            <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <button v-for="option in parsedMcqOptions" :key="option"
                                    @click="submitQcm(option)"
                                    class="btn-3d btn-3d-blue py-5 text-sm shadow-[0_4px_0_#1344a1] text-center">
                                    {{ option }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.animate-fade-in-up {
    animation: fadeInUp 0.5s ease-out forwards;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Custom Responsive Toast Notification styles */
:global(.p-toast) {
    max-width: calc(100vw - 2rem) !important;
}
@media (max-width: 480px) {
    :global(.p-toast) {
        width: calc(100vw - 2rem) !important;
        left: 1rem !important;
        right: 1rem !important;
        top: 1rem !important;
    }
    :global(.p-toast-message) {
        margin: 0 0 1rem 0 !important;
    }
}
</style>
