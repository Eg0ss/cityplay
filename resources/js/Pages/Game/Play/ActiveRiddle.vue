<script setup>
import { ref, onMounted, computed, onUnmounted, watch, nextTick } from 'vue';
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
    gameSteps: Array
});

const page = usePage();
const toast = useToast();
const confirm = useConfirm();

// Clé de stockage unique par session
const storageKey = `game_state_${props.session.id}`;

// Fonction pour charger l'état depuis localStorage
const loadGameState = () => {
    try {
        const saved = localStorage.getItem(storageKey);
        if (saved) {
            return JSON.parse(saved);
        }
    } catch (e) {
        console.error('Erreur lors du chargement de l\'état:', e);
    }
    return null;
};

// Fonction pour sauvegarder l'état dans localStorage
const saveGameState = () => {
    try {
        const state = {
            currentPlaceIndex: currentPlaceIndex.value,
            modeChoisi: modeChoisi.value,
            isPlaying: isPlaying.value,
            timeLeft: timeLeft.value,
            totalTime: totalTime.value,
            endTime: endTime.value,
            isPaused: isPaused.value,
            decisionState: decisionState.value,
            timestamp: Date.now(),
        };
        localStorage.setItem(storageKey, JSON.stringify(state));
    } catch (e) {
        console.error('Erreur lors de la sauvegarde de l\'état:', e);
    }
};

// Charger l'état sauvegardé ou initialiser
const savedState = loadGameState();

// État local du jeu
const currentPlaceIndex = ref(savedState?.currentPlaceIndex ?? 0);
const modeChoisi = ref(savedState?.modeChoisi ?? null); // Pour le mode 'mixte'
const isPlaying = ref(savedState?.isPlaying ?? false);
const timeLeft = ref(savedState?.timeLeft ?? 0);
const totalTime = ref(savedState?.totalTime ?? 0);
const endTime = ref(savedState?.endTime ?? null);
const isPaused = ref(savedState?.isPaused ?? false);
const userAnswer = ref('');
const userCoords = ref({ lat: null, lng: null });

// Moyen de transport pour le mode découverte
const transportMode = ref(savedState?.transportMode ?? null); // 'pied' | 'moto' | 'voiture' | 'avion'
const showTransportSelection = ref(false);

// Vitesses (km/h) et marges (secondes) par moyen de transport
const TRANSPORT_CONFIG = {
    pied:    { speed: 5,   margin: 3 * 60,  minTime: 2 * 60,  emoji: '🚶', label: 'À pied',  sub: '~5 km/h' },
    moto:    { speed: 40,  margin: 5 * 60,  minTime: 3 * 60,  emoji: '🏍️', label: 'Moto',    sub: '~40 km/h' },
    voiture: { speed: 60,  margin: 5 * 60,  minTime: 3 * 60,  emoji: '🚗', label: 'Voiture', sub: '~60 km/h' },
    avion:   { speed: 250, margin: 10 * 60, minTime: 5 * 60,  emoji: '✈️', label: 'Avion',   sub: '~250 km/h' },
};

// État des indices
const showFlashHint = ref(false);

const triggerHint = () => {
    if (showFlashHint.value) return;
    
    playClick();
    showFlashHint.value = true;
    
    // Fermer l'indice automatiquement après 1 seconde
    setTimeout(() => {
        showFlashHint.value = false;
    }, 1000);
};

// État de décision intermédiaire ('win', 'lose', 'already_solved' ou null)
const decisionState = ref(savedState?.decisionState ?? null);

// Réinitialiser l'état quand on change d'énigme
watch([currentPlaceIndex], () => {
    showFlashHint.value = false;
    saveGameState(); // Sauvegarder quand on change d'énigme
});

// Sauvegarder l'état à chaque changement important
watch([currentPlaceIndex, modeChoisi, isPlaying, timeLeft, isPaused, decisionState], () => {
    saveGameState();
}, { deep: true });

const alreadySolvedMessage = ref('');
const isNavigating = ref(false);
const isLoading = ref(false); // État global pour désactiver les boutons pendant les requêtes
let recordPromise = null;

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
    
    // Nettoyer le localStorage
    try {
        localStorage.removeItem(storageKey);
    } catch (e) {
        console.error('Erreur lors du nettoyage du localStorage:', e);
    }
    
    toast.add({
        severity: 'success',
        summary: 'Session terminée ! 🏆',
        detail: message || 'L\'équipe a atteint l\'objectif d\'énigmes. Bravo !',
        life: 3000,
    });
    router.get(route('game.dashboard'));
};

// Trouver la première énigme non tentée dans le mode participants
const selectFirstUnattemptedRiddle = () => {
    if (props.session.type !== 'participants') return;
    
    for (let pIdx = 0; pIdx < props.gameSteps.length; pIdx++) {
        const step = props.gameSteps[pIdx];
        const riddle = step.riddle;
        const attempted = localSessionData.value.attempts?.some(att => {
            const gameRiddle = att.game_riddle || att.gameRiddle;
            return gameRiddle?.riddle_id === riddle.id;
        });
        if (!attempted) {
            currentPlaceIndex.value = pIdx;
            return;
        }
    }
    
    // Si toutes les énigmes ont été tentées, on affiche l'écran de fin
    currentPlaceIndex.value = props.gameSteps.length;
};

const currentPlace = computed(() => {
    return props.gameSteps?.[currentPlaceIndex.value];
});

const currentRiddle = computed(() => {
    return currentPlace.value?.riddle;
});

const riddleImages = computed(() => {
    return currentRiddle.value?.images || [];
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
    // Dans le nouveau système, chaque étape est une énigme unique. 
    // On ne propose "une autre énigme sur ce lieu" que s'il y a d'autres étapes avec le même lieu ID plus loin.
    return props.gameSteps?.some((step, index) => index > currentPlaceIndex.value && step.id === currentPlace.value?.id);
});

// Vérification de la présence d'un lieu suivant
const hasNextPlace = computed(() => {
    return props.gameSteps?.length > currentPlaceIndex.value + 1;
});

// Total des étapes (énigmes)
const totalGamePlacesCount = computed(() => {
    if (props.session.type === 'participants' && participantsTeamTarget.value > 0) {
        return participantsTeamTarget.value;
    }
    return props.gameSteps?.length || 0;
});

const currentRiddleNumber = computed(() => {
    if (props.session.type !== 'participants') {
        return Math.min(currentPlaceIndex.value + 1, props.gameSteps?.length || 0);
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
const recordAttemptOnBackend = (status, pointsEarned) => {
    isLoading.value = true;
    recordPromise = (async () => {
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
        } finally {
            isLoading.value = false;
            recordPromise = null;
        }
    })();
    return recordPromise;
};

// Géolocalisation en temps réel pour le mode découverte
let watchId = null;
let unsubscribeBefore = null;

onMounted(() => {
    if (props.session.statut === 'termine') {
        finishSessionRedirect();
        return;
    }

    // Restaurer le timer si la partie était en cours
    if (savedState && savedState.isPlaying && savedState.endTime) {
        const now = Date.now();
        const remaining = Math.max(0, Math.floor((savedState.endTime - now) / 1000));
        
        if (remaining > 0) {
            // Restaurer le timer
            timeLeft.value = remaining;
            totalTime.value = savedState.totalTime;
            endTime.value = savedState.endTime;
            isPlaying.value = true;
            isPaused.value = savedState.isPaused;
            
            if (!isPaused.value) {
                startChrono();
            }
        } else {
            // Le temps est écoulé pendant l'absence
            timeLeft.value = 0;
            isPlaying.value = false;
            if (modeChoisi.value === 'gaming') {
                handleLose();
            }
        }
    }

    // Démarrer la musique de fond dès le montage (sans interaction requise via autoplay)
    playBackgroundMusic('game');

    document.addEventListener('visibilitychange', handleVisibilityChange);

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
        if (player.global_mode === 'decouverte' && !transportMode.value) {
            showTransportSelection.value = true;
        } else {
            startRiddle(player.global_mode);
        }
    }

    if (navigator.geolocation) {
        watchId = navigator.geolocation.watchPosition(
            (position) => {
                userCoords.value.lat = position.coords.latitude;
                userCoords.value.lng = position.coords.longitude;
            },
            (error) => {
                // On ne loggue l'erreur que si ce n'est pas un refus explicite (Permission Denied)
                if (error.code !== 1) {
                    console.warn("Erreur de suivi GPS:", error);
                }
            },
            { enableHighAccuracy: true }
        );
    }
});

onUnmounted(() => {
    // Sauvegarder l'état avant de quitter (au cas où l'utilisateur reviendrait)
    // Mais ne pas nettoyer si la session est terminée
    if (props.session.statut !== 'termine' && !sessionEndHandled.value) {
        saveGameState();
    } else {
        // Nettoyer si la session est terminée
        try {
            localStorage.removeItem(storageKey);
        } catch (e) {
            console.error('Erreur lors du nettoyage du localStorage:', e);
        }
    }
    
    if (unsubscribeBefore) {
        unsubscribeBefore();
    }
    if (window.Echo) {
        window.Echo.leave(`game.${props.session.lien_token}`);
    }
    document.removeEventListener('visibilitychange', handleVisibilityChange);
    stopBackgroundMusic();
    if (watchId) navigator.geolocation.clearWatch(watchId);
    clearInterval(timerInterval);
});

// Détecter si l'énigme active actuelle (currentRiddle) a été clôturée par un autre joueur
watch(() => localSessionData.value.attempts, (newAttempts) => {
    if (newAttempts && currentRiddle.value) {
        // En mode participants ou challenger (réponse par membre), on verrouille l'énigme si quelqu'un a déjà répondu
        if (props.session.type === 'participants' || (props.session.type === 'challengers' && props.session.challenger_mode === 'reponse_par_membre')) {
            const activeAttempt = newAttempts.find(att => {
                const gameRiddle = att.game_riddle || att.gameRiddle;
                return gameRiddle?.riddle_id === currentRiddle.value.id;
            });
            
            if (activeAttempt && !decisionState.value) {
                // Ignorer si c'est la tentative locale
                if (activeAttempt.user_id === page.props.auth.user.id) {
                    return;
                }
                
                decisionState.value = 'already_solved';
                clearInterval(timerInterval);
                
                if (activeAttempt.status === 'gagne') {
                    alreadySolvedMessage.value = `${activeAttempt.user?.name || 'Un joueur'} a résolu cette énigme ! 🟢`;
                } else {
                    alreadySolvedMessage.value = `${activeAttempt.user?.name || 'Un joueur'} a échoué sur cette énigme. Elle est désormais verrouillée. 🔴`;
                }
            }
        }
    }
}, { deep: true });

// Synchronisation de localSessionData avec props.session lors des reloads Inertia
watch(() => props.session, (newSession) => {
    if (newSession) {
        localSessionData.value.attempts = newSession.attempts || [];
        localSessionData.value.players = newSession.players || [];
        localSessionData.value.statut = newSession.statut;
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

// Temps calculé en secondes selon distance + moyen de transport + marge
const calculateChronoTimeForDiscovery = (transport) => {
    const mode = transport || transportMode.value || 'pied';
    const cfg = TRANSPORT_CONFIG[mode] || TRANSPORT_CONFIG.pied;
    const dist = distanceToPlace.value;

    if (dist === null) {
        // Pas de GPS : on applique un temps par défaut raisonnable selon le transport
        return Math.max(cfg.minTime, cfg.margin + Math.round(1 / cfg.speed * 3600));
    }

    // temps de trajet (h) × 3600 = secondes + marge de confort
    const travelSeconds = Math.round((dist / cfg.speed) * 3600);
    return Math.max(cfg.minTime, travelSeconds + cfg.margin);
};

// Démarre le flux découverte : affiche d'abord l'écran de choix du transport
const requestTransportSelection = () => {
    playClick();
    transportMode.value = null;
    showTransportSelection.value = true;
};

// Appelé quand le joueur choisit son transport → calcule le temps et lance le chrono
const confirmTransportAndStart = (transport) => {
    playClick();
    transportMode.value = transport;
    showTransportSelection.value = false;
    startRiddle('decouverte');
};

const currentPlayer = computed(() => {
    return props.session.players?.find(p => p.user_id === page.props.auth.user.id);
});

// Initialisation d'une énigme
const startRiddle = (mode) => {
    // Initialiser l'AudioContext au premier geste utilisateur
    initAudioContext();
    playGameStart();

    // Réinitialiser l'état de chargement pour que les boutons soient immédiatement cliquables
    isLoading.value = false;

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
    endTime.value = Date.now() + (totalTime.value * 1000);
    userAnswer.value = ''; // Reset answer
    startChrono();
};

const startChrono = () => {
    if (timerInterval) clearInterval(timerInterval);
    timerInterval = setInterval(() => {
        if (!isPaused.value) {
            const now = Date.now();
            const remaining = Math.ceil((endTime.value - now) / 1000);
            
            timeLeft.value = Math.max(0, remaining);
            
            // Sauvegarder l'état du timer
            saveGameState();
            
            if (timeLeft.value <= 0) {
                clearInterval(timerInterval);
                timerInterval = null;
                isPlaying.value = false;
                
                if (modeChoisi.value === 'gaming') {
                    handleLose();
                }
            } else if (timeLeft.value <= 5 && modeChoisi.value === 'gaming') {
                playCountdown();
            }
        }
    }, 100);
};

const handleVisibilityChange = () => {
    if (document.visibilityState === 'visible' && isPlaying.value && !isPaused.value && !decisionState.value) {
        // Recalculer le temps restant immédiatement au retour sur l'onglet
        const now = Date.now();
        const remaining = Math.ceil((endTime.value - now) / 1000);
        timeLeft.value = Math.max(0, remaining);
        
        if (timeLeft.value <= 0) {
            clearInterval(timerInterval);
            handleLose();
        }
    }
};

const togglePause = () => {
    playClick();
    isPaused.value = !isPaused.value;
    if (isPaused.value) {
        pauseBackgroundMusic();
    } else {
        // Recalculer endTime pour compenser le temps passé en pause
        endTime.value = Date.now() + (timeLeft.value * 1000);
        resumeBackgroundMusic();
    }
};

const handleWin = async () => {
    clearInterval(timerInterval);
    decisionState.value = 'win';
    playWin();
    toast.add({ severity: 'success', summary: 'Bonne réponse ! 🎯', detail: `La réponse était bien : ${currentRiddle.value.reponse}`, life: 1000 });
    userStatsStore.addPoints(riddlePoints.value);
    await recordAttemptOnBackend('gagne', riddlePoints.value);
};

const handleLose = async () => {
    clearInterval(timerInterval);
    decisionState.value = 'lose';
    playLose();
    toast.add({ severity: 'error', summary: 'Échec sur cette énigme', detail: 'Votre choix ou le temps écoulé a mené à un échec.', life: 1000 });
    await recordAttemptOnBackend('perdu', 0);
};

const submitDiscovery = async () => {
    if (isLoading.value) return;
    
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
    if (isLoading.value) return;
    playClick();
    userAnswer.value = option;
    submitGaming();
};

const submitGaming = async () => {
    if (isLoading.value) return;
    
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
    if (isNavigating.value) return;
    isNavigating.value = true;
    
    // Réinitialiser immédiatement isLoading pour que les boutons du prochain lieu soient cliquables
    isLoading.value = false;
    
    playClick();
    
    // L'enregistrement backend continue en tâche de fond, on ne bloque plus la navigation
    if (recordPromise) {
        recordPromise.catch(e => console.error("Erreur enregistrement backend (arrière-plan):", e));
    }
    
    if (props.session.statut === 'termine') {
        finishSessionRedirect();
        return;
    }
    
    const nextRiddleLogic = () => {
        const player = props.session.players?.find(p => p.user_id === page.props.auth.user.id);
        if (player && (player.global_mode === 'gaming' || player.global_mode === 'decouverte')) {
            if (player.global_mode === 'decouverte') {
                // Réinitialiser le transport pour que le joueur choisisse à nouveau
                transportMode.value = null;
                showTransportSelection.value = true;
            } else {
                startRiddle(player.global_mode);
            }
        } else {
            // Mode mixte : isPlaying reste false pour afficher le choix du mode
            isPlaying.value = false;
            decisionState.value = null;
        }
        isNavigating.value = false;
    };

    if (props.session.type === 'participants') {
        selectFirstUnattemptedRiddle();
        decisionState.value = null;
        
        if (currentPlaceIndex.value >= props.gameSteps.length) {
            toast.add({ severity: 'success', summary: 'Session terminée ! 🏆', detail: 'Toutes les énigmes ont été clôturées par la session !', life: 2000 });
            router.get(route('game.dashboard'));
            isNavigating.value = false;
        } else {
            nextTick(() => {
                nextRiddleLogic();
            });
        }
    } else {
        if (hasNextPlace.value) {
            currentPlaceIndex.value++;
            decisionState.value = null;
            nextTick(() => {
                nextRiddleLogic();
            });
        } else {
            toast.add({ severity: 'success', summary: 'Partie terminée ! 🏆', detail: 'Vous avez complété l\'aventure ! En route vers le Dashboard.', life: 2000 });
            router.get(route('game.dashboard'));
            isNavigating.value = false;
        }
    }
};

// Choix : Autre énigme pour le même lieu
const loadAnotherRiddle = async () => {
    if (isNavigating.value) return;
    isNavigating.value = true;
    
    if (recordPromise) {
        try {
            await recordPromise;
        } catch (e) {
            console.error("Erreur lors de l'attente de l'enregistrement backend:", e);
        }
    }
    
    // On cherche la prochaine étape qui a le même lieu
    const nextStepIndex = props.gameSteps.findIndex((step, index) => index > currentPlaceIndex.value && step.id === currentPlace.value?.id);
    
    if (nextStepIndex !== -1) {
        currentPlaceIndex.value = nextStepIndex;
        decisionState.value = null;
        
        if (currentPlayer.value && (currentPlayer.value.global_mode === 'gaming' || currentPlayer.value.global_mode === 'decouverte')) {
            if (currentPlayer.value.global_mode === 'decouverte') {
                transportMode.value = null;
                showTransportSelection.value = true;
            } else {
                startRiddle(currentPlayer.value.global_mode);
            }
        } else {
            isPlaying.value = false;
        }
    } else {
        toast.add({ severity: 'warn', summary: 'Énigmes épuisées', detail: 'Plus d\'énigmes de ce niveau pour ce lieu.', life: 4000 });
    }
    isNavigating.value = false;
};

// Choix : Perdre la session carrément
const forfeitSession = () => {
    playClick();
    
    confirm.require({
        message: 'Êtes-vous absolument sûr de vouloir abandonner cette session ? Toute votre progression pour cette partie sera perdue.',
        header: 'Abandonner la partie ⚠️',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Rester',
        acceptLabel: 'Abandonner',
        rejectClass: 'p-button-secondary p-button-outlined text-gray-300 border-gray-600 hover:bg-gray-850 px-4 py-2 rounded-lg mr-2',
        acceptClass: 'p-button-danger bg-red-600 border-red-600 text-white hover:bg-red-500 px-4 py-2 rounded-lg',
        accept: () => {
            playClick();
            // Nettoyer le localStorage
            try {
                localStorage.removeItem(storageKey);
            } catch (e) {
                console.error('Erreur lors du nettoyage du localStorage:', e);
            }
            
            toast.add({ severity: 'info', summary: 'Session abandonnée', detail: 'Retour au tableau de bord...', life: 3000 });
            router.get(route('game.dashboard'));
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
                            @click="triggerHint"
                            :disabled="showFlashHint"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-amber-500/30 bg-amber-500/10 text-xs font-black uppercase tracking-widest text-amber-500 hover:bg-amber-500/20 transition-all disabled:opacity-50"
                        >
                            <Zap :size="16" />
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
                            :disabled="isLoading"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-red-500/30 bg-[#1C1D24] text-xs font-black uppercase tracking-widest text-red-400 hover:bg-red-500/10 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
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
                    <div v-if="!isPlaying && !showTransportSelection" class="text-center animate-fade-in-up py-10">
                        <h2 class="text-3xl font-black uppercase italic tracking-tighter text-white mb-10">
                            CHOISISSEZ VOTRE <span class="text-[#2fc276]">MODE</span> DE RÉSOLUTION
                        </h2>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 max-w-2xl mx-auto">
                            <!-- Decouverte Adventure Card -->
                            <div class="bg-[#1C1D24] p-8 rounded-3xl border-2 border-[#f3a900]/30 hover:border-[#f3a900] hover:bg-[#f3a900]/5 transition-all duration-300 relative group flex flex-col items-center">
                                <MapIcon :size="64" class="mb-4 text-[#f3a900] transform group-hover:scale-105 group-hover:rotate-6 transition-transform" />
                                <h3 class="text-xl font-black text-[#f3a900] text-glow-yellow uppercase tracking-tight mb-2">Découverte</h3>
                                <p class="text-xs text-gray-400 font-semibold mb-6">Validez vos coordonnées GPS physiques sur place.</p>
                                <button @click="requestTransportSelection" class="btn-3d btn-3d-yellow w-full py-3 text-xs shadow-[0_4px_0_#9e6f00] flex items-center justify-center gap-2">
                                    <MapPin :size="14" />
                                    Choisir mon transport
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

                    <!-- Transport Selection Screen (mode découverte) -->
                    <div v-if="showTransportSelection" class="text-center animate-fade-in-up py-10">
                        <h2 class="text-3xl font-black uppercase italic tracking-tighter text-white mb-2">
                            COMMENT ALLEZ-VOUS <span class="text-[#f3a900]">VOUS DÉPLACER</span> ?
                        </h2>
                        <p class="text-xs text-gray-400 font-semibold mb-8">
                            Votre chronomètre sera calculé en fonction de la distance au lieu et de votre moyen de transport.
                        </p>

                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 max-w-2xl mx-auto">
                            <div
                                v-for="(cfg, key) in TRANSPORT_CONFIG"
                                :key="key"
                                @click="confirmTransportAndStart(key)"
                                class="cursor-pointer group bg-[#1C1D24] p-6 rounded-3xl border-2 border-[#f3a900]/20 hover:border-[#f3a900] hover:bg-[#f3a900]/8 transition-all duration-300 flex flex-col items-center gap-3 select-none active:scale-95"
                            >
                                <span class="text-5xl group-hover:scale-110 transition-transform duration-200">{{ cfg.emoji }}</span>
                                <div>
                                    <h3 class="text-sm font-black text-white uppercase tracking-tight">{{ cfg.label }}</h3>
                                    <p class="text-[10px] text-gray-500 font-bold">{{ cfg.sub }}</p>
                                    <p class="text-[9px] text-[#f3a900]/70 font-black uppercase tracking-wider mt-1">
                                        +{{ Math.round(cfg.margin / 60) }} min marge
                                    </p>
                                </div>
                            </div>
                        </div>

                        <button
                            @click="showTransportSelection = false"
                            class="mt-8 text-xs text-gray-500 hover:text-white transition-colors font-bold uppercase tracking-widest"
                        >
                            ← Retour au choix du mode
                        </button>
                    </div>

                    <!-- Active Riddle Board Console -->
                    <div v-if="isPlaying && currentRiddle" class="panel-glass p-4 sm:p-8 border border-[#26272F] relative overflow-hidden animate-fade-in-up shadow-2xl">
                        
                        <!-- Overlay intermediate states (Celebration/Failed/Solved) -->
                        <div v-if="decisionState" class="absolute inset-0 bg-[#0D0E12]/95 backdrop-blur-md z-30 flex flex-col items-center justify-center p-4 sm:p-8 text-center rounded-3xl">
                            
                            <!-- Win state overlay -->
                            <template v-if="decisionState === 'win'">
                                <div class="relative mb-4">
                                    <div class="absolute inset-0 bg-[#2fc276]/20 blur-2xl rounded-full"></div>
                                    <Trophy :size="60" class="text-[#2fc276] relative animate-bounce" />
                                </div>
                                <h2 class="text-3xl font-black text-[#2fc276] text-glow-green uppercase italic tracking-tighter mb-1">Énigme Résolue !</h2>
                                <p class="text-xs text-gray-400 font-bold mb-4">
                                    Vous avez empoché <span class="text-[#f3a900] text-glow-yellow font-black">{{ riddlePoints }} XP</span> !
                                </p>

                                <!-- Lieu Info Card -->
                                <div class="w-full bg-white/5 border border-white/10 rounded-2xl p-4 mb-6 text-left animate-fade-in-up">
                                    <div class="flex items-center gap-3 mb-3">
                                        <div class="w-10 h-10 rounded-lg bg-[#2fc276]/10 flex items-center justify-center border border-[#2fc276]/20">
                                            <MapPin :size="20" class="text-[#2fc276]" />
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-black text-white uppercase">{{ currentPlace?.nom }}</h4>
                                            <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest">Secret Dévoilé</p>
                                        </div>
                                    </div>
                                    <p class="text-xs text-gray-300 leading-relaxed italic mb-4">
                                        "{{ currentPlace?.verified_description || 'Un lieu emblématique chargé d\'histoire à découvrir.' }}"
                                    </p>
                                    <a :href="`https://www.google.com/maps/dir/?api=1&destination=${currentPlace?.latitude},${currentPlace?.longitude}&destination_place_id=${currentPlace?.nom}`" 
                                       target="_blank"
                                       @click="playClick"
                                       class="flex items-center justify-center gap-2 w-full py-2 bg-white/5 hover:bg-white/10 border border-white/10 rounded-xl text-[10px] font-black uppercase tracking-widest text-white transition-all">
                                        <Compass :size="14" />
                                        Ouvrir l'itinéraire GPS
                                    </a>
                                </div>
                                
                                <div class="flex flex-col gap-3 w-full max-w-xs">
                                    <button @click="goToNextPlace" 
                                        :disabled="isNavigating"
                                        class="btn-3d btn-3d-green w-full py-4 text-sm shadow-[0_5px_0_#1e7d4b] disabled:opacity-50 disabled:cursor-not-allowed">
                                        <CheckCircle2 v-if="!isNavigating" :size="18" class="mr-2 inline" />
                                        <RotateCcw v-else :size="18" class="mr-2 inline animate-spin" />
                                        {{ hasNextPlace ? 'Passer au lieu suivant' : 'Terminer l\'aventure !' }}
                                    </button>
                                </div>
                            </template>

                            <!-- Lose state overlay -->
                            <template v-if="decisionState === 'lose'">
                                <div class="relative mb-4">
                                    <div class="absolute inset-0 bg-red-500/20 blur-2xl rounded-full"></div>
                                    <Skull :size="60" class="text-red-500 relative animate-pulse" />
                                </div>
                                <h2 class="text-3xl font-black text-[#ea4335] text-glow-red uppercase italic tracking-tighter mb-1">Échec</h2>
                                <p class="text-xs text-gray-400 font-bold mb-4">Le chrono s'est écoulé ou la réponse était fausse.</p>

                                <!-- Lieu Info Card (Même en cas d'échec, on apprend !) -->
                                <div class="w-full bg-white/5 border border-white/10 rounded-2xl p-4 mb-6 text-left animate-fade-in-up">
                                    <div class="flex items-center gap-3 mb-3">
                                        <div class="w-10 h-10 rounded-lg bg-red-500/10 flex items-center justify-center border border-red-500/20">
                                            <MapPin :size="20" class="text-red-400" />
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-black text-white uppercase">{{ currentPlace?.nom }}</h4>
                                            <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest">Le saviez-vous ?</p>
                                        </div>
                                    </div>
                                    <p class="text-xs text-gray-300 leading-relaxed italic mb-4 line-clamp-3">
                                        "{{ currentPlace?.verified_description || 'Un lieu emblématique chargé d\'histoire à découvrir.' }}"
                                    </p>
                                    <a :href="`https://www.google.com/maps/dir/?api=1&destination=${currentPlace?.latitude},${currentPlace?.longitude}&destination_place_id=${currentPlace?.nom}`" 
                                       target="_blank"
                                       @click="playClick"
                                       class="flex items-center justify-center gap-2 w-full py-2 bg-white/5 hover:bg-white/10 border border-white/10 rounded-xl text-[10px] font-black uppercase tracking-widest text-white transition-all">
                                        <Compass :size="14" />
                                        S'y rendre quand même
                                    </a>
                                </div>
                                
                                <div class="flex flex-col gap-3 w-full max-w-xs">
                                     <button v-if="hasMoreRiddlesForPlace" @click="loadAnotherRiddle" :disabled="isNavigating" class="btn-3d btn-3d-blue w-full py-4 text-sm shadow-[0_5px_0_#1344a1] flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                                         <template v-if="!isNavigating">
                                             <RotateCcw :size="18" />
                                             Autre énigme sur ce lieu
                                         </template>
                                         <template v-else>
                                             <RotateCcw :size="18" class="animate-spin" />
                                             Chargement...
                                         </template>
                                     </button>
                                    <button @click="goToNextPlace" 
                                        :disabled="isNavigating"
                                        class="btn-3d btn-3d-yellow w-full py-3 text-xs shadow-[0_4px_0_#9e6f00] text-black flex items-center justify-center gap-2 disabled:opacity-50">
                                        <template v-if="!isNavigating">
                                            {{ hasNextPlace ? 'Passer au lieu suivant' : 'Terminer l\'aventure !' }}
                                            <ChevronRight v-if="hasNextPlace" :size="18" />
                                            <CheckCircle2 v-else :size="18" />
                                        </template>
                                        <template v-else>
                                            Chargement...
                                            <RotateCcw :size="18" class="animate-spin" />
                                        </template>
                                    </button>
                                    <button @click="forfeitSession" class="btn-3d btn-3d-red w-full py-2 text-[10px] shadow-[0_3px_0_#9e2318] flex items-center justify-center gap-2 opacity-60 hover:opacity-100">
                                        Abandonner
                                    </button>
                                </div>
                            </template>

                            <!-- Already Solved state overlay -->
                            <template v-if="decisionState === 'already_solved'">
                                <AlertTriangle :size="80" class="text-[#f3a900] mb-6" />
                                <h2 class="text-4xl font-black text-[#f3a900] text-glow-yellow uppercase italic tracking-tighter mb-2">Énigme Clôturée</h2>
                                <p class="text-sm text-gray-400 font-bold mb-10 max-w-md">{{ alreadySolvedMessage || 'Un coéquipier ou challenger a déjà répondu avec succès.' }}</p>
                                
                                <div class="flex flex-col gap-4 w-full max-w-xs">
                                    <button @click="goToNextPlace" 
                                        :disabled="isNavigating"
                                        class="btn-3d btn-3d-blue w-full py-4 text-sm shadow-[0_5px_0_#1344a1] flex items-center justify-center gap-2 disabled:opacity-50">
                                        <template v-if="!isNavigating">
                                            {{ hasNextPlace ? 'Passer au lieu suivant' : 'Terminer l\'aventure !' }}
                                            <ChevronRight v-if="hasNextPlace" :size="18" />
                                            <CheckCircle2 v-else :size="18" />
                                        </template>
                                        <template v-else>
                                            Chargement...
                                            <RotateCcw :size="18" class="animate-spin" />
                                        </template>
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
                        <div class="text-center mb-10 px-4 relative">
                            <h2 class="text-xl sm:text-2xl text-white font-black italic leading-relaxed text-glow-green mb-6">
                                "{{ currentRiddle.description }}"
                            </h2>

                            <!-- Flash Hint Small Label -->
                            <transition name="fade">
                                <div v-if="showFlashHint" class="inline-flex flex-col items-center justify-center p-4 bg-amber-500/10 border border-amber-500/30 rounded-2xl backdrop-blur-sm animate-bounce shadow-[0_0_20px_rgba(245,158,11,0.1)]">
                                    <div class="flex items-center gap-2 mb-1">
                                        <Zap :size="14" class="text-amber-500" />
                                        <span class="text-[8px] font-black uppercase tracking-[0.2em] text-amber-500/70">Indice Flash</span>
                                    </div>
                                    <p class="text-lg font-black text-white text-glow-yellow italic">
                                        {{ currentRiddle.hints?.[0]?.content || currentRiddle.reponse }}
                                    </p>
                                </div>
                            </transition>

                            <!-- Riddle Images Gallery -->
                            <div v-if="riddleImages.length > 0" class="flex flex-wrap justify-center gap-4 mt-6">
                                <div v-for="(img, idx) in riddleImages" :key="idx" 
                                    class="relative group overflow-hidden rounded-2xl border-2 border-white/10 hover:border-[#2fc276]/50 transition-all duration-300 shadow-lg">
                                    <img :src="img.image_path.startsWith('http') ? img.image_path : `/storage/${img.image_path}`" 
                                        class="w-full h-40 sm:h-48 object-cover transform group-hover:scale-110 transition-transform duration-500" 
                                        alt="Visuel de l'énigme" />
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex items-end justify-center pb-4">
                                        <span class="text-[10px] font-black uppercase tracking-widest text-white">Cliquer pour agrandir</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- COLUMN ACTION : Découverte (Physical Validation) -->
                        <div v-if="modeChoisi === 'decouverte'" class="space-y-6">
                            <div class="bg-[#1C1D24] border border-[#26272F] p-5 rounded-2xl text-center">
                                <p class="text-xs font-black uppercase text-gray-500 tracking-wider mb-2">Transport Conseillé</p>
                                <span class="inline-block px-4 py-2 bg-[#0D0E12] rounded-xl border border-[#26272F] text-xs font-black text-white">
                                    {{ recommendedTransport }}
                                </span>
                            </div>
                            
                            <button @click="submitDiscovery" :disabled="isLoading" class="btn-3d btn-3d-green w-full py-5 text-lg font-black shadow-[0_6px_0_#1e7d4b] tracking-widest disabled:opacity-50 disabled:cursor-not-allowed">
                                📍 JE SUIS SUR PLACE (VALIDER GPS)
                            </button>
                        </div>

                        <!-- COLUMN ACTION : Gaming (Couch Selection) -->
                        <div v-if="modeChoisi === 'gaming'" class="space-y-6">
                            <!-- Difficult Text input answer OR if MCQ options are missing -->
                            <div v-if="session.level === 'difficile' || parsedMcqOptions.length === 0" class="space-y-4">
                                <label class="block text-xs font-black uppercase tracking-widest text-gray-500 text-center">
                                    {{ parsedMcqOptions.length === 0 ? 'Aucun choix disponible : Saisissez le nom du lieu' : 'Quel est le nom de ce lieu ?' }}
                                </label>
                                <input v-model="userAnswer" type="text" placeholder="Entrez la réponse exacte..." 
                                    @keydown="playKey"
                                    @keydown.enter="submitGaming"
                                    class="w-full bg-[#0D0E12] border-2 border-[#26272F] focus:border-[#2fc276] focus:ring-0 rounded-2xl p-4.5 text-lg text-center text-white font-black uppercase tracking-widest transition-colors">
                                <button @click="submitGaming" :disabled="!userAnswer || isLoading" class="btn-3d btn-3d-green w-full py-4 text-sm shadow-[0_5px_0_#1e7d4b] flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                                    SOUMETTRE LA RÉPONSE
                                    <Rocket :size="18" />
                                </button>
                            </div>
                            
                            <!-- Easy/Intermediate MCQ grids -->
                            <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <button v-for="option in parsedMcqOptions" :key="option"
                                    @click="submitQcm(option)"
                                    :disabled="isLoading"
                                    class="btn-3d btn-3d-blue py-5 text-sm shadow-[0_4px_0_#1344a1] text-center disabled:opacity-50 disabled:cursor-not-allowed">
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
