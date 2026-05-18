<script setup>
import { ref, reactive } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const currentStep = ref(1);
const isLocating = ref(false);

const form = reactive({
    level: 'facile', // facile, intermediaire, difficile
    location_type: 'city', // departement, commune, city, place
    location_id: 1, // hardcoded for demo, normally fetched from API based on type
    riddles_count: 5,
    type: 'solo', // solo, participants, challengers
    challenger_mode: null, // reponse_par_membre, reponse_par_tous
    max_joueurs: 1,
    global_mode: 'mixte', // decouverte, gaming, mixte
    user_lat: null,
    user_lng: null
});

const nextStep = () => {
    if (currentStep.value < 4) {
        currentStep.value++;
    } else {
        startSubmission();
    }
};

const prevStep = () => {
    if (currentStep.value > 1) {
        currentStep.value--;
    }
};

const startSubmission = () => {
    isLocating.value = true;
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                form.user_lat = position.coords.latitude;
                form.user_lng = position.coords.longitude;
                submitForm();
            },
            (error) => {
                console.warn("Erreur Géolocalisation, utilisation de coordonnées par défaut (Cotonou).", error);
                // Fallback Cotonou
                form.user_lat = 6.3650;
                form.user_lng = 2.4183;
                submitForm();
            },
            { enableHighAccuracy: true, timeout: 5000, maximumAge: 0 }
        );
    } else {
        form.user_lat = 6.3650;
        form.user_lng = 2.4183;
        submitForm();
    }
};

const submitForm = () => {
    // If it's solo, max_joueurs is forced to 1 on backend, but let's be clean
    if (form.type === 'solo') form.max_joueurs = 1;
    
    router.post(route('game.create'), form, {
        onFinish: () => { isLocating.value = false; }
    });
};
</script>

<template>
    <AuthenticatedLayout title="Configuration de la Partie">
        <div class="min-h-screen bg-gray-900 text-gray-100 font-sans py-12 px-4">
            <div class="max-w-3xl mx-auto">
                <!-- Header -->
                <div class="text-center mb-10">
                    <h1 class="text-4xl font-black bg-clip-text text-transparent bg-gradient-to-r from-blue-400 to-purple-500 mb-2">
                        Création de Partie
                    </h1>
                    <p class="text-gray-400">Configurez votre prochaine aventure</p>
                </div>

                <!-- Stepper Progress -->
                <div class="mb-12 relative">
                    <div class="overflow-hidden h-2 mb-4 text-xs flex rounded bg-gray-800">
                        <div :style="`width: ${((currentStep - 1) / 3) * 100}%`" class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-gradient-to-r from-blue-500 to-purple-500 transition-all duration-500"></div>
                    </div>
                    <div class="flex justify-between text-xs font-semibold text-gray-500">
                        <span :class="{'text-blue-400': currentStep >= 1}">Lieu & Niveau</span>
                        <span :class="{'text-blue-400': currentStep >= 2}">Multijoueur</span>
                        <span :class="{'text-blue-400': currentStep >= 3}">Mode de Jeu</span>
                        <span :class="{'text-blue-400': currentStep >= 4}">Résumé</span>
                    </div>
                </div>

                <!-- Form Card -->
                <div class="bg-gray-800 rounded-2xl shadow-[0_0_40px_rgba(0,0,0,0.5)] border border-gray-700 p-8 relative overflow-hidden">
                    <!-- Deco elements -->
                    <div class="absolute top-0 right-0 w-64 h-64 bg-blue-500/10 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2 pointer-events-none"></div>
                    
                    <!-- STEP 1: Lieu et Niveau -->
                    <div v-show="currentStep === 1" class="space-y-8 relative z-10 animate-fade-in-up">
                        <h2 class="text-2xl font-bold border-b border-gray-700 pb-2">Où voulez-vous jouer ?</h2>
                        
                        <div class="space-y-4">
                            <label class="block text-sm font-medium text-gray-400">Niveau de Difficulté</label>
                            <div class="grid grid-cols-3 gap-4">
                                <label v-for="lvl in ['facile', 'intermediaire', 'difficile']" :key="lvl" 
                                    class="cursor-pointer relative rounded-xl border-2 transition-all duration-200 p-4 text-center"
                                    :class="form.level === lvl ? 'border-blue-500 bg-blue-500/10' : 'border-gray-700 bg-gray-900 hover:border-gray-500'">
                                    <input type="radio" v-model="form.level" :value="lvl" class="sr-only">
                                    <span class="capitalize font-bold block" :class="form.level === lvl ? 'text-blue-400' : 'text-gray-300'">{{ lvl }}</span>
                                </label>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <label class="block text-sm font-medium text-gray-400">Nombre d'énigmes</label>
                            <input type="range" v-model="form.riddles_count" min="1" max="20" class="w-full accent-purple-500">
                            <div class="text-center text-3xl font-black text-purple-400">{{ form.riddles_count }}</div>
                        </div>
                    </div>

                    <!-- STEP 2: Multijoueur -->
                    <div v-show="currentStep === 2" class="space-y-8 relative z-10 animate-fade-in-up">
                        <h2 class="text-2xl font-bold border-b border-gray-700 pb-2">Avec qui ?</h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- Solo -->
                            <label class="cursor-pointer rounded-xl border-2 p-4 flex flex-col items-center transition-all"
                                :class="form.type === 'solo' ? 'border-green-500 bg-green-500/10' : 'border-gray-700 bg-gray-900'">
                                <input type="radio" v-model="form.type" value="solo" class="sr-only">
                                <span class="text-3xl mb-2">👤</span>
                                <span class="font-bold">Solo</span>
                            </label>
                            
                            <!-- Participants -->
                            <label class="cursor-pointer rounded-xl border-2 p-4 flex flex-col items-center transition-all"
                                :class="form.type === 'participants' ? 'border-blue-500 bg-blue-500/10' : 'border-gray-700 bg-gray-900'">
                                <input type="radio" v-model="form.type" value="participants" class="sr-only">
                                <span class="text-3xl mb-2">🤝</span>
                                <span class="font-bold">Participants</span>
                                <span class="text-xs text-center mt-2 text-gray-400">Coopération. On gagne ou perd ensemble.</span>
                            </label>

                            <!-- Challengers -->
                            <label class="cursor-pointer rounded-xl border-2 p-4 flex flex-col items-center transition-all"
                                :class="form.type === 'challengers' ? 'border-red-500 bg-red-500/10' : 'border-gray-700 bg-gray-900'">
                                <input type="radio" v-model="form.type" value="challengers" class="sr-only">
                                <span class="text-3xl mb-2">⚔️</span>
                                <span class="font-bold">Challengers</span>
                                <span class="text-xs text-center mt-2 text-gray-400">Compétition. Que le meilleur gagne.</span>
                            </label>
                        </div>

                        <div v-if="form.type !== 'solo'" class="space-y-4 animate-fade-in">
                            <label class="block text-sm font-medium text-gray-400">Nombre de Joueurs (Max)</label>
                            <input type="number" v-model="form.max_joueurs" min="2" max="50" class="w-full bg-gray-900 border border-gray-700 rounded-lg p-3 text-white focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <div v-if="form.type === 'challengers'" class="space-y-4 animate-fade-in p-4 bg-gray-900 rounded-lg border border-gray-700">
                            <label class="block text-sm font-medium text-gray-400 mb-2">Mode de Compétition</label>
                            <label class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-800 cursor-pointer">
                                <input type="radio" v-model="form.challenger_mode" value="reponse_par_membre" class="text-red-500 focus:ring-red-500 bg-gray-800 border-gray-600">
                                <div>
                                    <span class="block font-bold">Rapide (Le premier rafle tout)</span>
                                    <span class="text-xs text-gray-400">L'énigme se verrouille dès qu'un joueur trouve la réponse.</span>
                                </div>
                            </label>
                            <label class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-800 cursor-pointer">
                                <input type="radio" v-model="form.challenger_mode" value="reponse_par_tous" class="text-red-500 focus:ring-red-500 bg-gray-800 border-gray-600">
                                <div>
                                    <span class="block font-bold">Complet (Chacun pour soi)</span>
                                    <span class="text-xs text-gray-400">Tout le monde doit répondre à chaque énigme.</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- STEP 3: Mode de Jeu -->
                    <div v-show="currentStep === 3" class="space-y-8 relative z-10 animate-fade-in-up">
                        <h2 class="text-2xl font-bold border-b border-gray-700 pb-2">Comment voulez-vous jouer ?</h2>
                        <p class="text-sm text-gray-400">Choisissez la façon dont vous allez résoudre les énigmes.</p>

                        <div class="grid grid-cols-1 gap-4">
                            <label class="cursor-pointer rounded-xl border-2 p-5 flex items-center gap-4 transition-all"
                                :class="form.global_mode === 'decouverte' ? 'border-yellow-500 bg-yellow-500/10' : 'border-gray-700 bg-gray-900'">
                                <input type="radio" v-model="form.global_mode" value="decouverte" class="sr-only">
                                <div class="text-4xl">🗺️</div>
                                <div>
                                    <span class="block font-bold text-lg text-yellow-400">Découverte & Voyage</span>
                                    <span class="text-sm text-gray-400">Déplacez-vous physiquement sur les lieux pour valider avec votre GPS. Temps chronométré selon votre moyen de transport !</span>
                                </div>
                            </label>

                            <label class="cursor-pointer rounded-xl border-2 p-5 flex items-center gap-4 transition-all"
                                :class="form.global_mode === 'gaming' ? 'border-purple-500 bg-purple-500/10' : 'border-gray-700 bg-gray-900'">
                                <input type="radio" v-model="form.global_mode" value="gaming" class="sr-only">
                                <div class="text-4xl">🎮</div>
                                <div>
                                    <span class="block font-bold text-lg text-purple-400">Pure Gaming</span>
                                    <span class="text-sm text-gray-400">Trouvez le nom du lieu depuis votre canapé. Pas de déplacement physique requis.</span>
                                </div>
                            </label>

                            <label class="cursor-pointer rounded-xl border-2 p-5 flex items-center gap-4 transition-all"
                                :class="form.global_mode === 'mixte' ? 'border-blue-500 bg-blue-500/10' : 'border-gray-700 bg-gray-900'">
                                <input type="radio" v-model="form.global_mode" value="mixte" class="sr-only">
                                <div class="text-4xl">☯️</div>
                                <div>
                                    <span class="block font-bold text-lg text-blue-400">Les Deux (Choix à chaque énigme)</span>
                                    <span class="text-sm text-gray-400">Le jeu vous demandera de choisir votre mode avant de dévoiler chaque énigme.</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- STEP 4: Résumé -->
                    <div v-show="currentStep === 4" class="space-y-8 relative z-10 animate-fade-in-up">
                        <h2 class="text-2xl font-bold border-b border-gray-700 pb-2">Prêt pour l'aventure ?</h2>
                        
                        <div class="bg-gray-900 p-6 rounded-xl border border-gray-700 space-y-4">
                            <div class="flex justify-between border-b border-gray-800 pb-2">
                                <span class="text-gray-400">Niveau</span>
                                <span class="font-bold capitalize text-white">{{ form.level }}</span>
                            </div>
                            <div class="flex justify-between border-b border-gray-800 pb-2">
                                <span class="text-gray-400">Énigmes</span>
                                <span class="font-bold text-white">{{ form.riddles_count }}</span>
                            </div>
                            <div class="flex justify-between border-b border-gray-800 pb-2">
                                <span class="text-gray-400">Type de Partie</span>
                                <span class="font-bold capitalize" :class="{
                                    'text-green-400': form.type === 'solo',
                                    'text-blue-400': form.type === 'participants',
                                    'text-red-400': form.type === 'challengers'
                                }">{{ form.type }} <span v-if="form.type !== 'solo'">({{ form.max_joueurs }} max)</span></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-400">Mode de Résolution</span>
                                <span class="font-bold capitalize text-white">{{ form.global_mode }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Navigation Buttons -->
                    <div class="mt-10 flex justify-between pt-6 border-t border-gray-700 relative z-10">
                        <button v-if="currentStep > 1" @click="prevStep" 
                            class="px-6 py-3 rounded-lg font-bold text-gray-300 hover:text-white hover:bg-gray-700 transition-colors">
                            Retour
                        </button>
                        <div v-else></div> <!-- Spacer -->

                        <button @click="nextStep" :disabled="isLocating"
                            class="px-8 py-3 rounded-lg font-bold text-white shadow-lg transition-all duration-300 transform hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed"
                            :class="currentStep === 4 ? 'bg-gradient-to-r from-green-500 to-emerald-600 hover:shadow-[0_0_15px_rgba(16,185,129,0.5)]' : 'bg-gradient-to-r from-blue-500 to-purple-600 hover:shadow-[0_0_15px_rgba(59,130,246,0.5)]'">
                            <span v-if="isLocating">LOCALISATION... 📍</span>
                            <span v-else>{{ currentStep === 4 ? 'CRÉER LA PARTIE 🚀' : 'Suivant' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.animate-fade-in-up {
    animation: fadeInUp 0.4s ease-out forwards;
}
.animate-fade-in {
    animation: fadeIn 0.3s ease-out forwards;
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

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
</style>
