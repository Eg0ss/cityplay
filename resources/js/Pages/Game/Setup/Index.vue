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
    if (form.type === 'solo') form.max_joueurs = 1;
    
    router.post(route('game.create'), form, {
        onFinish: () => { isLocating.value = false; }
    });
};
</script>

<template>
    <AuthenticatedLayout title="Configuration de la Partie">
        <div class="min-h-screen text-white font-sans py-8 px-4 relative">
            <div class="max-w-3xl mx-auto">
                
                <!-- Header (Arcade Mode Creation Title) -->
                <div class="text-center mb-10">
                    <h1 class="text-4xl font-black tracking-tighter uppercase italic text-white mb-2">
                        CRÉATION DE <span class="text-[#2fc276] text-glow-green">PARTIE</span>
                    </h1>
                    <p class="text-gray-400 font-semibold">Configurez les paramètres de votre prochaine aventure</p>
                </div>

                <!-- Stepper Progress Tracker (Vibrant Neon Line) -->
                <div class="mb-12 relative px-4">
                    <div class="overflow-hidden h-3 mb-4 text-xs flex rounded-full bg-[#1C1D24] border border-[#26272F]">
                        <div :style="`width: ${((currentStep - 1) / 3) * 100}%`" 
                             class="shadow-[0_0_15px_rgba(47,194,118,0.5)] rounded-full bg-[#2fc276] transition-all duration-500 ease-out"></div>
                    </div>
                    <div class="flex justify-between text-[10px] font-black uppercase tracking-wider text-gray-500">
                        <span :class="currentStep >= 1 ? 'text-[#2fc276] text-glow-green' : ''">1. Niveau</span>
                        <span :class="currentStep >= 2 ? 'text-[#2fc276] text-glow-green' : ''">2. Joueurs</span>
                        <span :class="currentStep >= 3 ? 'text-[#2fc276] text-glow-green' : ''">3. Mode</span>
                        <span :class="currentStep >= 4 ? 'text-[#2fc276] text-glow-green' : ''">4. Résumé</span>
                    </div>
                </div>

                <!-- Main Form Card Console -->
                <div class="panel-glass p-5 sm:p-8 border border-[#26272F] relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-64 h-64 bg-[#2fc276]/5 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2 pointer-events-none"></div>
                    
                    <!-- STEP 1: Lieu et Niveau -->
                    <div v-show="currentStep === 1" class="space-y-8 animate-fade-in-up">
                        <h2 class="text-2xl font-black uppercase italic tracking-tighter border-b border-[#26272F] pb-3 mb-6">Où voulez-vous jouer ?</h2>
                        
                        <!-- Difficulty Grid Selections (Tactile Tiles) -->
                        <div class="space-y-4">
                            <label class="block text-xs font-black uppercase tracking-widest text-gray-400">Niveau de Difficulté</label>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <label v-for="lvl in ['facile', 'intermediaire', 'difficile']" :key="lvl" 
                                    class="cursor-pointer relative rounded-2xl border-2 p-5 text-center flex flex-col items-center justify-center transition-all duration-200 bg-[#1C1D24] border-[#26272F]"
                                    :class="form.level === lvl ? 'border-[#2fc276] bg-[#2fc276]/5 shadow-[0_0_15px_rgba(47,194,118,0.15)]' : 'hover:border-gray-500'">
                                    <input type="radio" v-model="form.level" :value="lvl" class="sr-only">
                                    <span class="capitalize font-black text-sm tracking-wider" :class="form.level === lvl ? 'text-[#2fc276]' : 'text-gray-400'">{{ lvl }}</span>
                                </label>
                            </div>
                        </div>

                        <!-- Riddle Count Range Slider -->
                        <div class="space-y-4 pt-4">
                            <label class="block text-xs font-black uppercase tracking-widest text-gray-400">Nombre d'énigmes</label>
                            <input type="range" v-model="form.riddles_count" min="1" max="20" class="w-full accent-[#2fc276] bg-[#1C1D24] h-2 rounded-full cursor-pointer">
                            <div class="text-center text-4xl font-black text-[#2fc276] text-glow-green tabular-nums">{{ form.riddles_count }}</div>
                        </div>
                    </div>

                    <!-- STEP 2: Multijoueur -->
                    <div v-show="currentStep === 2" class="space-y-8 animate-fade-in-up">
                        <h2 class="text-2xl font-black uppercase italic tracking-tighter border-b border-[#26272F] pb-3 mb-6">Avec qui jouer ?</h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- Solo Mode Tile -->
                            <label class="cursor-pointer rounded-2xl border-2 p-5 flex flex-col items-center justify-center text-center transition-all bg-[#1C1D24] border-[#26272F]"
                                :class="form.type === 'solo' ? 'border-[#2fc276] bg-[#2fc276]/5 shadow-[0_0_15px_rgba(47,194,118,0.15)]' : 'hover:border-gray-500'">
                                <input type="radio" v-model="form.type" value="solo" class="sr-only">
                                <span class="text-4xl mb-3">👤</span>
                                <span class="font-black uppercase text-xs tracking-widest" :class="form.type === 'solo' ? 'text-[#2fc276]' : 'text-white'">Solo</span>
                            </label>
                            
                            <!-- Participants Mode Tile -->
                            <label class="cursor-pointer rounded-2xl border-2 p-5 flex flex-col items-center justify-center text-center transition-all bg-[#1C1D24] border-[#26272F]"
                                :class="form.type === 'participants' ? 'border-[#2c72f6] bg-[#2c72f6]/5 shadow-[0_0_15px_rgba(44,114,246,0.15)]' : 'hover:border-gray-500'">
                                <input type="radio" v-model="form.type" value="participants" class="sr-only">
                                <span class="text-4xl mb-3">🤝</span>
                                <span class="font-black uppercase text-xs tracking-widest" :class="form.type === 'participants' ? 'text-[#2c72f6]' : 'text-white'">Coopération</span>
                                <span class="text-[9px] text-center mt-2 text-gray-500 font-bold leading-normal">On résout ensemble</span>
                            </label>

                            <!-- Challengers Mode Tile -->
                            <label class="cursor-pointer rounded-2xl border-2 p-5 flex flex-col items-center justify-center text-center transition-all bg-[#1C1D24] border-[#26272F]"
                                :class="form.type === 'challengers' ? 'border-[#ea4335] bg-[#ea4335]/5 shadow-[0_0_15px_rgba(234,67,53,0.15)]' : 'hover:border-gray-500'">
                                <input type="radio" v-model="form.type" value="challengers" class="sr-only">
                                <span class="text-4xl mb-3">⚔️</span>
                                <span class="font-black uppercase text-xs tracking-widest" :class="form.type === 'challengers' ? 'text-[#ea4335]' : 'text-white'">Challengers</span>
                                <span class="text-[9px] text-center mt-2 text-gray-500 font-bold leading-normal">Chacun pour soi</span>
                            </label>
                        </div>

                        <!-- Co-op Max Players Widget -->
                        <div v-if="form.type !== 'solo'" class="space-y-3 pt-4 animate-fade-in">
                            <label class="block text-xs font-black uppercase tracking-widest text-gray-400">Nombre de Joueurs (Max)</label>
                            <input type="number" v-model="form.max_joueurs" min="2" max="50" class="w-full bg-[#1C1D24] border border-[#26272F] rounded-xl p-3.5 text-white font-black focus:border-[#2fc276] focus:ring-0">
                        </div>

                        <!-- Challengers Settings Widget -->
                        <div v-if="form.type === 'challengers'" class="space-y-4 animate-fade-in p-5 bg-[#14151B] rounded-2xl border border-[#26272F]">
                            <label class="block text-xs font-black uppercase tracking-widest text-[#ea4335] mb-2">Options de Compétition</label>
                            
                            <label class="flex items-center gap-4 p-3.5 rounded-xl hover:bg-[#1C1D24] cursor-pointer transition-colors border border-transparent"
                                   :class="form.challenger_mode === 'reponse_par_membre' ? 'border-red-500/20 bg-red-500/5' : ''">
                                <input type="radio" v-model="form.challenger_mode" value="reponse_par_membre" class="text-[#ea4335] focus:ring-0 bg-[#1C1D24] border-[#26272F]">
                                <div>
                                    <span class="block font-black text-sm text-white">Rapide (Le premier rafle tout)</span>
                                    <span class="text-[10px] text-gray-500 font-bold">L'énigme se verrouille dès qu'un joueur trouve la réponse.</span>
                                </div>
                            </label>
                            
                            <label class="flex items-center gap-4 p-3.5 rounded-xl hover:bg-[#1C1D24] cursor-pointer transition-colors border border-transparent"
                                   :class="form.challenger_mode === 'reponse_par_tous' ? 'border-red-500/20 bg-red-500/5' : ''">
                                <input type="radio" v-model="form.challenger_mode" value="reponse_par_tous" class="text-[#ea4335] focus:ring-0 bg-[#1C1D24] border-[#26272F]">
                                <div>
                                    <span class="block font-black text-sm text-white">Complet (Chacun pour soi)</span>
                                    <span class="text-[10px] text-gray-500 font-bold">Tout le monde doit répondre individuellement.</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- STEP 3: Mode de Jeu -->
                    <div v-show="currentStep === 3" class="space-y-8 animate-fade-in-up">
                        <h2 class="text-2xl font-black uppercase italic tracking-tighter border-b border-[#26272F] pb-3 mb-6">Comment résoudre ?</h2>
                        
                        <div class="grid grid-cols-1 gap-4">
                            <!-- Découverte Mode -->
                            <label class="cursor-pointer rounded-2xl border-2 p-5 flex items-center gap-5 transition-all bg-[#1C1D24] border-[#26272F]"
                                :class="form.global_mode === 'decouverte' ? 'border-[#f3a900] bg-[#f3a900]/5 shadow-[0_0_15px_rgba(243,169,0,0.15)]' : 'hover:border-gray-500'">
                                <input type="radio" v-model="form.global_mode" value="decouverte" class="sr-only">
                                <div class="text-4xl">🗺️</div>
                                <div>
                                    <span class="block font-black text-base" :class="form.global_mode === 'decouverte' ? 'text-[#f3a900]' : 'text-white'">Découverte & Voyage</span>
                                    <span class="text-[10px] text-gray-500 font-bold block mt-1">Marchez vers le lieu réel et validez par coordonnées GPS en extérieur.</span>
                                </div>
                            </label>

                            <!-- Gaming Mode -->
                            <label class="cursor-pointer rounded-2xl border-2 p-5 flex items-center gap-5 transition-all bg-[#1C1D24] border-[#26272F]"
                                :class="form.global_mode === 'gaming' ? 'border-[#2c72f6] bg-[#2c72f6]/5 shadow-[0_0_15px_rgba(44,114,246,0.15)]' : 'hover:border-gray-500'">
                                <input type="radio" v-model="form.global_mode" value="gaming" class="sr-only">
                                <div class="text-4xl">🎮</div>
                                <div>
                                    <span class="block font-black text-base" :class="form.global_mode === 'gaming' ? 'text-[#2c72f6]' : 'text-white'">Pure Gaming (Canapé)</span>
                                    <span class="text-[10px] text-gray-500 font-bold block mt-1">Trouvez le nom exact du lieu géographique depuis chez vous.</span>
                                </div>
                            </label>

                            <!-- Mixte Mode -->
                            <label class="cursor-pointer rounded-2xl border-2 p-5 flex items-center gap-5 transition-all bg-[#1C1D24] border-[#26272F]"
                                :class="form.global_mode === 'mixte' ? 'border-[#2fc276] bg-[#2fc276]/5 shadow-[0_0_15px_rgba(47,194,118,0.15)]' : 'hover:border-gray-500'">
                                <input type="radio" v-model="form.global_mode" value="mixte" class="sr-only">
                                <div class="text-4xl">☯️</div>
                                <div>
                                    <span class="block font-black text-base" :class="form.global_mode === 'mixte' ? 'text-[#2fc276]' : 'text-white'">Les Deux (Choix libre)</span>
                                    <span class="text-[10px] text-gray-500 font-bold block mt-1">Le jeu vous demandera de choisir à chaque énigme selon vos envies.</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- STEP 4: Résumé -->
                    <div v-show="currentStep === 4" class="space-y-8 animate-fade-in-up">
                        <h2 class="text-2xl font-black uppercase italic tracking-tighter border-b border-[#26272F] pb-3 mb-6">Prêt pour l'exploration ?</h2>
                        
                        <div class="bg-[#14151B] p-6 rounded-2xl border border-[#26272F] space-y-4">
                            <div class="flex justify-between border-b border-[#26272F] pb-3">
                                <span class="text-xs font-black uppercase text-gray-500">Niveau de difficulté</span>
                                <span class="font-black text-sm capitalize text-white">{{ form.level }}</span>
                            </div>
                            <div class="flex justify-between border-b border-[#26272F] pb-3">
                                <span class="text-xs font-black uppercase text-gray-500">Nombre d'énigmes</span>
                                <span class="font-black text-sm text-[#2fc276] text-glow-green">{{ form.riddles_count }}</span>
                            </div>
                            <div class="flex justify-between border-b border-[#26272F] pb-3">
                                <span class="text-xs font-black uppercase text-gray-500">Type d'aventure</span>
                                <span class="font-black text-sm capitalize" :class="{
                                    'text-[#2fc276]': form.type === 'solo',
                                    'text-[#2c72f6]': form.type === 'participants',
                                    'text-[#ea4335]': form.type === 'challengers'
                                }">{{ form.type }} <span v-if="form.type !== 'solo'">({{ form.max_joueurs }} max)</span></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-xs font-black uppercase text-gray-500">Mode de Résolution</span>
                                <span class="font-black text-sm capitalize text-white">{{ form.global_mode }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Navigation Action Buttons (Bouncy 3D Controls) -->
                    <div class="mt-10 flex justify-between pt-6 border-t border-[#26272F] relative z-10">
                        <button v-if="currentStep > 1" @click="prevStep" 
                            class="px-6 py-3.5 btn-3d btn-3d-blue text-xs font-black uppercase">
                            Retour
                        </button>
                        <div v-else></div>

                        <button @click="nextStep" :disabled="isLocating"
                            class="px-8 py-3.5 btn-3d btn-3d-green text-xs font-black uppercase"
                            :class="currentStep === 4 ? 'shadow-[0_5px_0_#1e7d4b]' : 'shadow-[0_5px_0_#1e7d4b]'">
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
