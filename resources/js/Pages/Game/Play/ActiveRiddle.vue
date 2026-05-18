<script setup>
import { ref, onMounted, computed, onUnmounted, watch } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useToast } from 'primevue/usetoast';
import Toast from 'primevue/toast';
import axios from 'axios';
import { userStatsStore } from '@/store.js';

const props = defineProps({
    session: Object,
    placesWithRiddles: Array
});

const page = usePage();
const toast = useToast();

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
let syncInterval = null;

onMounted(() => {
    // Dans le mode participants, se positionner sur la première énigme non tentée
    if (props.session.type === 'participants') {
        selectFirstUnattemptedRiddle();
        
        // Polling de synchronisation temps réel des participants
        syncInterval = setInterval(() => {
            router.reload({ 
                only: ['session'],
                preserveState: true,
                preserveScroll: true
            });
        }, 4000);
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
    if (watchId) navigator.geolocation.clearWatch(watchId);
    clearInterval(timerInterval);
    if (syncInterval) clearInterval(syncInterval);
});

// Détecter si l'énigme active actuelle (currentRiddle) a été clôturée par un autre joueur
watch(() => props.session.attempts, (newAttempts) => {
    if (props.session.type === 'participants' && newAttempts && currentRiddle.value) {
        const hasAttempt = newAttempts.some(att => 
            att.game_riddle?.riddle_id === currentRiddle.value.id
        );
        
        // Si l'énigme active a été clôturée par un autre joueur et que le joueur local n'a pas encore fini
        if (hasAttempt && !decisionState.value) {
            const attempt = newAttempts.find(att => att.game_riddle?.riddle_id === currentRiddle.value.id);
            if (attempt && attempt.user_id !== page.props.auth.user.id) {
                clearInterval(timerInterval);
                decisionState.value = 'already_solved';
                alreadySolvedMessage.value = `Désolé, cette énigme a déjà été clôturée par ${attempt.user?.name || 'un autre participant'} !`;
                toast.add({ 
                    severity: 'warn', 
                    summary: 'Énigme déjà résolue ⚠️', 
                    detail: `Cette énigme a été clôturée par ${attempt.user?.name || 'un autre joueur'}.`, 
                    life: 5000 
                });
            }
        }
    }
}, { deep: true });

// Calcul de distance à vol d'oiseau (Haversine)
const distanceToPlace = computed(() => {
    if (!userCoords.value.lat || !currentPlace.value) return null;
    const lat1 = userCoords.value.lat;
    const lon1 = userCoords.value.lng;
    const lat2 = currentPlace.value.lat;
    const lon2 = currentPlace.value.lng;
    
    const R = 6371; // km
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
              Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
              Math.sin(dLon/2) * Math.sin(dLon/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    return R * c; // Distance en kilomètres
});

const recommendedTransport = computed(() => {
    const dist = distanceToPlace.value;
    if (dist === null) return "🚗 Voiture / Moto";
    if (dist < 0.5) return "🚶 Marche (Très proche)";
    if (dist < 2) return "🚲 Vélo / Moto";
    return "🚗 Voiture / Transport commun";
});

const startRiddle = (mode) => {
    modeChoisi.value = mode;
    isPlaying.value = true;
    decisionState.value = null;
    isPaused.value = false;
    userAnswer.value = '';
    
    if (mode === 'decouverte') {
        timeLeft.value = 1200; // 20 min découverte
        totalTime.value = 1200;
    } else {
        // Mode Gaming : décompte dynamique selon la difficulté
        const level = props.session.level;
        if (level === 'facile') {
            timeLeft.value = 25;
            totalTime.value = 25;
        } else if (level === 'intermediaire') {
            timeLeft.value = 20;
            totalTime.value = 20;
        } else {
            timeLeft.value = 15;
            totalTime.value = 15;
        }
    }
    startTimer();
};

let timerInterval;
const startTimer = () => {
    clearInterval(timerInterval);
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
        
        // Si le mode global n'est pas mixte, relancer automatiquement le mode correct
        const player = props.session.players?.find(p => p.user_id === page.props.auth.user.id);
        if (player && (player.global_mode === 'gaming' || player.global_mode === 'decouverte')) {
            startRiddle(player.global_mode);
        }
        
        // Si toutes les énigmes ont été complétées/clôturées
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
            
            // Si le mode global n'est pas mixte, relancer automatiquement le mode correct
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
        
        // Si le mode global n'est pas mixte, relancer automatiquement le mode correct
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
    toast.add({ severity: 'info', summary: 'Session abandonnée', detail: 'Retour au tableau de bord...', life: 3000 });
    setTimeout(() => {
        router.get(route('game.dashboard'));
    }, 2000);
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
        <div class="min-h-screen bg-gray-950 text-white font-sans flex flex-col relative overflow-hidden transition-colors duration-500">
            <!-- Background Elements -->
            <div class="absolute inset-0 z-0">
                <div class="absolute top-0 left-1/4 w-96 h-96 bg-blue-900/20 rounded-full blur-[100px]"></div>
                <div class="absolute bottom-0 right-1/4 w-96 h-96 bg-purple-900/20 rounded-full blur-[100px]"></div>
            </div>

            <!-- Main Content -->
            <main class="relative z-10 flex-1 flex flex-col items-center justify-center p-4">
                <div class="max-w-3xl w-full">
                    
                    <!-- Tableau des scores live (Mode Participants / Challengers) -->
                    <div v-if="session.type !== 'solo'" class="mb-6 bg-gray-800/60 border border-gray-700/50 backdrop-blur-xl p-4 rounded-2xl shadow-xl animate-fade-in-up">
                        <div class="flex items-center justify-between mb-3 border-b border-gray-700/50 pb-2">
                            <div class="flex items-center gap-2">
                                <span class="text-xl">🏆</span>
                                <h3 class="text-sm font-black uppercase tracking-wider text-gray-300">Classement de la Session</h3>
                            </div>
                            <span class="text-[10px] bg-blue-900/40 text-blue-300 px-2 py-0.5 rounded border border-blue-500/20 font-bold uppercase tracking-widest animate-pulse">En Direct</span>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <div v-for="(p, idx) in sessionLeaderboard" :key="p.id" 
                                class="flex items-center gap-3 bg-gray-900/40 p-2 rounded-xl border border-gray-800"
                                :class="p.user_id === page.props.auth.user.id ? 'border-yellow-500/30 bg-yellow-500/5' : ''">
                                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center font-bold text-xs">
                                    {{ idx + 1 }}
                                </div>
                                <div class="overflow-hidden">
                                    <p class="text-xs font-black truncate" :class="p.user_id === page.props.auth.user.id ? 'text-yellow-400' : 'text-white'">{{ p.name }}</p>
                                    <p class="text-[10px] font-bold text-gray-400">{{ p.points }} XP</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sélection du mode (Si mixte et pas encore choisi) -->
                    <div v-if="!isPlaying" class="text-center animate-fade-in-up">
                        <h2 class="text-3xl font-bold mb-8">Choisissez votre mode pour cette énigme</h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <button @click="startRiddle('decouverte')" class="bg-gray-800 p-8 rounded-2xl border-2 border-yellow-500/50 hover:border-yellow-500 hover:bg-yellow-500/10 transition-all group relative overflow-hidden">
                                <div class="text-6xl mb-4 transform group-hover:scale-110 transition-transform">🗺️</div>
                                <h3 class="text-xl font-bold text-yellow-400 mb-2">Découverte</h3>
                                <p class="text-sm text-gray-400">Je veux me rendre sur place.</p>
                            </button>
                            <button @click="startRiddle('gaming')" class="bg-gray-800 p-8 rounded-2xl border-2 border-purple-500/50 hover:border-purple-500 hover:bg-purple-500/10 transition-all group relative overflow-hidden">
                                <div class="text-6xl mb-4 transform group-hover:scale-110 transition-transform">🎮</div>
                                <h3 class="text-xl font-bold text-purple-400 mb-2">Gaming</h3>
                                <p class="text-sm text-gray-400">Je veux répondre depuis ici.</p>
                            </button>
                        </div>
                    </div>

                    <!-- L'Énigme -->
                    <div v-if="isPlaying && currentRiddle" class="bg-gray-800/80 backdrop-blur-xl p-8 rounded-3xl border border-gray-700 shadow-2xl relative overflow-hidden animate-fade-in-up">
                        
                        <!-- Modal Intermédiaire en cas de Victoire / Défaite / Déjà Résolue -->
                        <div v-if="decisionState" class="absolute inset-0 bg-gray-900/95 backdrop-blur-md z-30 flex flex-col items-center justify-center p-8 text-center">
                            <template v-if="decisionState === 'win'">
                                <div class="text-6xl mb-4 animate-bounce">🎉</div>
                                <h2 class="text-3xl font-black text-green-400 mb-2">Énigme Résolue !</h2>
                                <p class="text-gray-300 mb-8 max-w-md">Vous avez brillamment résolu cette énigme et gagné <span class="text-yellow-400 font-bold">{{ riddlePoints }} points</span> ! Que souhaitez-vous faire ?</p>
                                
                                <div class="flex flex-col gap-4 w-full max-w-sm">
                                    <button @click="goToNextPlace" class="py-4 bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-400 hover:to-emerald-500 rounded-xl font-bold text-lg shadow-lg transition-transform transform hover:-translate-y-0.5">
                                        {{ hasNextPlace ? '👉 Passer au lieu suivant' : '🏆 Terminer l\'aventure !' }}
                                    </button>
                                    <button v-if="hasMoreRiddlesForPlace" @click="loadAnotherRiddle" class="py-3 bg-gray-800 hover:bg-gray-700 border border-gray-600 rounded-xl text-sm font-semibold transition-all text-purple-400">
                                        💡 Autre énigme pour ce même lieu (Pour plus comprendre)
                                    </button>
                                </div>
                            </template>

                            <template v-if="decisionState === 'lose'">
                                <div class="text-6xl mb-4">😢</div>
                                <h2 class="text-3xl font-black text-red-400 mb-2">Échec de l'énigme</h2>
                                <p class="text-gray-300 mb-8 max-w-md">Le temps est écoulé ou vous avez échoué. Choisissez votre destin :</p>
                                
                                <div class="flex flex-col gap-4 w-full max-w-sm">
                                    <button v-if="hasMoreRiddlesForPlace" @click="loadAnotherRiddle" class="py-4 bg-gradient-to-r from-purple-500 to-indigo-600 hover:from-purple-400 hover:to-indigo-500 rounded-xl font-bold text-lg shadow-lg transition-transform transform hover:-translate-y-0.5">
                                        🔄 Autre énigme pour le même niveau (Même lieu)
                                    </button>
                                    <button @click="goToNextPlace" class="py-3 bg-gray-800 hover:bg-gray-700 border border-gray-600 rounded-xl font-semibold transition-all">
                                        👉 Passer au lieu suivant
                                    </button>
                                    <button @click="forfeitSession" class="py-3 bg-red-950/40 hover:bg-red-950 border border-red-800/30 text-red-400 rounded-xl font-semibold transition-all">
                                        💀 Perdre la session carrément
                                    </button>
                                </div>
                            </template>

                            <template v-if="decisionState === 'already_solved'">
                                <div class="text-6xl mb-4 animate-bounce">⚠️</div>
                                <h2 class="text-3xl font-black text-yellow-400 mb-2">Énigme Déjà Clôturée</h2>
                                <p class="text-gray-300 mb-8 max-w-md">{{ alreadySolvedMessage || 'Désolé, un autre participant a déjà clôturé cette énigme !' }}</p>
                                
                                <div class="flex flex-col gap-4 w-full max-w-sm">
                                    <button @click="goToNextPlace" class="py-4 bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-400 hover:to-indigo-500 rounded-xl font-bold text-lg shadow-lg transition-transform transform hover:-translate-y-0.5">
                                        👉 Passer à l'énigme / lieu suivant
                                    </button>
                                </div>
                            </template>
                        </div>

                        <div v-if="isPaused" class="absolute inset-0 bg-gray-900/90 backdrop-blur-sm z-20 flex flex-col items-center justify-center rounded-3xl">
                            <div class="text-5xl mb-4">⏸️</div>
                            <h2 class="text-3xl font-bold text-yellow-400 mb-6">Jeu en Pause</h2>
                            <button @click="togglePause" class="px-8 py-4 bg-yellow-500 text-gray-900 font-bold rounded-full hover:bg-yellow-400 transition-colors">REPRENDRE</button>
                        </div>

                        <!-- Infos d'en-tête de la carte -->
                        <div class="flex justify-between items-center mb-6 border-b border-gray-700/50 pb-4">
                            <div class="flex flex-col gap-1">
                                <span class="text-xs uppercase tracking-widest text-gray-500 font-bold">Progression</span>
                                <div class="flex items-center gap-2">
                                    <span class="text-lg font-black text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-purple-400">
                                        Lieu {{ currentPlaceIndex + 1 }} / {{ totalGamePlacesCount }}
                                    </span>
                                    <span class="text-xs bg-purple-900/40 text-purple-300 px-2 py-0.5 rounded border border-purple-500/20 font-bold uppercase tracking-wider">
                                        {{ currentPlace?.nom }}
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Chrono Découverte -->
                            <div v-if="modeChoisi === 'decouverte'" class="flex items-center gap-4">
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full animate-ping" :class="timeLeft < 30 ? 'bg-red-500' : 'bg-green-500'"></span>
                                    <span class="text-xl font-mono font-black tabular-nums" :class="timeLeft < 30 ? 'text-red-400' : 'text-white'">
                                        {{ formatTime(timeLeft) }}
                                    </span>
                                </div>
                                <button @click="togglePause" 
                                    class="p-1.5 bg-gray-800 rounded-lg hover:bg-gray-700 transition-colors border border-gray-700 text-yellow-500 focus:outline-none">
                                    <svg v-if="!isPaused" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM7 8a1 1 0 012 0v4a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v4a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                                    <svg v-else class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"></path></svg>
                                </button>
                            </div>
                        </div>

                        <!-- Boussole de décompte pour le mode Gaming -->
                        <div v-if="modeChoisi === 'gaming'" class="flex flex-col items-center justify-center mb-8">
                            <div class="relative w-36 h-36 flex items-center justify-center bg-gray-900/60 rounded-full border-4 border-purple-500/20 shadow-[0_0_20px_rgba(139,92,246,0.15)] backdrop-blur">
                                <!-- SVG Ring -->
                                <svg class="absolute inset-0 w-full h-full transform -rotate-90">
                                    <circle
                                        cx="72"
                                        cy="72"
                                        r="64"
                                        stroke="rgba(139, 92, 246, 0.1)"
                                        stroke-width="8"
                                        fill="transparent"
                                    />
                                    <circle
                                        cx="72"
                                        cy="72"
                                        r="64"
                                        stroke="url(#purpleGradient)"
                                        stroke-width="8"
                                        fill="transparent"
                                        :stroke-dasharray="strokeDasharray"
                                        :stroke-dashoffset="strokeDashoffset"
                                        stroke-linecap="round"
                                        class="transition-all duration-1000 ease-linear"
                                    />
                                    <defs>
                                        <linearGradient id="purpleGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                            <stop offset="0%" stop-color="#a78bfa" />
                                            <stop offset="100%" stop-color="#7c3aed" />
                                        </linearGradient>
                                    </defs>
                                </svg>
                                
                                <!-- Compass Details -->
                                <div class="text-center z-10 flex flex-col items-center">
                                    <div class="text-2xl mb-1 transition-transform duration-1000 ease-linear" :style="{ transform: `rotate(${compassRotation}deg)` }">
                                        🧭
                                    </div>
                                    <div class="text-3xl font-black font-mono tabular-nums text-purple-400">
                                        {{ timeLeft }}s
                                    </div>
                                    <div class="text-[10px] uppercase tracking-widest text-gray-500 font-bold">
                                        Temps
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="text-center mb-10 px-4">
                            <h2 class="text-2xl sm:text-3xl text-blue-300 font-bold leading-relaxed">
                                "{{ currentRiddle.description }}"
                            </h2>
                        </div>

                        <!-- Actions Découverte -->
                        <div v-if="modeChoisi === 'decouverte'" class="space-y-6">
                            <div class="bg-blue-900/20 border border-blue-500/30 p-4 rounded-xl text-center">
                                <p class="text-sm text-blue-300 mb-2">Moyen de transport conseillé :</p>
                                <div class="flex justify-center gap-4">
                                    <span class="px-3 py-1 bg-gray-800 rounded-full border border-gray-600 text-xs font-bold text-white">
                                        {{ recommendedTransport }}
                                    </span>
                                </div>
                            </div>
                            <button @click="submitDiscovery" class="w-full py-5 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 rounded-2xl font-black text-xl shadow-[0_0_30px_rgba(59,130,246,0.3)] hover:shadow-[0_0_40px_rgba(139,92,246,0.5)] transition-all transform hover:-translate-y-1">
                                📍 JE SUIS SUR PLACE (VALIDER GPS)
                            </button>
                        </div>

                        <!-- Actions Gaming -->
                        <div v-if="modeChoisi === 'gaming'" class="space-y-6">
                            <div v-if="session.level === 'difficile'">
                                <label class="block text-sm font-medium text-gray-400 mb-2">Quel est le nom de ce lieu ?</label>
                                <input v-model="userAnswer" type="text" placeholder="Entrez la réponse exacte..." 
                                    class="w-full bg-gray-900 border-2 border-gray-700 focus:border-purple-500 focus:ring-purple-500 rounded-xl p-4 text-xl text-center text-white transition-colors">
                                <button @click="submitGaming" :disabled="!userAnswer" class="mt-4 w-full py-4 bg-purple-600 hover:bg-purple-500 disabled:bg-gray-700 disabled:text-gray-500 rounded-xl font-bold text-lg transition-colors">
                                    SOUMETTRE
                                </button>
                            </div>
                            <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <button v-for="option in parsedMcqOptions" :key="option"
                                    @click="submitQcm(option)"
                                    class="p-4 bg-gray-900 border border-gray-700 hover:border-purple-500 rounded-xl transition-all font-semibold">
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
