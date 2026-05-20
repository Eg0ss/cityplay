<script setup>
// Importation du layout de base pour l'administration
import AdminLayout from './AdminLayout.vue';
// Importation des utilitaires Inertia pour la gestion du head, des liens et des formulaires
import { Head, Link, useForm, router } from '@inertiajs/vue3';
// Importation des hooks Vue pour la réactivité et la surveillance
import { ref, watch, computed } from 'vue';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';

// Définition des propriétés reçues (Lieu parent et liste des énigmes)
const props = defineProps({
    place: Object,
    enigmas: Array,
});

const toast = useToast();
const confirm = useConfirm();

// État pour afficher ou masquer le processus de création
const showForm = ref(false);

// État pour suivre l'étape actuelle du processus (Style Jeu Vidéo)
const currentStep = ref(1);

// Définition du formulaire avec Inertia useForm
const form = useForm({
    niveau: 1,                // Niveau de difficulté (1-3)
    description: '',          // Texte de l'énigme
    reponse: props.place.nom,  // Réponse attendue (Pré-remplie avec le nom du lieu)
    mcq_options: ['', '', '', ''], // Options pour les niveaux 1 & 2
    images: [],               // Fichiers images associés
    hints: [],                // Liste des indices
});

// Fonctions pour gérer les indices
const addHint = () => {
    form.hints.push({
        type: 'text',
        content: '',
        difficulty_level: 'easy'
    });
};

const removeHint = (index) => {
    form.hints.splice(index, 1);
};

// Gestion du changement de fichiers images
const onFileChange = (e) => {
    form.images = Array.from(e.target.files);
};

// Surveillance du niveau pour adapter les options QCM (pas de QCM au niveau 3)
watch(() => form.niveau, (newVal) => {
    if (newVal === 3) {
        form.mcq_options = [];
    } else if (form.mcq_options.length === 0) {
        form.mcq_options = ['', '', '', ''];
    }
});

/**
 * Navigation entre les phases de forge
 */
const nextStep = () => {
    if (currentStep.value < 5) currentStep.value++;
};

const prevStep = () => {
    if (currentStep.value > 1) currentStep.value--;
};

// Calcul de la barre de progression (Gaming style)
const progressWidth = computed(() => {
    return ((currentStep.value - 1) / 4) * 100 + '%';
});

const isEditing = ref(false);
const editingEnigmaId = ref(null);

const openCreateForm = () => {
    isEditing.value = false;
    editingEnigmaId.value = null;
    form.reset();
    showForm.value = true;
    currentStep.value = 1;
};

const openEditForm = (enigma) => {
    isEditing.value = true;
    editingEnigmaId.value = enigma.id;
    form.niveau = enigma.niveau;
    form.description = enigma.description;
    form.reponse = enigma.reponse;
    form.mcq_options = enigma.mcq_options || ['', '', '', ''];
    form.hints = enigma.hints ? JSON.parse(JSON.stringify(enigma.hints)) : [];
    form.images = [];
    showForm.value = true;
    currentStep.value = 1;
};

/**
 * Soumission finale de l'énigme à la matrice
 */
const submit = () => {
    const url = isEditing.value 
        ? route('admin.enigmas.update', { enigma: editingEnigmaId.value })
        : route('admin.enigmas.store', { place: props.place.id });

    form.post(url, {
        onSuccess: () => {
            showForm.value = false;
            currentStep.value = 1;
            form.reset();
            toast.add({ 
                severity: 'success', 
                summary: isEditing.value ? 'Énigme Mise à Jour' : 'Énigme Forgeé', 
                detail: isEditing.value ? 'Le défi a été mis à jour dans la matrice.' : 'Le nouveau défi a été inscrit dans la matrice.', 
                life: 3000 
            });
        },
        onError: (errors) => {
            const firstError = Object.values(errors)[0];
            toast.add({ 
                severity: 'error', 
                summary: 'Erreur de Forge', 
                detail: firstError || 'Une erreur est survenue lors de l\'opération.', 
                life: 5000 
            });
        }
    });
};

const confirmDelete = (enigma) => {
    confirm.require({
        message: `Voulez-vous vraiment supprimer cette énigme ? Cette action est irréversible.`,
        header: 'CONFIRMATION DE SUPPRESSION',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'SUPPRIMER',
        rejectLabel: 'ANNULER',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('admin.enigmas.delete', { enigma: enigma.id }), {
                onSuccess: () => {
                    toast.add({ 
                        severity: 'success', 
                        summary: 'Matrice Mise à Jour', 
                        detail: 'L\'énigme a été supprimée de la matrice.', 
                        life: 3000 
                    });
                },
            });
        }
    });
};
</script>

<template>
    <Head :title="'Quest Editor - ' + place.nom" />
    
    <AdminLayout>
        <div class="space-y-8 lg:space-y-12">
            <!-- En-tête de l'éditeur de quêtes -->
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
                <div class="w-full md:w-auto">
                    <div class="flex items-center gap-3 md:gap-4 mb-4">
                        <!-- Retour vers les secteurs -->
                        <Link :href="route('admin.cities.places', { city: place.city_id })" 
                            class="h-10 w-10 md:h-12 md:w-12 dark:bg-white/5 bg-gray-100 rounded-xl md:rounded-2xl flex items-center justify-center hover:bg-[#FF9F1C] hover:text-black transition-all group">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 md:w-5 md:h-5 group-hover:-translate-x-1 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                        </Link>
                        <span class="text-[8px] md:text-[10px] font-black uppercase tracking-[0.2em] md:tracking-[0.3em] text-[#FF9F1C] truncate">SECTEUR : {{ place.city?.name.toUpperCase() }}</span>
                    </div>
                    <h1 class="text-3xl md:text-6xl font-black tracking-tighter uppercase italic leading-none dark:text-white text-gray-900">
                        QUEST <span class="text-[#FF9F1C]">EDITOR</span>
                    </h1>
                    <p class="text-gray-500 font-bold uppercase tracking-[0.2em] text-[8px] md:text-[10px] mt-2 md:mt-4 truncate">
                        UNITÉ CIBLE : <span class="dark:text-white text-gray-900">{{ place.nom.toUpperCase() }}</span>
                    </p>
                </div>
                <!-- Bouton d'activation de l'atelier -->
                <button @click="showForm ? (showForm = false) : openCreateForm()" 
                    :class="showForm ? 'bg-red-500 shadow-lg text-white' : 'dark:bg-white bg-gray-900 dark:text-black text-white shadow-lg'"
                    class="w-full md:w-auto px-6 md:px-10 py-4 md:py-5 rounded-xl md:rounded-2xl font-black uppercase tracking-widest text-[10px] md:text-xs transition-all hover:scale-105 active:scale-95">
                    {{ showForm ? 'FERMER L\'ATELIER' : 'FORGER UNE ÉNIGME' }}
                </button>
            </div>

            <!-- Atelier de forge (Processus interactif style jeu vidéo) -->
            <transition name="gaming-slide">
                <div v-if="showForm" class="dark:bg-[#111113] bg-white p-6 lg:p-12 rounded-[2.5rem] border dark:border-white/5 border-gray-200 shadow-2xl relative overflow-hidden">
                    
                    <!-- Barre de progression lumineuse -->
                    <div class="absolute top-0 left-0 w-full h-1.5 bg-gray-100 dark:bg-white/5 overflow-hidden">
                        <div class="h-full bg-[#FF9F1C] transition-all duration-700 ease-out shadow-[0_0_15px_#FF9F1C]" :style="{ width: progressWidth }"></div>
                    </div>

                    <div class="relative z-10">
                        <transition name="step-fade" mode="out-in">
                            <!-- Phase 01 : Niveau de difficulté -->
                            <div v-if="currentStep === 1" key="step1" class="space-y-6 md:space-y-8 py-2 md:py-4">
                                <div class="space-y-2">
                                    <span class="text-[8px] md:text-[10px] font-black text-[#FF9F1C] tracking-[0.5em] uppercase">Phase 01</span>
                                    <h3 class="text-2xl md:text-3xl font-black uppercase italic tracking-tighter">NIVEAU D'<span class="text-[#FF9F1C]">ÉNERGIE</span></h3>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 md:gap-4">
                                    <button v-for="n in [1,2,3]" :key="n" type="button" @click="form.niveau = n"
                                        :class="[
                                            form.niveau === n ? (n===1 ? 'bg-blue-500 text-black' : n===2 ? 'bg-yellow-500 text-black' : 'bg-red-500 text-black shadow-[0_0_30px_rgba(239,68,68,0.3)]') : 'dark:bg-black/40 bg-gray-50 border dark:border-white/5 border-gray-200 text-gray-500'
                                        ]"
                                        class="py-6 md:py-8 rounded-xl md:rounded-2xl font-black transition-all text-xs md:text-sm uppercase tracking-widest">
                                        {{ n === 1 ? 'Facile' : n === 2 ? 'Intermédiaire' : 'Légendaire' }}
                                        <span class="block text-[7px] md:text-[8px] opacity-60 mt-1 md:mt-2">LVL {{ n }}</span>
                                    </button>
                                </div>
                                <button @click="nextStep" class="w-full md:w-auto group flex items-center justify-center gap-4 bg-[#FF9F1C] text-black px-8 md:px-10 py-5 md:py-6 rounded-xl md:rounded-2xl font-black uppercase tracking-widest text-xs md:text-sm shadow-xl hover:scale-105 transition-all">
                                    VERROUILLER LA DIFFICULTÉ
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 md:w-6 md:h-6 group-hover:translate-x-2 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                                </button>
                            </div>

                            <!-- Phase 02 : Réponse attendue -->
                            <div v-else-if="currentStep === 2" key="step2" class="space-y-6 md:space-y-8 py-2 md:py-4">
                                <div class="space-y-2">
                                    <span class="text-[8px] md:text-[10px] font-black text-[#FF9F1C] tracking-[0.5em] uppercase">Phase 02</span>
                                    <h3 class="text-2xl md:text-3xl font-black uppercase italic tracking-tighter">CODE DE <span class="text-[#FF9F1C]">VALIDATION</span></h3>
                                </div>
                                <div class="space-y-3 md:space-y-4">
                                    <label class="block text-[8px] md:text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 ms-2">La réponse exacte à l'énigme</label>
                                    <input v-model="form.reponse" type="text" autofocus
                                        class="w-full text-xl md:text-4xl dark:bg-black/40 bg-gray-50 border-none rounded-2xl md:rounded-3xl py-6 md:py-8 px-6 md:px-8 focus:ring-4 focus:ring-[#FF9F1C]/20 dark:text-white text-gray-900 font-black italic tracking-tighter placeholder:opacity-20" 
                                        placeholder="EX: PLACE DES MARTYRS" />
                                </div>
                                <div class="flex flex-col sm:flex-row gap-3 md:gap-4">
                                    <button @click="prevStep" class="w-full sm:w-auto px-8 py-5 md:py-6 rounded-xl md:rounded-2xl border-2 dark:border-white/10 border-gray-200 font-black uppercase tracking-widest text-[10px] md:text-xs hover:bg-white hover:text-black transition-all">RETOUR</button>
                                    <button @click="nextStep" :disabled="!form.reponse" class="w-full sm:flex-1 group flex items-center justify-center gap-4 bg-[#FF9F1C] text-black px-8 md:px-10 py-5 md:py-6 rounded-xl md:rounded-2xl font-black uppercase tracking-widest text-[10px] md:text-sm shadow-xl hover:scale-105 transition-all disabled:opacity-30">
                                        ENREGISTRER LE CODE
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 md:w-6 md:h-6 group-hover:translate-x-2 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Phase 03 : Texte de l'énigme & QCM -->
                            <div v-else-if="currentStep === 3" key="step3" class="space-y-6 md:space-y-8 py-2 md:py-4">
                                <div class="space-y-2">
                                    <span class="text-[8px] md:text-[10px] font-black text-[#FF9F1C] tracking-[0.5em] uppercase">Phase 03</span>
                                    <h3 class="text-2xl md:text-3xl font-black uppercase italic tracking-tighter">SÉQUENCE <span class="text-[#FF9F1C]">ÉNIGMATIQUE</span></h3>
                                </div>
                                <div class="space-y-4 md:space-y-6">
                                    <div class="space-y-2 md:space-y-3">
                                        <label class="block text-[8px] md:text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 ms-2">Le texte de l'indice</label>
                                        <textarea v-model="form.description" rows="4" autofocus
                                            class="w-full text-lg md:text-xl dark:bg-black/40 bg-gray-50 border-none rounded-2xl md:rounded-3xl py-5 md:py-6 px-6 md:px-8 focus:ring-4 focus:ring-[#FF9F1C]/20 dark:text-white text-gray-900 font-bold italic tracking-tight placeholder:opacity-20" 
                                            placeholder="DÉCRIVEZ LE LIEU SANS LE NOMMER..."></textarea>
                                    </div>

                                    <!-- Options QCM (Seulement LVL 1 & 2) -->
                                    <div v-if="form.niveau < 3" class="space-y-3 md:space-y-4">
                                        <label class="block text-[8px] md:text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 ms-2">Propositions de Réponses (Fausse pistes)</label>
                                        <div class="grid gap-3 md:gap-4 sm:grid-cols-2">
                                            <div v-for="(opt, index) in form.mcq_options" :key="index" class="relative group">
                                                <span class="absolute left-5 top-1/2 -translate-y-1/2 text-[8px] md:text-[10px] font-black text-[#FF9F1C] group-focus-within:scale-125 transition-transform">{{ index + 1 }}</span>
                                                <input v-model="form.mcq_options[index]" type="text" 
                                                    class="w-full dark:bg-black/40 bg-gray-50 border-none rounded-xl md:rounded-2xl py-4 md:py-5 pl-10 md:pl-12 pr-4 md:pr-6 focus:ring-2 focus:ring-[#FF9F1C]/30 text-[10px] md:text-xs dark:text-white text-gray-900 font-bold uppercase tracking-widest" 
                                                    :placeholder="'OPTION ' + (index+1)" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex flex-col sm:flex-row gap-3 md:gap-4">
                                    <button @click="prevStep" class="w-full sm:w-auto px-8 py-5 md:py-6 rounded-xl md:rounded-2xl border-2 dark:border-white/10 border-gray-200 font-black uppercase tracking-widest text-[10px] md:text-xs hover:bg-white hover:text-black transition-all">RETOUR</button>
                                    <button @click="nextStep" :disabled="!form.description" class="w-full sm:flex-1 group flex items-center justify-center gap-4 bg-[#FF9F1C] text-black px-8 md:px-10 py-5 md:py-6 rounded-xl md:rounded-2xl font-black uppercase tracking-widest text-[10px] md:text-sm shadow-xl hover:scale-105 transition-all disabled:opacity-30">
                                        VERROUILLER LE NARRATIF
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 md:w-6 md:h-6 group-hover:translate-x-2 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Phase 04 : Images / Artefacts -->
                            <div v-else-if="currentStep === 4" key="step4" class="space-y-6 md:space-y-8 py-2 md:py-4">
                                <div class="space-y-2">
                                    <span class="text-[8px] md:text-[10px] font-black text-[#FF9F1C] tracking-[0.5em] uppercase">Phase 04</span>
                                    <h3 class="text-2xl md:text-3xl font-black uppercase italic tracking-tighter">ARTEFACTS <span class="text-[#FF9F1C]">VISUELS</span></h3>
                                </div>
                                <div class="space-y-4">
                                    <div class="relative group">
                                        <input type="file" multiple @change="onFileChange" 
                                            class="absolute inset-0 opacity-0 cursor-pointer z-20" accept="image/*" />
                                        <div class="dark:bg-black/40 bg-gray-50 border-2 border-dashed dark:border-white/10 border-gray-200 rounded-[2rem] py-10 md:py-16 px-6 md:px-8 text-center group-hover:border-[#FF9F1C]/50 transition-all">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 md:w-12 md:h-12 mx-auto mb-3 md:mb-4 text-gray-500 group-hover:text-[#FF9F1C] transition-colors" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                                            <p class="text-[10px] md:text-xs font-black uppercase tracking-widest dark:text-white text-gray-900">Transférer les artefacts (Images)</p>
                                            <p class="text-[8px] md:text-[10px] text-gray-500 uppercase font-bold mt-2">{{ form.images.length }} fichier(s) sélectionné(s)</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex flex-col sm:flex-row gap-3 md:gap-4">
                                    <button @click="prevStep" class="w-full sm:w-auto px-8 py-5 md:py-6 rounded-xl md:rounded-2xl border-2 dark:border-white/10 border-gray-200 font-black uppercase tracking-widest text-[10px] md:text-xs hover:bg-white hover:text-black transition-all">RETOUR</button>
                                    <button @click="nextStep" class="w-full sm:flex-1 group flex items-center justify-center gap-4 bg-[#FF9F1C] text-black px-8 md:px-10 py-5 md:py-6 rounded-xl md:rounded-2xl font-black uppercase tracking-widest text-[10px] md:text-sm shadow-xl hover:scale-105 transition-all">
                                        VERROUILLER LES IMAGES
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 md:w-6 md:h-6 group-hover:translate-x-2 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Phase 05 : Indices (Hints) -->
                            <div v-else-if="currentStep === 5" key="step5" class="space-y-6 md:space-y-8 py-2 md:py-4">
                                <div class="space-y-2">
                                    <span class="text-[8px] md:text-[10px] font-black text-[#FF9F1C] tracking-[0.5em] uppercase">Phase 05</span>
                                    <h3 class="text-2xl md:text-3xl font-black uppercase italic tracking-tighter">PROTOCOLE D'<span class="text-[#FF9F1C]">ASSISTANCE</span></h3>
                                </div>
                                
                                <div class="space-y-4">
                                    <div v-for="(hint, index) in form.hints" :key="index" class="p-6 rounded-3xl dark:bg-black/40 bg-gray-50 border dark:border-white/5 border-gray-200 space-y-4 relative group/hint">
                                        <button @click="removeHint(index)" class="absolute top-4 right-4 text-gray-500 hover:text-red-500 transition-colors opacity-0 group-hover/hint:opacity-100">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                                        </button>
                                        
                                        <div class="grid grid-cols-2 gap-4">
                                            <div class="space-y-2">
                                                <label class="text-[8px] font-black uppercase text-gray-500">Type d'indice</label>
                                                <select v-model="hint.type" class="w-full bg-transparent border-none p-0 text-[10px] font-black uppercase tracking-widest text-[#FF9F1C] focus:ring-0">
                                                    <option value="text">Texte descriptif</option>
                                                    <option value="keyword">Mot-clé</option>
                                                    <option value="image">URL Image / Chemin</option>
                                                    <option value="description">Description détaillée</option>
                                                </select>
                                            </div>
                                            <div class="space-y-2">
                                                <label class="text-[8px] font-black uppercase text-gray-500">Difficulté d'accès</label>
                                                <select v-model="hint.difficulty_level" class="w-full bg-transparent border-none p-0 text-[10px] font-black uppercase tracking-widest text-gray-400 focus:ring-0">
                                                    <option value="easy">Facile (Faible coût)</option>
                                                    <option value="medium">Moyen (Coût modéré)</option>
                                                    <option value="hard">Difficile (Coût élevé)</option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div class="space-y-2">
                                            <label class="text-[8px] font-black uppercase text-gray-500">Contenu de l'indice</label>
                                            <textarea v-model="hint.content" rows="2" class="w-full bg-transparent border-none p-0 text-xs font-bold italic text-white focus:ring-0 placeholder:opacity-20" placeholder="SAISISSEZ L'INDICE ICI..."></textarea>
                                        </div>
                                    </div>
                                    
                                    <button @click="addHint" type="button" class="w-full py-4 border-2 border-dashed dark:border-white/10 border-gray-200 rounded-2xl text-[8px] font-black uppercase tracking-[0.3em] text-gray-500 hover:border-[#FF9F1C]/50 hover:text-[#FF9F1C] transition-all flex items-center justify-center gap-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                                        AJOUTER UN INDICE AU PROTOCOLE
                                    </button>
                                </div>

                                <div class="flex flex-col sm:flex-row gap-3 md:gap-4">
                                    <button @click="prevStep" class="w-full sm:w-auto px-8 py-5 md:py-6 rounded-xl md:rounded-2xl border-2 dark:border-white/10 border-gray-200 font-black uppercase tracking-widest text-[10px] md:text-xs hover:bg-white hover:text-black transition-all">RETOUR</button>
                                    <button @click="submit" :disabled="form.processing" class="w-full sm:flex-1 group flex items-center justify-center gap-4 bg-white text-black px-8 md:px-10 py-5 md:py-6 rounded-xl md:rounded-2xl font-black uppercase tracking-widest text-[10px] md:text-sm shadow-[0_0_30px_rgba(255,255,255,0.3)] hover:scale-105 transition-all disabled:opacity-30">
                                        INSCRIRE DANS LA MATRICE
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 md:w-6 md:h-6 animate-pulse" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v4"/><path d="m16.2 7.8 2.9-2.9"/><path d="M18 12h4"/><path d="m16.2 16.2 2.9 2.9"/><path d="M12 18v4"/><path d="m4.9 19.1 2.9-2.9"/><path d="M2 12h4"/><path d="m4.9 4.9 2.9 2.9"/></svg>
                                    </button>
                                </div>
                            </div>
                        </transition>
                    </div>

                    <!-- Éléments HUD décoratifs -->
                    <div class="absolute bottom-4 right-8 text-[8px] font-black text-gray-600 uppercase tracking-[0.4em] animate-pulse">Core Sync: 100%</div>
                    <div class="absolute bottom-4 left-8 text-[8px] font-black text-gray-600 uppercase tracking-[0.4em]">Step {{ currentStep }} / 05</div>
                </div>
            </transition>

            <!-- Liste des Énigmes déployées -->
            <div class="grid gap-12">
                <div v-for="level in [3, 2, 1]" :key="level" class="space-y-6">
                    <!-- En-tête de niveau -->
                    <div class="flex items-center gap-4">
                        <div :class="[level===1 ? 'bg-blue-500' : level===2 ? 'bg-yellow-500' : 'bg-red-500 shadow-[0_0_15px_#ef4444]']" class="h-1.5 w-16 rounded-full"></div>
                        <h3 class="text-2xl font-black uppercase tracking-[0.2em] italic dark:text-white text-gray-900">
                            {{ level === 1 ? 'Facile' : level === 2 ? 'Intermédiaire' : 'Légendaire' }}
                        </h3>
                    </div>

                    <div class="grid gap-4 md:gap-6 md:grid-cols-2">
                        <div v-for="enigma in enigmas.filter(e => e.niveau === level)" :key="enigma.id" 
                            class="dark:bg-[#111113]/40 bg-white border dark:border-white/5 border-gray-200 p-5 md:p-8 rounded-[2rem] md:rounded-[2.5rem] group hover:border-[#FF9F1C]/40 transition-all duration-500 shadow-xl flex flex-col justify-between">
                            <div>
                                <div class="flex justify-between items-start mb-6 md:mb-8">
                                    <div class="text-[7px] md:text-[9px] font-black uppercase tracking-[0.2em] md:tracking-[0.3em] text-[#FF9F1C] dark:bg-[#FF9F1C]/10 bg-[#FF9F1C]/5 px-3 md:px-4 py-1 md:py-1.5 rounded-full border border-[#FF9F1C]/20">
                                        DATA-ID-{{ enigma.id.toString().padStart(4, '0') }}
                                    </div>
                                    <div class="flex gap-2 md:gap-3">
                                        <button @click="openEditForm(enigma)" 
                                            class="h-8 w-8 md:h-10 md:w-10 dark:bg-white/5 bg-gray-50 rounded-lg md:rounded-xl flex items-center justify-center hover:bg-[#FF9F1C] hover:text-black transition-all">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 md:w-4 md:h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
                                        </button>
                                        <button @click="confirmDelete(enigma)" 
                                            class="h-8 w-8 md:h-10 md:w-10 dark:bg-white/5 bg-gray-50 rounded-lg md:rounded-xl flex items-center justify-center hover:bg-red-500/20 hover:text-red-500 transition-all text-gray-400">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 md:w-4 md:h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                                        </button>
                                    </div>
                                </div>
                                
                                <p class="dark:text-gray-300 text-gray-700 font-bold italic mb-6 md:mb-8 leading-relaxed text-sm md:text-lg group-hover:text-white transition-colors">"{{ enigma.description }}"</p>
                            </div>
                            
                            <div class="flex items-center justify-between pt-6 md:pt-8 border-t dark:border-white/5 border-gray-100">
                                <div class="space-y-1">
                                    <p class="text-[7px] md:text-[8px] font-black uppercase text-gray-500 tracking-widest">CODE DE VALIDATION</p>
                                    <p class="text-xs md:text-sm font-black uppercase dark:text-[#FF9F1C] text-gray-900 tracking-tighter">{{ enigma.reponse }}</p>
                                </div>
                                <!-- Visualiseur d'artefacts (QCM placeholders) -->
                                <div v-if="enigma.mcq_options && enigma.mcq_options.length > 0" class="flex -space-x-2 md:-space-x-3">
                                    <div v-for="opt in 4" :key="opt" class="h-6 w-6 md:h-8 md:w-8 rounded-lg md:rounded-xl dark:bg-black/60 bg-gray-100 border-2 dark:border-[#111113] border-white flex items-center justify-center text-[8px] md:text-[10px] font-black text-gray-500 group-hover:border-[#FF9F1C]/30 transition-all">
                                        {{ opt }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- État vide pour un niveau spécifique -->
                        <div v-if="enigmas.filter(e => e.niveau === level).length === 0" 
                            class="md:col-span-2 py-10 md:py-16 text-center border-4 border-dashed dark:border-white/5 border-gray-100 rounded-[2rem] md:rounded-[3rem] flex flex-col items-center justify-center opacity-20">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 md:w-12 md:h-12 mb-3 md:mb-4 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            <p class="text-[10px] md:text-xs font-black uppercase tracking-[0.3em] md:tracking-[0.5em] dark:text-white text-gray-900">AUCUNE ÉNIGME DÉPLOYÉE</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
/* Animations Gaming Slide */
.gaming-slide-enter-active, .gaming-slide-leave-active {
    transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1);
}
.gaming-slide-enter-from, .gaming-slide-leave-to {
    opacity: 0;
    transform: scale(0.95) translateY(30px);
}

/* Transitions entre phases */
.step-fade-enter-active, .step-fade-leave-active {
    transition: all 0.4s ease;
}
.step-fade-enter-from {
    opacity: 0;
    transform: translateX(20px);
}
.step-fade-leave-to {
    opacity: 0;
    transform: translateX(-20px);
}
</style>

<style scoped>
.fade-enter-active, .fade-leave-active {
    transition: all 0.5s ease;
}
.fade-enter-from, .fade-leave-to {
    opacity: 0;
    transform: translateY(-20px);
}
</style>
