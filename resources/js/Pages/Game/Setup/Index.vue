<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { 
    ChevronLeft, ChevronRight, Map as MapIcon, AlertTriangle,
    CheckCircle2, Landmark, User, Users, Swords, Gamepad2,
    Layers, MapPin, Rocket, Zap, Shield
} from 'lucide-vue-next';

const props = defineProps({
    cities: { type: Array, default: () => [] }
});

const currentStep = ref(1);
const isLocating = ref(false);
const TOTAL_STEPS = 5;

const form = reactive({
    level: 'facile',
    location_type: 'city',
    location_id: null,
    riddles_count: 5,
    type: 'solo',
    challenger_mode: null,
    max_joueurs: 1,
    global_mode: 'mixte',
    user_lat: null,
    user_lng: null,
    participate: true
});

onMounted(() => {
    if (props.cities?.length > 0) form.location_id = props.cities[0].id;
});

const selectedCity = computed(() => props.cities.find(c => c.id === form.location_id));
const isCityWithoutRiddles = computed(() => selectedCity.value && selectedCity.value.riddles_count === 0);
const maxRiddlesForLevel = computed(() => {
    if (!selectedCity.value?.riddles_by_level) return 1;
    return Math.max(1, selectedCity.value.riddles_by_level[form.level] || 0);
});

const adjustRiddlesCount = () => {
    if (form.riddles_count > maxRiddlesForLevel.value) form.riddles_count = maxRiddlesForLevel.value;
};

const stepProgress = computed(() => Math.round(((currentStep.value - 1) / (TOTAL_STEPS - 1)) * 100));

const STEP_LABELS = ['Ville', 'Niveau', 'Joueurs', 'Mode', 'Résumé'];

const canGoNext = computed(() => {
    if (currentStep.value === 1 && isCityWithoutRiddles.value) return false;
    if (currentStep.value === 3 && form.type === 'challengers' && !form.challenger_mode) return false;
    return true;
});

const nextStep = () => {
    if (!canGoNext.value) return;
    if (currentStep.value < TOTAL_STEPS) { currentStep.value++; }
    else { startSubmission(); }
};
const prevStep = () => { if (currentStep.value > 1) currentStep.value--; };

const startSubmission = () => {
    isLocating.value = true;
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (pos) => { form.user_lat = pos.coords.latitude; form.user_lng = pos.coords.longitude; submitForm(); },
            () => { form.user_lat = 6.3650; form.user_lng = 2.4183; submitForm(); },
            { enableHighAccuracy: true, timeout: 5000, maximumAge: 0 }
        );
    } else {
        form.user_lat = 6.3650; form.user_lng = 2.4183; submitForm();
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
    <AuthenticatedLayout title="Nouvelle Partie">
        <div class="max-w-xl mx-auto animate-fade-in-up">

            <!-- Header compact -->
            <div class="text-center mb-4 sm:mb-6">
                <span class="text-[9px] font-black tracking-[0.3em] text-[#87d74e] uppercase italic">Nouvel itinéraire</span>
                <h1 class="text-2xl sm:text-3xl font-black uppercase italic tracking-tighter text-white mt-1">
                    CRÉER UNE <span class="text-[#87d74e] text-glow-green">PARTIE</span>
                </h1>
            </div>

            <!-- Stepper compact -->
            <div class="mb-4 sm:mb-6">
                <div class="flex justify-between items-center mb-2">
                    <span class="text-[9px] font-black text-gray-500 uppercase tracking-widest">Étape {{ currentStep }} / {{ TOTAL_STEPS }}</span>
                    <span class="text-[9px] font-black text-[#87d74e] uppercase tracking-widest">{{ STEP_LABELS[currentStep - 1] }}</span>
                </div>
                <div class="h-2 bg-[#10101c] rounded-full border border-[#2a245c] overflow-hidden">
                    <div :style="`width: ${stepProgress}%`"
                         class="h-full bg-gradient-to-r from-[#7751de] to-[#87d74e] rounded-full transition-all duration-400 ease-out shadow-[0_0_8px_rgba(135,215,78,0.4)]"></div>
                </div>
                <!-- Dots -->
                <div class="flex justify-between mt-2 px-0.5">
                    <div v-for="i in TOTAL_STEPS" :key="i"
                        class="w-2 h-2 rounded-full transition-all duration-300"
                        :class="i <= currentStep ? 'bg-[#87d74e] shadow-[0_0_6px_rgba(135,215,78,0.6)]' : 'bg-[#2a245c]'">
                    </div>
                </div>
            </div>

            <!-- Erreur backend -->
            <div v-if="$page.props.errors.error || $page.props.flash?.error"
                class="mb-4 bg-red-500/10 border border-red-500/30 p-3 rounded-2xl text-red-400 text-xs font-black uppercase tracking-widest flex items-center gap-2">
                <AlertTriangle :size="14" />
                <span>{{ $page.props.errors.error || $page.props.flash?.error }}</span>
            </div>

            <!-- Panel principal -->
            <div class="panel-glass p-4 sm:p-6 border border-[#2a245c] relative overflow-hidden">

                <!-- STEP 1 : Ville -->
                <div v-show="currentStep === 1" class="animate-fade-in-up space-y-3">
                    <div class="mb-3">
                        <h2 class="text-lg font-black uppercase italic tracking-tighter">Dans quelle ville ?</h2>
                        <p class="text-gray-400 text-[11px] font-medium mt-0.5">Choisissez une zone d'exploration active.</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <label v-for="city in cities" :key="city.id"
                            class="cursor-pointer relative rounded-xl border-2 p-3.5 flex items-center gap-3 transition-all duration-150 bg-[#10101c]"
                            :class="form.location_id === city.id
                                ? 'border-[#87d74e] bg-[#87d74e]/5 shadow-[0_0_12px_rgba(135,215,78,0.15)]'
                                : 'border-[#2a245c] hover:border-gray-500 active:scale-98'">
                            <input type="radio" v-model="form.location_id" :value="city.id" class="sr-only">
                            <CheckCircle2 v-if="form.location_id === city.id" :size="16" class="text-[#87d74e] shrink-0" />
                            <Landmark v-else :size="16" class="text-gray-500 shrink-0" />
                            <div class="flex-1 min-w-0">
                                <p class="font-black text-sm truncate" :class="form.location_id === city.id ? 'text-[#87d74e]' : 'text-white'">{{ city.name }}</p>
                                <p class="text-[8px] font-black uppercase tracking-widest"
                                    :class="city.riddles_count > 0 ? 'text-[#87d74e]/70' : 'text-red-500'">
                                    {{ city.riddles_count }} énigme{{ city.riddles_count > 1 ? 's' : '' }}
                                </p>
                            </div>
                        </label>
                    </div>

                    <div v-if="isCityWithoutRiddles"
                        class="p-4 rounded-xl border-2 border-dashed border-red-500/30 bg-red-500/5 text-center">
                        <p class="text-xs font-black text-red-400 uppercase tracking-wider">Pas d'énigmes pour {{ selectedCity?.name }}</p>
                        <p class="text-[10px] text-gray-400 mt-1">Des énigmes seront bientôt disponibles !</p>
                    </div>
                </div>

                <!-- STEP 2 : Niveau & Nb d'énigmes -->
                <div v-show="currentStep === 2" class="animate-fade-in-up space-y-5">
                    <div class="mb-3">
                        <h2 class="text-lg font-black uppercase italic tracking-tighter">Niveau de défi</h2>
                        <p class="text-gray-400 text-[11px] font-medium mt-0.5">Choisissez la difficulté et le nombre d'énigmes.</p>
                    </div>

                    <!-- Difficulté -->
                    <div class="grid grid-cols-3 gap-2">
                        <label v-for="lvl in ['facile', 'intermediaire', 'difficile']" :key="lvl"
                            class="cursor-pointer rounded-xl border-2 p-3 text-center transition-all duration-150 bg-[#10101c]"
                            :class="form.level === lvl ? 'border-[#87d74e] bg-[#87d74e]/5' : 'border-[#2a245c] hover:border-gray-500'"
                            @click="form.level = lvl; adjustRiddlesCount();">
                            <input type="radio" v-model="form.level" :value="lvl" class="sr-only">
                            <span class="block text-[10px] font-black capitalize tracking-wider" :class="form.level === lvl ? 'text-[#87d74e]' : 'text-gray-400'">
                                {{ lvl === 'facile' ? '😊' : lvl === 'intermediaire' ? '🔥' : '💀' }}
                            </span>
                            <span class="block text-[9px] font-black capitalize" :class="form.level === lvl ? 'text-[#87d74e]' : 'text-gray-400'">{{ lvl }}</span>
                            <span v-if="selectedCity?.riddles_by_level" class="text-[7px] text-gray-500 font-bold">
                                {{ selectedCity.riddles_by_level[lvl] || 0 }} dispo
                            </span>
                        </label>
                    </div>

                    <!-- Nombre d'énigmes -->
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-[9px] font-black uppercase tracking-widest text-gray-400">Nombre d'énigmes</span>
                            <span class="text-[9px] font-bold text-gray-500">Max: {{ maxRiddlesForLevel }}</span>
                        </div>
                        <input type="range" v-model="form.riddles_count" min="1" :max="maxRiddlesForLevel"
                            class="w-full accent-[#87d74e] h-2 rounded-full cursor-pointer bg-[#10101c]">
                        <div class="text-center text-4xl font-black text-[#87d74e] text-glow-green tabular-nums">{{ form.riddles_count }}</div>
                    </div>
                </div>

                <!-- STEP 3 : Mode joueurs -->
                <div v-show="currentStep === 3" class="animate-fade-in-up space-y-4">
                    <div class="mb-3">
                        <h2 class="text-lg font-black uppercase italic tracking-tighter">Avec qui jouer ?</h2>
                        <p class="text-gray-400 text-[11px] font-medium mt-0.5">Solo, coopération ou versus.</p>
                    </div>

                    <div class="grid grid-cols-3 gap-2.5">
                        <label v-for="mode in [
                            { id: 'solo',         label: 'Solo',  emoji: '🧍', color: 'text-blue-400',    active: 'border-blue-400 bg-blue-400/5' },
                            { id: 'participants', label: 'Coop',  emoji: '🤝', color: 'text-[#87d74e]',   active: 'border-[#87d74e] bg-[#87d74e]/5' },
                            { id: 'challengers', label: 'Versus', emoji: '⚔️', color: 'text-red-400',     active: 'border-red-400 bg-red-400/5' }
                        ]" :key="mode.id"
                            class="relative flex flex-col items-center justify-center p-4 rounded-2xl border-2 transition-all cursor-pointer"
                            :class="form.type === mode.id ? mode.active : 'bg-[#10101c] border-[#2a245c] hover:border-gray-500'">
                            <input type="radio" v-model="form.type" :value="mode.id" class="sr-only">
                            <span class="text-2xl mb-1">{{ mode.emoji }}</span>
                            <span class="text-[9px] font-black uppercase tracking-widest" :class="form.type === mode.id ? mode.color : 'text-gray-500'">
                                {{ mode.label }}
                            </span>
                        </label>
                    </div>

                    <!-- Participation admin -->
                    <div v-if="$page.props.auth.user.is_admin"
                        class="p-3 rounded-xl bg-[#10101c] border border-[#2a245c] flex items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-black text-white">Participer à la partie ?</p>
                            <p class="text-[9px] text-gray-500">Sinon, tu partages juste le lien.</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer shrink-0">
                            <input type="checkbox" v-model="form.participate" class="sr-only peer">
                            <div class="w-11 h-6 bg-[#2a245c] rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#87d74e]"></div>
                        </label>
                    </div>

                    <!-- Max joueurs (si multijoueur) -->
                    <div v-if="form.type !== 'solo'" class="space-y-2">
                        <label class="text-[9px] font-black uppercase tracking-widest text-gray-400">Nombre de joueurs max</label>
                        <input type="number" v-model="form.max_joueurs" min="2" max="50"
                            class="w-full bg-[#10101c] border border-[#2a245c] rounded-xl p-3 text-white font-black text-center text-lg focus:ring-1 focus:ring-[#87d74e] focus:border-[#87d74e] focus:outline-none">
                    </div>

                    <!-- Options challengers -->
                    <div v-if="form.type === 'challengers'" class="space-y-2 p-4 bg-[#10101c] rounded-xl border border-[#2a245c]">
                        <p class="text-[9px] font-black uppercase tracking-widest text-[#7751de] mb-2">Mode de compétition</p>
                        <label v-for="opt in [
                            { val: 'reponse_par_membre', label: '⚡ Rapide', sub: 'Le premier répond bloque l\'énigme.' },
                            { val: 'reponse_par_tous', label: '🎯 Complet', sub: 'Chacun doit répondre individuellement.' }
                        ]" :key="opt.val"
                            class="flex items-center gap-3 p-3 rounded-xl cursor-pointer transition-all"
                            :class="form.challenger_mode === opt.val ? 'bg-[#7751de]/10 border border-[#7751de]/30' : 'hover:bg-[#1c183a]'">
                            <input type="radio" v-model="form.challenger_mode" :value="opt.val" class="text-[#7751de] focus:ring-0">
                            <div>
                                <p class="text-xs font-black text-white">{{ opt.label }}</p>
                                <p class="text-[9px] text-gray-500 font-bold">{{ opt.sub }}</p>
                            </div>
                        </label>
                        <p v-if="form.type === 'challengers' && !form.challenger_mode" class="text-[9px] text-red-400 font-black mt-1">Choisissez un mode de compétition.</p>
                    </div>
                </div>

                <!-- STEP 4 : Mode de résolution -->
                <div v-show="currentStep === 4" class="animate-fade-in-up space-y-3">
                    <div class="mb-3">
                        <h2 class="text-lg font-black uppercase italic tracking-tighter">Comment résoudre ?</h2>
                        <p class="text-gray-400 text-[11px] font-medium mt-0.5">Physique, gaming ou les deux.</p>
                    </div>

                    <div class="space-y-2.5">
                        <label v-for="gm in [
                            { val: 'decouverte', emoji: '🗺️', label: 'Découverte', sub: 'Rendez-vous sur place, validation GPS.', color: '#ffc628', border: 'border-[#ffc628] bg-[#ffc628]/5' },
                            { val: 'gaming',     emoji: '🎮', label: 'Gaming',     sub: 'Répondez depuis chez vous, pas de GPS.',  color: '#4769b0', border: 'border-[#4769b0] bg-[#4769b0]/5' },
                            { val: 'mixte',      emoji: '⚡', label: 'Mixte',      sub: 'Choix libre à chaque énigme.',             color: '#87d74e', border: 'border-[#87d74e] bg-[#87d74e]/5' },
                        ]" :key="gm.val"
                            class="cursor-pointer rounded-xl border-2 p-4 flex items-center gap-4 transition-all duration-150 bg-[#10101c]"
                            :class="form.global_mode === gm.val ? gm.border : 'border-[#2a245c] hover:border-gray-500'"
                            @click="form.global_mode = gm.val">
                            <input type="radio" v-model="form.global_mode" :value="gm.val" class="sr-only">
                            <span class="text-2xl shrink-0">{{ gm.emoji }}</span>
                            <div>
                                <p class="font-black text-sm" :style="form.global_mode === gm.val ? `color: ${gm.color}` : 'color: white'">{{ gm.label }}</p>
                                <p class="text-[9px] text-gray-500 font-bold mt-0.5">{{ gm.sub }}</p>
                            </div>
                            <CheckCircle2 v-if="form.global_mode === gm.val" :size="16" class="ml-auto shrink-0" :style="`color: ${gm.color}`" />
                        </label>
                    </div>
                </div>

                <!-- STEP 5 : Résumé -->
                <div v-show="currentStep === 5" class="animate-fade-in-up space-y-4">
                    <div class="mb-3">
                        <h2 class="text-lg font-black uppercase italic tracking-tighter">Tout est prêt ! 🚀</h2>
                        <p class="text-gray-400 text-[11px] font-medium mt-0.5">Vérifie ta configuration avant de lancer.</p>
                    </div>

                    <div class="bg-[#10101c] rounded-xl border border-[#2a245c] divide-y divide-[#2a245c]">
                        <div class="flex justify-between items-center px-4 py-3">
                            <span class="text-[10px] font-black uppercase text-gray-500">Ville</span>
                            <span class="font-black text-sm text-[#ffc628] uppercase">{{ selectedCity?.name }}</span>
                        </div>
                        <div class="flex justify-between items-center px-4 py-3">
                            <span class="text-[10px] font-black uppercase text-gray-500">Niveau</span>
                            <span class="font-black text-sm capitalize text-white">{{ form.level }}</span>
                        </div>
                        <div class="flex justify-between items-center px-4 py-3">
                            <span class="text-[10px] font-black uppercase text-gray-500">Énigmes</span>
                            <span class="font-black text-sm text-[#87d74e] text-glow-green">{{ form.riddles_count }}</span>
                        </div>
                        <div class="flex justify-between items-center px-4 py-3">
                            <span class="text-[10px] font-black uppercase text-gray-500">Mode</span>
                            <span class="font-black text-sm capitalize text-white">
                                {{ form.type }} <span v-if="form.type !== 'solo'" class="text-gray-400 font-normal">({{ form.max_joueurs }} max)</span>
                            </span>
                        </div>
                        <div class="flex justify-between items-center px-4 py-3">
                            <span class="text-[10px] font-black uppercase text-gray-500">Résolution</span>
                            <span class="font-black text-sm capitalize text-white">{{ form.global_mode }}</span>
                        </div>
                    </div>
                </div>

                <!-- Navigation -->
                <div class="mt-5 flex justify-between items-center pt-4 border-t border-[#2a245c]">
                    <button v-if="currentStep > 1" @click="prevStep"
                        class="btn-3d btn-3d-purple px-5 py-3 text-[10px] shadow-[0_4px_0_#4d2f94]">
                        <span class="flex items-center gap-1.5"><ChevronLeft :size="14" /> Retour</span>
                    </button>
                    <div v-else></div>

                    <button @click="nextStep"
                        :disabled="isLocating || !canGoNext"
                        class="btn-3d btn-3d-green px-6 py-3 text-[10px] shadow-[0_4px_0_#5d9933] disabled:opacity-30 flex items-center gap-2">
                        <template v-if="isLocating">
                            <MapPin :size="14" class="animate-bounce" /> GPS...
                        </template>
                        <template v-else-if="currentStep === TOTAL_STEPS">
                            <Rocket :size="14" /> FORGER LA PARTIE
                        </template>
                        <template v-else>
                            Suivant <ChevronRight :size="14" />
                        </template>
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.animate-fade-in-up {
    animation: fadeInUp 0.3s ease-out forwards;
}
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(8px); }
    to   { opacity: 1; transform: translateY(0); }
}
</style>
