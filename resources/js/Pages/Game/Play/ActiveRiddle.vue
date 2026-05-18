<script setup>
import { ref, onMounted, computed, onUnmounted } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useToast } from 'primevue/usetoast';
import Toast from 'primevue/toast';

const props = defineProps({
    session: Object
});

const page = usePage();
const toast = useToast();

// État local du jeu
const currentRiddleIndex = ref(0);
const modeChoisi = ref(null); // Pour le mode 'mixte'
const isPlaying = ref(false);
const timeLeft = ref(0);
const totalTime = ref(0);
const isPaused = ref(false);
const userAnswer = ref('');
const userCoords = ref({ lat: null, lng: null });

const gameRiddles = computed(() => {
    return props.session.game_riddles || props.session.gameRiddles || [];
});

const currentRiddle = computed(() => {
    return gameRiddles.value[currentRiddleIndex.value]?.riddle;
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

// Géolocalisation en temps réel pour le mode découverte
let watchId = null;
onMounted(() => {
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
});

// Calcul de distance à vol d'oiseau (Haversine)
const distanceToPlace = computed(() => {
    if (!userCoords.value.lat || !currentRiddle.value?.place) return null;
    const lat1 = userCoords.value.lat;
    const lon1 = userCoords.value.lng;
    const lat2 = currentRiddle.value.place.lat;
    const lon2 = currentRiddle.value.place.lng;
    
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
            toast.add({ severity: 'error', summary: 'Temps écoulé', detail: 'Vous n\'avez pas résolu l\'énigme à temps !', life: 5000 });
            nextRiddle();
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

const submitDiscovery = () => {
    if (!distanceToPlace.value) {
        toast.add({ severity: 'warn', summary: 'Signal GPS faible', detail: 'Impossible de valider sans votre position GPS.', life: 4000 });
        return;
    }

    const margin = currentRiddle.value?.place?.marge_validation_gps || currentRiddle.value?.place?.rayon_marge || 50;
    const distanceInMeters = distanceToPlace.value * 1000;

    if (distanceInMeters <= margin) {
        toast.add({ severity: 'success', summary: 'Félicitations ! 🎉', detail: 'Vous êtes bien sur le lieu recherché !', life: 5000 });
        nextRiddle();
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

const submitGaming = () => {
    const answer = userAnswer.value.trim().toLowerCase();
    const correctAnswer = currentRiddle.value.reponse.trim().toLowerCase();

    if (answer === correctAnswer) {
        toast.add({ severity: 'success', summary: 'Bonne réponse ! 🎯', detail: `La réponse était bien : ${currentRiddle.value.reponse}`, life: 4000 });
        nextRiddle();
    } else {
        toast.add({ severity: 'error', summary: 'Mauvaise réponse', detail: 'Essayez encore !', life: 3000 });
        userAnswer.value = '';
    }
};

const nextRiddle = () => {
    clearInterval(timerInterval);
    if (currentRiddleIndex.value < gameRiddles.value.length - 1) {
        currentRiddleIndex.value++;
        userAnswer.value = '';
        isPlaying.value = false;
        modeChoisi.value = null;
        
        // Si le mode global n'est pas mixte, relancer automatiquement le mode correct
        const player = props.session.players?.find(p => p.user_id === page.props.auth.user.id);
        if (player && (player.global_mode === 'gaming' || player.global_mode === 'decouverte')) {
            startRiddle(player.global_mode);
        }
    } else {
        toast.add({ severity: 'success', summary: 'Partie terminée ! 🏆', detail: 'Vous avez terminé toutes les énigmes !', life: 6000 });
        setTimeout(() => {
            router.get(route('game.dashboard'));
        }, 3000);
    }
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

            <!-- Header -->
            <header class="relative z-10 flex items-center justify-between p-4 bg-gray-900/80 backdrop-blur border-b border-gray-800">
                <div class="flex items-center gap-4">
                    <div class="text-xs uppercase tracking-widest text-gray-500 font-bold">Énigme</div>
                    <div class="text-xl font-black text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-purple-400">
                        {{ currentRiddleIndex + 1 }} / {{ gameRiddles.length }}
                    </div>
                </div>
                
                <div v-if="isPlaying" class="flex items-center gap-6">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full animate-ping" :class="timeLeft < 30 ? 'bg-red-500' : 'bg-green-500'"></span>
                        <span class="text-3xl font-mono font-black tabular-nums" :class="timeLeft < 30 ? 'text-red-400' : 'text-white'">
                            {{ formatTime(timeLeft) }}
                        </span>
                    </div>
                    <button v-if="modeChoisi === 'decouverte'" @click="togglePause" 
                        class="p-2 bg-gray-800 rounded-lg hover:bg-gray-700 transition-colors border border-gray-700 text-yellow-500 focus:outline-none focus:ring-2 focus:ring-yellow-500">
                        <svg v-if="!isPaused" class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM7 8a1 1 0 012 0v4a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v4a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                        <svg v-else class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"></path></svg>
                    </button>
                </div>
            </header>

            <!-- Main Content -->
            <main class="relative z-10 flex-1 flex flex-col items-center justify-center p-4">
                <div class="max-w-3xl w-full">
                    
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
                    <div v-if="isPlaying && currentRiddle" class="bg-gray-800/80 backdrop-blur-xl p-8 rounded-3xl border border-gray-700 shadow-2xl relative animate-fade-in-up">
                        <div v-if="isPaused" class="absolute inset-0 bg-gray-900/90 backdrop-blur-sm z-20 flex flex-col items-center justify-center rounded-3xl">
                            <div class="text-5xl mb-4">⏸️</div>
                            <h2 class="text-3xl font-bold text-yellow-400 mb-6">Jeu en Pause</h2>
                            <button @click="togglePause" class="px-8 py-4 bg-yellow-500 text-gray-900 font-bold rounded-full hover:bg-yellow-400 transition-colors">REPRENDRE</button>
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
