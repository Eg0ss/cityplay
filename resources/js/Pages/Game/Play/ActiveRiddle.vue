<script setup>
import { ref, onMounted, computed, onUnmounted, watch } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import Toast from 'primevue/toast';
import axios from 'axios';
import { userStatsStore } from '@/store.js';

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

// État de décision intermédiaire ('win', 'lose', 'already_solved' ou null)
const decisionState = ref(null);
const alreadySolvedMessage = ref('');

// Classement de la session en direct
const sessionLeaderboard = computed(() => {
    return props.session.players?.map(player => {
        const sessionPoints = props.session.attempts
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

// Trouver la première énigme non tentée dans le mode participants
const selectFirstUnattemptedRiddle = () => {
    if (props.session.type !== 'participants') return;
    
    for (let pIdx = 0; pIdx < props.placesWithRiddles.length; pIdx++) {
        const place = props.placesWithRiddles[pIdx];
        for (let rIdx = 0; rIdx < (place.riddles?.length || 0); rIdx++) {
            const riddle = place.riddles[rIdx];
            const attempted = props.session.attempts?.some(att => att.game_riddle?.riddle_id === riddle.id);
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

// Total des énigmes (nombre de lieux)
const totalGamePlacesCount = computed(() => {
    return props.placesWithRiddles?.length || 0;
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
    // Écouter le canal du jeu via Laravel Echo pour synchroniser la partie en temps réel
    window.Echo.channel(`game.${props.session.lien_token}`)
        .listen('GameUpdated', (e) => {
            // Recharger la session pour obtenir le classement et les tentatives les plus récentes
            router.reload({ 
                only: ['session'],
                preserveState: true,
                preserveScroll: true
            });
        });

    // Nettoyer tous les canaux, observateurs et timers dès qu'une navigation démarre
    unsubscribeBefore = router.on('before', () => {
        window.Echo.leave(`game.${props.session.lien_token}`);
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
    window.Echo.leave(`game.${props.session.lien_token}`);
    if (watchId) navigator.geolocation.clearWatch(watchId);
    clearInterval(timerInterval);
});

// Détecter si l'énigme active actuelle (currentRiddle) a été clôturée par un autre joueur
watch(() => props.session.attempts, (newAttempts) => {
    if (props.session.type === 'participants' && newAttempts && currentRiddle.value) {
        const hasAttempt = newAttempts.some(att => 
            att.game_riddle?.riddle_id === currentRiddle.value.id
        );
        
        if (hasAttempt && !decisionState.value) {
            // Re-vérifier s'il y a un gagnant
            const winningAttempt = newAttempts.find(att => 
                att.game_riddle?.riddle_id === currentRiddle.value.id && att.status === 'gagne'
            );
            
            if (winningAttempt) {
                if (winningAttempt.user_id === page.props.auth.user.id) {
                    decisionState.value = 'win';
                } else {
                    decisionState.value = 'already_solved';
                    alreadySolvedMessage.value = `${winningAttempt.user?.name || 'Un participant'} a résolu cette énigme ! Progression partagée.`;
                }
                clearInterval(timerInterval);
            }
        }
    }
}, { deep: true });

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
let timerInterval = null;
const startRiddle = (mode) => {
    modeChoisi.value = mode;
    isPlaying.value = true;
    decisionState.value = null;

    if (mode === 'gaming') {
        const riddleLevel = props.session.level;
        totalTime.value = riddleLevel === 'facile' ? 60 : riddleLevel === 'intermediaire' ? 45 : 30;
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
        } else if (timeLeft.value <= 0) {
            clearInterval(timerInterval);
            handleLose();
        }
    }, 1000);
};

const togglePause = () => {
    isPaused.value = !isPaused.value;
    if (isPaused.value) {
        toast.add({ severity: 'warn', summary: 'Chrono suspendu', detail: 'La pause est active.', life: 2500 });
    } else {
        toast.add({ severity: 'info', summary: 'Reprise', detail: 'Le chrono a repris.', life: 2000 });
    }
};

const handleWin = async () => {
    clearInterval(timerInterval);
    decisionState.value = 'win';
    toast.add({ severity: 'success', summary: 'Bonne réponse ! 🎯', detail: `La réponse était bien : ${currentRiddle.value.reponse}`, life: 4000 });
    userStatsStore.addPoints(riddlePoints.value);
    await recordAttemptOnBackend('gagne', riddlePoints.value);
};

const handleLose = async () => {
    clearInterval(timerInterval);
    decisionState.value = 'lose';
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
    userAnswer.value = option;
    submitGaming();
};

const submitGaming = async () => {
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
                    </div>
                    
                    <!-- Mode Selection Screen (If Mixte is chosen) -->
                    <div v-if="!isPlaying" class="text-center animate-fade-in-up py-10">
                        <h2 class="text-3xl font-black uppercase italic tracking-tighter text-white mb-10">
                            CHOISISSEZ VOTRE <span class="text-[#2fc276]">MODE</span> DE RÉSOLUTION
                        </h2>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 max-w-2xl mx-auto">
                            <!-- Decouverte Adventure Card -->
                            <div class="bg-[#1C1D24] p-8 rounded-3xl border-2 border-[#f3a900]/30 hover:border-[#f3a900] hover:bg-[#f3a900]/5 transition-all duration-300 relative group flex flex-col items-center">
                                <div class="text-6xl mb-4 transform group-hover:scale-105 group-hover:rotate-6 transition-transform">🗺️</div>
                                <h3 class="text-xl font-black text-[#f3a900] text-glow-yellow uppercase tracking-tight mb-2">Découverte</h3>
                                <p class="text-xs text-gray-400 font-semibold mb-6">Validez vos coordonnées GPS physiques sur place.</p>
                                <button @click="startRiddle('decouverte')" class="btn-3d btn-3d-yellow w-full py-3 text-xs shadow-[0_4px_0_#9e6f00]">
                                    C'est parti ! 📍
                                </button>
                            </div>
                            
                            <!-- Gaming couch Card -->
                            <div class="bg-[#1C1D24] p-8 rounded-3xl border-2 border-[#2c72f6]/30 hover:border-[#2c72f6] hover:bg-[#2c72f6]/5 transition-all duration-300 relative group flex flex-col items-center">
                                <div class="text-6xl mb-4 transform group-hover:scale-105 group-hover:-rotate-6 transition-transform">🎮</div>
                                <h3 class="text-xl font-black text-[#2c72f6] text-glow-blue uppercase tracking-tight mb-2">Gaming</h3>
                                <p class="text-xs text-gray-400 font-semibold mb-6">Répondez intellectuellement depuis chez vous.</p>
                                <button @click="startRiddle('gaming')" class="btn-3d btn-3d-blue w-full py-3 text-xs shadow-[0_4px_0_#1344a1]">
                                    C'est parti ! 🕹️
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
                                <div class="text-7xl mb-6 animate-bounce">🏆</div>
                                <h2 class="text-4xl font-black text-[#2fc276] text-glow-green uppercase italic tracking-tighter mb-2">Énigme Résolue !</h2>
                                <p class="text-sm text-gray-400 font-bold mb-10 max-w-md">
                                    Vous avez résolu cette énigme avec brio et empochez <span class="text-[#f3a900] text-glow-yellow font-black">{{ riddlePoints }} XP</span> !
                                </p>
                                
                                <div class="flex flex-col gap-4 w-full max-w-xs">
                                    <button @click="goToNextPlace" class="btn-3d btn-3d-green w-full py-4 text-sm shadow-[0_5px_0_#1e7d4b]">
                                        {{ hasNextPlace ? 'Passer au lieu suivant 👉' : 'Terminer l\'aventure ! 🏁' }}
                                    </button>
                                    <button v-if="hasMoreRiddlesForPlace" @click="loadAnotherRiddle" class="btn-3d btn-3d-blue w-full py-3 text-xs shadow-[0_4px_0_#1344a1]">
                                        💡 Autre énigme pour ce lieu
                                    </button>
                                </div>
                            </template>

                            <!-- Lose state overlay -->
                            <template v-if="decisionState === 'lose'">
                                <div class="text-7xl mb-6">💀</div>
                                <h2 class="text-4xl font-black text-[#ea4335] text-glow-red uppercase italic tracking-tighter mb-2">Échec de l'énigme</h2>
                                <p class="text-sm text-gray-400 font-bold mb-10 max-w-md">Le chrono s'est écoulé ou vous avez soumis une mauvaise réponse. Choisissez votre destin :</p>
                                
                                <div class="flex flex-col gap-4 w-full max-w-xs">
                                    <button v-if="hasMoreRiddlesForPlace" @click="loadAnotherRiddle" class="btn-3d btn-3d-blue w-full py-4 text-sm shadow-[0_5px_0_#1344a1]">
                                        🔄 Autre énigme sur ce lieu
                                    </button>
                                    <button @click="goToNextPlace" class="btn-3d btn-3d-yellow w-full py-3 text-xs shadow-[0_4px_0_#9e6f00] text-black">
                                        Passer au lieu suivant 👉
                                    </button>
                                    <button @click="forfeitSession" class="btn-3d btn-3d-red w-full py-3 text-xs shadow-[0_4px_0_#9e2318]">
                                        Abandonner la partie
                                    </button>
                                </div>
                            </template>

                            <!-- Already Solved state overlay -->
                            <template v-if="decisionState === 'already_solved'">
                                <div class="text-7xl mb-6">⚠️</div>
                                <h2 class="text-4xl font-black text-[#f3a900] text-glow-yellow uppercase italic tracking-tighter mb-2">Énigme Clôturée</h2>
                                <p class="text-sm text-gray-400 font-bold mb-10 max-w-md">{{ alreadySolvedMessage || 'Un coéquipier ou challenger a déjà répondu avec succès.' }}</p>
                                
                                <div class="flex flex-col gap-4 w-full max-w-xs">
                                    <button @click="goToNextPlace" class="btn-3d btn-3d-blue w-full py-4 text-sm shadow-[0_5px_0_#1344a1]">
                                        Passer au lieu suivant 👉
                                    </button>
                                </div>
                            </template>
                        </div>

                        <!-- Paused overlay -->
                        <div v-if="isPaused" class="absolute inset-0 bg-[#0D0E12]/95 backdrop-blur-md z-20 flex flex-col items-center justify-center rounded-3xl">
                            <div class="text-6xl mb-4">⏸️</div>
                            <h2 class="text-3xl font-black uppercase italic tracking-tighter text-[#f3a900] text-glow-yellow mb-8">Jeu en Pause</h2>
                            <button @click="togglePause" class="btn-3d btn-3d-yellow px-8 py-3.5 text-xs text-[#0A0B0E] font-black shadow-[0_5px_0_#9e6f00]">REPRENDRE</button>
                        </div>

                        <!-- Game Header Details -->
                        <div class="flex flex-col sm:flex-row gap-4 justify-between items-stretch sm:items-center mb-8 border-b border-[#26272F] pb-6">
                            <div class="flex flex-col gap-1 text-center sm:text-left">
                                <span class="text-[8px] font-black uppercase tracking-widest text-gray-500">Progression</span>
                                <div class="flex items-center justify-center sm:justify-start gap-2">
                                    <span class="text-sm font-black text-white">
                                        Lieu {{ currentPlaceIndex + 1 }} / {{ totalGamePlacesCount }}
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
                                <button @click="togglePause" 
                                    class="p-2 bg-[#1C1D24] border border-[#26272F] rounded-xl hover:scale-105 transition-all text-[#f3a900]">
                                    <svg v-if="!isPaused" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM7 8a1 1 0 012 0v4a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v4a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                                    <svg v-else class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"></path></svg>
                                </button>
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
                                    <div class="text-2xl mb-1 transition-transform duration-1000 ease-linear" :style="{ transform: `rotate(${compassRotation}deg)` }">
                                        🧭
                                    </div>
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
                                    class="w-full bg-[#0D0E12] border-2 border-[#26272F] focus:border-[#2fc276] focus:ring-0 rounded-2xl p-4.5 text-lg text-center text-white font-black uppercase tracking-widest transition-colors">
                                <button @click="submitGaming" :disabled="!userAnswer" class="btn-3d btn-3d-green w-full py-4 text-sm shadow-[0_5px_0_#1e7d4b]">
                                    SOUMETTRE LA RÉPONSE 🚀
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
</style>
