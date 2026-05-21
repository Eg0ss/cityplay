<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { 
    ChevronLeft, 
    ChevronRight, 
    Map as MapIcon, 
    AlertTriangle, 
    CheckCircle2, 
    Landmark, 
    User, 
    Users, 
    Swords, 
    Gamepad2, 
    Layers,
    MapPin,
    Rocket,
    Clock,
    Zap,
    Shield
} from 'lucide-vue-next';

const props = defineProps({
    cities: { type: Array, default: () => [] }
});

const currentStep = ref(1);
const isLocating = ref(false);

const form = reactive({
    level: 'facile', // facile, intermediaire, difficile
    location_type: 'city',
    location_id: null,
    riddles_count: 5,
    type: 'solo', // solo, participants, challengers
    challenger_mode: null,
    max_joueurs: 1,
    global_mode: 'mixte',
    user_lat: null,
    user_lng: null
});

// Set default city on mount
onMounted(() => {
    if (props.cities && props.cities.length > 0) {
        form.location_id = props.cities[0].id;
    }
});

const selectedCity = computed(() => {
    return props.cities.find(c => c.id === form.location_id);
});

const isCityWithoutRiddles = computed(() => {
    return selectedCity.value && selectedCity.value.riddles_count === 0;
});

// Nombre maximum d'énigmes disponibles pour le niveau sélectionné
const maxRiddlesForLevel = computed(() => {
    if (!selectedCity.value || !selectedCity.value.riddles_by_level) {
        return 1;
    }
    const count = selectedCity.value.riddles_by_level[form.level] || 0;
    return Math.max(1, count);
});

// Ajuster automatiquement riddles_count si dépasse le max du niveau
const adjustRiddlesCount = () => {
    if (form.riddles_count > maxRiddlesForLevel.value) {
        form.riddles_count = maxRiddlesForLevel.value;
    }
};

const nextStep = () => {
    if (isCityWithoutRiddles.value && currentStep.value === 1) return;
    
    if (currentStep.value < 5) {
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
                // Silencieux si l'utilisateur a refusé, sinon on prévient gentiment
                if (error.code !== 1) {
                    console.warn("Erreur Géolocalisation, utilisation de coordonnées par défaut (Cotonou).", error);
                }
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
                    <span class="inline-block text-[#87d74e] text-glow-green font-black text-xs tracking-[0.4em] uppercase italic mb-3">Nouvel itinéraire</span>
                    <h1 class="text-4xl lg:text-7xl font-black tracking-tighter uppercase italic leading-none text-white mb-6 flex items-center justify-center gap-4">
                        CRÉATION DE <span class="text-[#87d74e] text-glow-green">PARTIE</span>
                        <MapIcon :size="48" class="text-[#87d74e]" />
                    </h1>
                    <p class="text-gray-400 font-semibold text-sm">Configurez les paramètres de votre prochaine aventure</p>
                </div>

                <!-- Stepper Progress Tracker (Vibrant Neon Line - 5 Steps) -->
                <div class="mb-12 relative px-4">
                    <div class="overflow-hidden h-3 mb-4 text-xs flex rounded-full bg-[#10101c] border border-[#2a245c]">
                        <div :style="`width: ${((currentStep - 1) / 4) * 100}%`" 
                             class="shadow-[0_0_15px_rgba(135,215,78,0.5)] rounded-full bg-[#87d74e] transition-all duration-500 ease-out"></div>
                    </div>
                    <div class="flex justify-between text-[8px] sm:text-[10px] font-black uppercase tracking-wider text-gray-500">
                        <span :class="currentStep >= 1 ? 'text-[#87d74e] text-glow-green' : ''">1. Ville</span>
                        <span :class="currentStep >= 2 ? 'text-[#87d74e] text-glow-green' : ''">2. Niveau</span>
                        <span :class="currentStep >= 3 ? 'text-[#87d74e] text-glow-green' : ''">3. Joueurs</span>
                        <span :class="currentStep >= 4 ? 'text-[#87d74e] text-glow-green' : ''">4. Mode</span>
                        <span :class="currentStep >= 5 ? 'text-[#87d74e] text-glow-green' : ''">5. Résumé</span>
                    </div>
                </div>

                <!-- Backend Errors (Neon Red Warning Bar) -->
                <div v-if="$page.props.errors.error || $page.props.flash?.error" class="mb-6 bg-red-500/10 border border-red-500/30 p-5 rounded-3xl text-red-500 text-xs font-black uppercase tracking-widest animate-fade-in flex items-center gap-3">
                    <AlertTriangle :size="18" />
                    <span>{{ $page.props.errors.error || $page.props.flash?.error }}</span>
                </div>

                <!-- Main Form Card Console -->
                <div class="panel-glass p-6 sm:p-8 border border-[#2a245c] relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-64 h-64 bg-[#87d74e]/5 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2 pointer-events-none"></div>
                    
                    <!-- STEP 1: Choisir une Ville (First Choice Screen) -->
                    <div v-show="currentStep === 1" class="space-y-8 animate-fade-in-up">
                        <div class="space-y-2 border-b border-[#2a245c] pb-4 mb-6">
                            <h2 class="text-2xl font-black uppercase italic tracking-tighter">Dans quelle ville voulez-vous jouer ?</h2>
                            <p class="text-gray-400 text-xs font-medium">Sélectionnez une zone d'exploration active au Bénin.</p>
                        </div>
                        
                        <!-- City interactive card grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <label v-for="city in cities" :key="city.id" 
                                   class="cursor-pointer relative rounded-2xl border-2 p-5 flex flex-col justify-between transition-all duration-200 bg-[#10101c] border-[#2a245c] min-h-[120px] hover-lift"
                                   :class="form.location_id === city.id ? 'border-[#87d74e] bg-[#87d74e]/5 shadow-[0_0_15px_rgba(135,215,78,0.15)]' : 'hover:border-gray-500'">
                                <input type="radio" v-model="form.location_id" :value="city.id" class="sr-only">
                                
                                <div class="flex items-center gap-3">
                                    <CheckCircle2 v-if="form.location_id === city.id" class="w-5 h-5 text-[#87d74e]" />
                                    <div class="space-y-1">
                                        <span class="text-[8px] font-black uppercase tracking-widest text-gray-500 block">{{ city.departement }}</span>
                                        <span class="font-black text-lg uppercase tracking-tight" :class="form.location_id === city.id ? 'text-[#87d74e] text-glow-green' : 'text-white'">{{ city.name }}</span>
                                    </div>
                                </div>

                                <div class="mt-4 flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="h-10 w-10 bg-white/5 rounded-xl flex items-center justify-center border border-white/5 transition-all">
                                            <Landmark class="w-5 h-5 text-[#87d74e]" />
                                        </div>
                                        <span class="text-[9px] font-black uppercase tracking-widest py-1 px-3 rounded-full"
                                              :class="city.riddles_count > 0 ? 'bg-[#87d74e]/10 text-[#87d74e]' : 'bg-red-500/10 text-red-500'">
                                            {{ city.riddles_count }} {{ city.riddles_count > 1 ? 'énigmes' : 'énigme' }}
                                        </span>
                                    </div>
                                </div>
                            </label>
                        </div>

                        <!-- Warnings if chosen city has 0 riddles (Custom requested alert block) -->
                        <div v-if="isCityWithoutRiddles" class="p-6 rounded-2xl border-2 border-dashed border-red-500/30 bg-red-500/5 text-center space-y-3 animate-fade-in">
                            <Landmark class="w-12 h-12 mx-auto text-red-500" />
                            <h4 class="text-sm font-black uppercase text-red-500 tracking-wider">Énigmes inexistantes pour {{ selectedCity?.name }}</h4>
                            <p class="text-xs font-bold text-gray-400 leading-relaxed max-w-md mx-auto">
                                Il n'y a pas encore d'énigmes enregistrées pour cette ville. Soyez sans crainte, la mairie se hâtera de remplir des énigmes palpitantes pour cette ville très bientôt !
                            </p>
                        </div>
                    </div>

                    <!-- STEP 2: Niveau & Difficulté -->
                    <div v-show="currentStep === 2" class="space-y-8 animate-fade-in-up">
                        <div class="space-y-2 border-b border-[#2a245c] pb-4 mb-6">
                            <h2 class="text-2xl font-black uppercase italic tracking-tighter">Paramètres de défi</h2>
                            <p class="text-gray-400 text-xs font-medium">Ajustez la complexité et la quantité de vos énigmes.</p>
                        </div>
                        
                        <!-- Difficulty Grid Selections -->
                        <div class="space-y-4">
                            <label class="block text-[10px] font-black uppercase tracking-widest text-gray-400">Niveau de Difficulté</label>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <label v-for="lvl in ['facile', 'intermediaire', 'difficile']" :key="lvl" 
                                    class="cursor-pointer relative rounded-2xl border-2 p-5 text-center flex flex-col items-center justify-center transition-all duration-200 bg-[#10101c] border-[#2a245c]"
                                    :class="form.level === lvl ? 'border-[#87d74e] bg-[#87d74e]/5 shadow-[0_0_15px_rgba(135,215,78,0.15)]' : 'hover:border-gray-500'"
                                    @click="form.level = lvl; adjustRiddlesCount();">
                                    <input type="radio" v-model="form.level" :value="lvl" class="sr-only">
                                    <span class="capitalize font-black text-sm tracking-wider" :class="form.level === lvl ? 'text-[#87d74e]' : 'text-gray-400'">{{ lvl }}</span>
                                    <span class="text-[8px] font-bold text-gray-500 mt-2" v-if="selectedCity && selectedCity.riddles_by_level">
                                        {{ selectedCity.riddles_by_level[lvl] || 0 }} dispo
                                    </span>
                                </label>
                            </div>
                        </div>

                        <!-- Riddle Count Range Slider -->
                        <div class="space-y-4 pt-4">
                            <div class="flex justify-between items-center">
                                <label class="block text-[10px] font-black uppercase tracking-widest text-gray-400">Nombre d'énigmes</label>
                                <span class="text-[9px] font-bold text-gray-500" v-if="selectedCity">
                                    Max pour niveau {{ form.level }} : {{ maxRiddlesForLevel }}
                                </span>
                            </div>
                            <input type="range" v-model="form.riddles_count" min="1" :max="maxRiddlesForLevel" class="w-full accent-[#87d74e] bg-[#10101c] h-2 rounded-full cursor-pointer">
                            <div class="text-center text-4xl font-black text-[#87d74e] text-glow-green tabular-nums">{{ form.riddles_count }}</div>
                        </div>
                    </div>

                    <!-- STEP 3: Multijoueur -->
                    <div v-show="currentStep === 3" class="space-y-8 animate-fade-in-up">
                        <div class="space-y-2 border-b border-[#2a245c] pb-4 mb-6">
                            <h2 class="text-2xl font-black uppercase italic tracking-tighter">Avec qui jouer ?</h2>
                            <p class="text-gray-400 text-xs font-medium">Choisissez entre une quête solo ou une partie partagée.</p>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <label v-for="mode in [
                                { id: 'solo', label: 'Solo', icon: User, color: 'text-blue-400' },
                                { id: 'participants', label: 'Coop', icon: Users, color: 'text-[#87d74e]' },
                                { id: 'challengers', label: 'Versus', icon: Swords, color: 'text-red-400' }
                            ]" :key="mode.id"
                                class="relative flex flex-col items-center justify-center p-8 rounded-3xl border-2 transition-all cursor-pointer group"
                                :class="form.type === mode.id ? 'bg-[#87d74e]/10 border-[#87d74e] shadow-[0_0_20px_rgba(135,215,78,0.2)]' : 'bg-[#10101c] border-white/5 hover:border-white/10'">
                                
                                <input type="radio" v-model="form.type" :value="mode.id" class="sr-only">
                                <component :is="mode.icon" :size="48" class="mb-4" :class="form.type === mode.id ? 'text-[#87d74e]' : 'text-gray-500'" />
                                <span class="text-xs font-black uppercase tracking-[0.2em]" :class="form.type === mode.id ? 'text-white' : 'text-gray-500'">{{ mode.label }}</span>
                            </label>
                        </div>

                        <!-- Co-op Max Players Widget -->
                        <div v-if="form.type !== 'solo'" class="space-y-3 pt-4 animate-fade-in">
                            <label class="block text-[10px] font-black uppercase tracking-widest text-gray-400">Nombre de Joueurs (Max)</label>
                            <input type="number" v-model="form.max_joueurs" min="2" max="50" class="w-full bg-[#10101c] border border-[#2a245c] rounded-2xl p-4.5 text-white font-black focus:ring-1 focus:ring-[#87d74e] focus:border-[#87d74e] focus:ring-0">
                        </div>

                        <!-- Challengers Settings Widget -->
                        <div v-if="form.type === 'challengers'" class="space-y-4 animate-fade-in p-5 bg-[#10101c] rounded-2xl border border-[#2a245c]">
                            <label class="block text-[10px] font-black uppercase tracking-widest text-[#7751de] mb-2">Options de Compétition</label>
                            
                            <label class="flex items-center gap-4 p-3.5 rounded-xl hover:bg-[#1c183a] cursor-pointer transition-colors border border-transparent"
                                   :class="form.challenger_mode === 'reponse_par_membre' ? 'border-[#7751de]/20 bg-[#7751de]/5' : ''">
                                <input type="radio" v-model="form.challenger_mode" value="reponse_par_membre" class="text-[#7751de] focus:ring-0 bg-[#10101c] border-[#2a245c]">
                                <div>
                                    <span class="block font-black text-sm text-white">Rapide (Le premier rafle tout)</span>
                                    <span class="text-[10px] text-gray-500 font-bold">L'énigme se verrouille dès qu'un joueur trouve la réponse.</span>
                                </div>
                            </label>
                            
                            <label class="flex items-center gap-4 p-3.5 rounded-xl hover:bg-[#1c183a] cursor-pointer transition-colors border border-transparent"
                                   :class="form.challenger_mode === 'reponse_par_tous' ? 'border-[#7751de]/20 bg-[#7751de]/5' : ''">
                                <input type="radio" v-model="form.challenger_mode" value="reponse_par_tous" class="text-[#7751de] focus:ring-0 bg-[#10101c] border-[#2a245c]">
                                <div>
                                    <span class="block font-black text-sm text-white">Complet (Chacun pour soi)</span>
                                    <span class="text-[10px] text-gray-500 font-bold">Tout le monde doit répondre individuellement.</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- STEP 4: Mode de Jeu -->
                    <div v-show="currentStep === 4" class="space-y-8 animate-fade-in-up">
                        <div class="space-y-2 border-b border-[#2a245c] pb-4 mb-6">
                            <h2 class="text-2xl font-black uppercase italic tracking-tighter">Comment résoudre ?</h2>
                            <p class="text-gray-400 text-xs font-medium">Déterminez le protocole de localisation physique.</p>
                        </div>
                        
                        <div class="grid grid-cols-1 gap-4">
                            <!-- Découverte Mode -->
                            <label class="cursor-pointer rounded-2xl border-2 p-5 flex items-center gap-5 transition-all bg-[#10101c] border-[#2a245c]"
                                :class="form.global_mode === 'decouverte' ? 'border-[#ffc628] bg-[#ffc628]/5 shadow-[0_0_15px_rgba(255,198,40,0.15)]' : 'hover:border-gray-500'">
                                <input type="radio" v-model="form.global_mode" value="decouverte" class="sr-only">
                                <MapIcon :size="32" class="text-[#ffc628]" />
                                <div>
                                    <span class="block font-black text-base" :class="form.global_mode === 'decouverte' ? 'text-[#ffc628]' : 'text-white'">Découverte & Voyage</span>
                                    <span class="text-[10px] text-gray-500 font-bold block mt-1">Marchez vers le lieu réel et validez par coordonnées GPS en extérieur.</span>
                                </div>
                            </label>

                            <!-- Gaming Mode -->
                            <label class="cursor-pointer rounded-2xl border-2 p-5 flex items-center gap-5 transition-all bg-[#10101c] border-[#2a245c]"
                                :class="form.global_mode === 'gaming' ? 'border-[#4769b0] bg-[#4769b0]/5 shadow-[0_0_15px_rgba(71,105,176,0.15)]' : 'hover:border-gray-500'">
                                <input type="radio" v-model="form.global_mode" value="gaming" class="sr-only">
                                <Gamepad2 :size="32" class="text-[#4769b0]" />
                                <div>
                                    <span class="block font-black text-base" :class="form.global_mode === 'gaming' ? 'text-[#4769b0]' : 'text-white'">Pure Gaming (Canapé)</span>
                                    <span class="text-[10px] text-gray-500 font-bold block mt-1">Trouvez le nom exact du lieu géographique depuis chez vous.</span>
                                </div>
                            </label>

                            <!-- Mixte Mode -->
                            <label class="cursor-pointer rounded-2xl border-2 p-5 flex items-center gap-5 transition-all bg-[#10101c] border-[#2a245c]"
                                :class="form.global_mode === 'mixte' ? 'border-[#87d74e] bg-[#87d74e]/5 shadow-[0_0_15px_rgba(135,215,78,0.15)]' : 'hover:border-gray-500'">
                                <input type="radio" v-model="form.global_mode" value="mixte" class="sr-only">
                                <Layers :size="32" class="text-[#87d74e]" />
                                <div>
                                    <span class="block font-black text-base" :class="form.global_mode === 'mixte' ? 'text-[#87d74e]' : 'text-white'">Les Deux (Choix libre)</span>
                                    <span class="text-[10px] text-gray-500 font-bold block mt-1">Le jeu vous demandera de choisir à chaque énigme selon vos envies.</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- STEP 5: Résumé -->
                    <div v-show="currentStep === 5" class="space-y-8 animate-fade-in-up">
                        <div class="space-y-2 border-b border-[#2a245c] pb-4 mb-6">
                            <h2 class="text-2xl font-black uppercase italic tracking-tighter">Prêt pour l'exploration ?</h2>
                            <p class="text-gray-400 text-xs font-medium">Passez en revue votre configuration avant l'allumage.</p>
                        </div>
                        
                        <div class="bg-[#10101c] p-6 rounded-2xl border border-[#2a245c] space-y-4">
                            <div class="flex justify-between border-b border-[#2a245c] pb-3">
                                <span class="text-xs font-black uppercase text-gray-500">Ville d'exploration</span>
                                <span class="font-black text-sm text-[#ffc628] uppercase">{{ selectedCity?.name }}</span>
                            </div>
                            <div class="flex justify-between border-b border-[#2a245c] pb-3">
                                <span class="text-xs font-black uppercase text-gray-500">Niveau de difficulté</span>
                                <span class="font-black text-sm capitalize text-white">{{ form.level }}</span>
                            </div>
                            <div class="flex justify-between border-b border-[#2a245c] pb-3">
                                <span class="text-xs font-black uppercase text-gray-500">Nombre d'énigmes</span>
                                <span class="font-black text-sm text-[#87d74e] text-glow-green">{{ form.riddles_count }}</span>
                            </div>
                            <div class="flex justify-between border-b border-[#2a245c] pb-3">
                                <span class="text-xs font-black uppercase text-gray-500">Type d'aventure</span>
                                <span class="font-black text-sm capitalize" :class="{
                                    'text-[#87d74e]': form.type === 'solo',
                                    'text-[#4769b0]': form.type === 'participants',
                                    'text-[#7751de]': form.type === 'challengers'
                                }">{{ form.type }} <span v-if="form.type !== 'solo'">({{ form.max_joueurs }} max)</span></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-xs font-black uppercase text-gray-500">Mode de Résolution</span>
                                <span class="font-black text-sm capitalize text-white">{{ form.global_mode }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Navigation Action Buttons (Bouncy 3D Controls) -->
                    <div class="mt-10 flex justify-between pt-6 border-t border-[#2a245c] relative z-10">
                        <button v-if="currentStep > 1" @click="prevStep" 
                            class="px-6 py-3.5 btn-3d btn-3d-purple text-xs font-black uppercase shadow-[0_4px_0_#4d2f94]">
                            Retour
                        </button>
                        <div v-else></div>

                        <button @click="nextStep" :disabled="isLocating || (currentStep === 1 && isCityWithoutRiddles)"
                            class="px-8 py-3.5 btn-3d btn-3d-green text-xs font-black uppercase disabled:opacity-30 disabled:scale-100 disabled:pointer-events-none flex items-center gap-3"
                            :class="currentStep === 5 ? 'shadow-[0_5px_0_#5d9933]' : 'shadow-[0_5px_0_#5d9933]'">
                            <template v-if="isLocating">
                                LOCALISATION...
                                <MapPin :size="16" class="animate-bounce" />
                            </template>
                            <template v-else>
                                {{ currentStep === 5 ? 'FORGER LA PARTIE' : 'Suivant' }}
                                <Rocket v-if="currentStep === 5" :size="16" />
                                <ChevronRight v-else :size="16" />
                            </template>
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
