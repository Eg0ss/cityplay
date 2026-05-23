<script setup>
import AdminLayout from './AdminLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';

const props = defineProps({
    enigmas: {
        type: Array,
        default: () => []
    },
    places: {
        type: Array,
        default: () => []
    },
});

const toast = useToast();
const confirm = useConfirm();
const searchQuery = ref('');
const selectedPlaceFilter = ref('');
const showCreateModal = ref(false);
const showEditModal = ref(false);
const loadingEnigmaId = ref(null);
const editingEnigmaId = ref(null);

const form = useForm({
    niveau: 1,
    description: '',
    reponse: '',
    mcq_options: ['', '', '', ''],
    hint_keyword: '',
});

const openEditModal = (enigma) => {
    editingEnigmaId.value = enigma.id;
    form.niveau = enigma.niveau;
    form.description = enigma.description;
    form.reponse = enigma.reponse;
    form.mcq_options = enigma.mcq_options || ['', '', '', ''];
    
    // Charger le mot-clé d'indice si présent
    form.hint_keyword = '';
    if (enigma.hints) {
        const keywordHint = enigma.hints.find(h => h.type === 'keyword');
        if (keywordHint) form.hint_keyword = keywordHint.content;
    }
    
    showEditModal.value = true;
};

const submitEdit = () => {
    form.post(route('admin.enigmas.update', { enigma: editingEnigmaId.value }), {
        onSuccess: () => {
            showEditModal.value = false;
            form.reset();
            toast.add({ 
                severity: 'success', 
                summary: 'Matrice Mise à Jour', 
                detail: 'L\'énigme a été mise à jour avec succès.', 
                life: 3000 
            });
        },
    });
};

// Surveillance du niveau pour adapter les options QCM
watch(() => form.niveau, (newVal) => {
    if (newVal === 3) {
        form.mcq_options = [];
    } else if (form.mcq_options.length === 0) {
        form.mcq_options = ['', '', '', ''];
    }
});

const openCreateForPlace = () => {
    if (!selectedPlaceFilter.value) {
        toast.add({ 
            severity: 'warn', 
            summary: 'Lieu non sélectionné', 
            detail: 'Veuillez sélectionner un lieu pour forger une énigme.', 
            life: 3000 
        });
        return;
    }
    router.visit(route('admin.enigmas', { place: selectedPlaceFilter.value }));
};

const filteredEnigmas = computed(() => {
    let result = [...props.enigmas];

    if (selectedPlaceFilter.value) {
        result = result.filter(enigma => enigma.place_id == selectedPlaceFilter.value);
    }

    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(enigma => 
            enigma.description.toLowerCase().includes(query) || 
            enigma.reponse.toLowerCase().includes(query) ||
            (enigma.place && enigma.place.nom.toLowerCase().includes(query))
        );
    }

    return result;
});

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
    <Head title="Matrice des Énigmes" />
    
    <AdminLayout>
        <div class="space-y-8 lg:space-y-12">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                <div>
                    <h1 class="text-4xl lg:text-6xl font-black tracking-tighter uppercase italic leading-none text-[#FF9F1C]">
                        UNIVERS <span class="text-[#2fc276]">ÉNIGMES</span>
                    </h1>
                    <p class="text-gray-500 font-bold uppercase tracking-[0.3em] text-[10px] mt-4 flex items-center gap-2">
                        <span class="h-2 w-2 rounded-full bg-[#2fc276] animate-pulse"></span>
                        Cityplay Bénin
                    </p>
                </div>

                <div class="flex flex-col md:flex-row items-center gap-4 w-full lg:w-auto">
                    <!-- Bouton Créer une énigme -->
                    <button @click="showCreateModal = true" 
                        class="w-full md:w-auto bg-[#2fc276] text-black px-8 py-4 rounded-2xl font-black uppercase tracking-widest text-[10px] shadow-lg hover:scale-105 active:scale-95 transition-all">
                        FORGER UNE ÉNIGME
                    </button>

                    <!-- Filtre par Lieu -->
                    <div class="w-full md:w-64">
                        <select 
                            v-model="selectedPlaceFilter" 
                            class="w-full bg-white dark:bg-[#111113] border-2 dark:border-white/5 border-gray-100 rounded-2xl py-4 px-6 text-xs font-black uppercase tracking-widest focus:ring-0 focus:border-[#FF9F1C]/50 transition-all appearance-none cursor-pointer text-gray-900 dark:text-white"
                        >
                            <option value="" class="dark:bg-[#111113] dark:text-white">TOUS LES LIEUX</option>
                            <option v-for="place in places" :key="place.id" :value="place.id" class="dark:bg-[#111113] dark:text-white">
                                {{ place.nom.toUpperCase() }}
                            </option>
                        </select>
                    </div>

                    <!-- Barre de recherche -->
                    <div class="relative w-full lg:w-80 group">
                        <div class="absolute inset-y-0 left-6 flex items-center pointer-events-none text-gray-500 group-focus-within:text-[#FF9F1C] transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                        </div>
                        <input 
                            v-model="searchQuery"
                            type="text" 
                            placeholder="RECHERCHER..." 
                            class="w-full bg-white dark:bg-[#111113] border-2 dark:border-white/5 border-gray-100 rounded-2xl py-5 pl-16 pr-8 text-xs font-black uppercase tracking-widest focus:ring-0 focus:border-[#FF9F1C]/50 transition-all placeholder:text-gray-500/50 text-gray-900 dark:text-white"
                        />
                    </div>
                </div>
            </div>

            <!-- Modal de sélection de lieu pour création -->
            <transition name="step-fade">
                <div v-if="showCreateModal" class="fixed inset-0 z-[100] flex items-center justify-center p-6 bg-black/80 backdrop-blur-md">
                    <div class="w-full max-w-xl dark:bg-[#1c183a] bg-white rounded-[2.5rem] p-10 border dark:border-white/10 border-gray-100 shadow-2xl relative">
                        <button @click="showCreateModal = false" class="absolute top-8 right-8 text-gray-500 hover:text-white transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                        </button>
                        
                        <div class="space-y-8">
                            <div class="space-y-2">
                                <span class="text-[10px] font-black text-[#FF9F1C] tracking-[0.5em] uppercase">Initialisation</span>
                                <h3 class="text-3xl font-black uppercase italic tracking-tighter dark:text-white">CIBLER UNE <span class="text-[#FF9F1C]">UNITÉ</span></h3>
                                <p class="text-gray-500 font-bold uppercase tracking-widest text-[10px]">Sélectionnez le lieu où déployer ce nouveau défi.</p>
                            </div>

                            <div class="space-y-4">
                                <label class="block text-[10px] font-black uppercase tracking-[0.2em] dark:text-gray-400 text-gray-500 ms-2">Lieu de Déploiement</label>
                                <select v-model="selectedPlaceFilter"
                                    class="w-full bg-gray-50 dark:bg-[#10101c]/60 border-none rounded-2xl py-6 px-8 text-sm font-black uppercase tracking-widest focus:ring-4 focus:ring-[#FF9F1C]/20 transition-all cursor-pointer appearance-none dark:text-white">
                                    <option value="" disabled>SÉLECTIONNEZ UN LIEU...</option>
                                    <option v-for="place in places" :key="place.id" :value="place.id">
                                        {{ place.nom.toUpperCase() }}
                                    </option>
                                </select>
                            </div>

                            <button @click="openCreateForPlace" :disabled="!selectedPlaceFilter"
                                class="w-full bg-white text-black py-6 rounded-2xl font-black uppercase tracking-[0.3em] text-xs shadow-xl hover:scale-[1.02] transition-all disabled:opacity-30">
                                OUVRIR L'ATELIER DE FORGE
                            </button>
                        </div>
                    </div>
                </div>
            </transition>

            <!-- Modal d'édition d'énigme -->
            <transition name="step-fade">
                <div v-if="showEditModal" class="fixed inset-0 z-[100] flex items-center justify-center p-6 bg-black/80 backdrop-blur-md">
                    <div class="w-full max-w-2xl dark:bg-[#1c183a] bg-white rounded-[2.5rem] p-10 border dark:border-white/10 border-gray-100 shadow-2xl relative overflow-y-auto max-h-[90vh]">
                        <button @click="showEditModal = false" class="absolute top-8 right-8 text-gray-500 hover:text-white transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                        </button>
                        
                        <div class="space-y-8">
                            <div class="space-y-2">
                                <span class="text-[10px] font-black text-[#FF9F1C] tracking-[0.5em] uppercase">Mise à jour</span>
                                <h3 class="text-3xl font-black uppercase italic tracking-tighter dark:text-white">ÉDITER L'<span class="text-[#FF9F1C]">ÉNIGME</span></h3>
                                <p class="text-gray-500 font-bold uppercase tracking-widest text-[10px]">Modifiez les paramètres de ce défi.</p>
                            </div>

                            <form @submit.prevent="submitEdit" class="space-y-6">
                                <div class="grid grid-cols-2 gap-6">
                                    <!-- Niveau -->
                                    <div class="space-y-2">
                                        <label class="block text-[10px] font-black uppercase tracking-[0.2em] dark:text-gray-400 text-gray-500 ms-2">Niveau de difficulté</label>
                                        <select v-model="form.niveau" class="w-full bg-gray-50 dark:bg-[#10101c]/60 border-none rounded-2xl py-4 px-6 text-sm font-black uppercase tracking-widest focus:ring-4 focus:ring-[#FF9F1C]/20 transition-all dark:text-white">
                                            <option :value="1">LVL 1 - FACILE</option>
                                            <option :value="2">LVL 2 - INTERMÉDIAIRE</option>
                                            <option :value="3">LVL 3 - LÉGENDAIRE</option>
                                        </select>
                                    </div>
                                    <!-- Réponse -->
                                    <div class="space-y-2">
                                        <label class="block text-[10px] font-black uppercase tracking-[0.2em] dark:text-gray-400 text-gray-500 ms-2">Réponse attendue</label>
                                        <input v-model="form.reponse" type="text" class="w-full bg-gray-50 dark:bg-[#10101c]/60 border-none rounded-2xl py-4 px-6 text-sm font-black uppercase tracking-widest focus:ring-4 focus:ring-[#FF9F1C]/20 transition-all dark:text-white" />
                                    </div>
                                </div>

                                <!-- Description -->
                                <div class="space-y-2">
                                    <label class="block text-[10px] font-black uppercase tracking-[0.2em] dark:text-gray-400 text-gray-500 ms-2">Énoncé de l'énigme</label>
                                    <textarea v-model="form.description" rows="4" class="w-full bg-gray-50 dark:bg-[#10101c]/60 border-none rounded-2xl py-4 px-6 text-sm font-bold italic tracking-tight focus:ring-4 focus:ring-[#FF9F1C]/20 transition-all dark:text-white"></textarea>
                                </div>

                                <!-- Options QCM (Seulement LVL 1 & 2) -->
                                <div v-if="form.niveau < 3" class="space-y-4">
                                    <label class="block text-[10px] font-black uppercase tracking-[0.2em] dark:text-gray-400 text-gray-500 ms-2">Options QCM (Fausse pistes)</label>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div v-for="(opt, index) in form.mcq_options" :key="index" class="relative">
                                            <input v-model="form.mcq_options[index]" type="text" :placeholder="'Option ' + (index + 1)" class="w-full bg-gray-50 dark:bg-[#10101c]/60 border-none rounded-xl py-3 px-4 text-xs font-black uppercase tracking-widest focus:ring-4 focus:ring-[#FF9F1C]/20 transition-all dark:text-white" />
                                        </div>
                                    </div>
                                </div>

                                <!-- Mot-clé d'indice -->
                                <div class="space-y-2">
                                    <label class="block text-[10px] font-black uppercase tracking-[0.2em] dark:text-gray-400 text-gray-500 ms-2">Mot-clé d'indice (Optionnel)</label>
                                    <input v-model="form.hint_keyword" type="text" placeholder="EX: HISTOIRE" class="w-full bg-gray-50 dark:bg-[#10101c]/60 border-none rounded-2xl py-4 px-6 text-sm font-black uppercase tracking-widest focus:ring-4 focus:ring-[#FF9F1C]/20 transition-all dark:text-white" />
                                </div>

                                <div class="flex gap-4 pt-4">
                                    <button type="button" @click="showEditModal = false" class="flex-1 px-8 py-5 rounded-2xl border-2 dark:border-white/10 border-gray-200 font-black uppercase tracking-widest text-xs dark:text-white hover:bg-gray-100 dark:hover:bg-white/5 transition-all">ANNULER</button>
                                    <button type="submit" :disabled="form.processing" class="flex-[2] dark:bg-[#2fc276] bg-[#FF9F1C] text-black px-10 py-5 rounded-2xl font-black uppercase tracking-widest text-xs shadow-xl hover:scale-[1.02] active:scale-95 transition-all disabled:opacity-50 flex items-center justify-center gap-3">
                                        <svg v-if="form.processing" class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        {{ form.processing ? 'MISE À JOUR...' : 'SAUVEGARDER LES MODIFICATIONS' }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </transition>

            <!-- Liste des énigmes -->
            <div v-if="filteredEnigmas.length > 0" class="grid gap-6">
                <div v-for="enigma in filteredEnigmas" :key="enigma.id" 
                    class="dark:bg-[#1c183a]/40 bg-white border dark:border-white/10 border-gray-200 p-8 rounded-[2.5rem] group hover:border-[#FF9F1C]/40 transition-all duration-500 shadow-xl">
                    <div class="flex flex-col lg:flex-row justify-between gap-6">
                        <div class="flex-1 space-y-4">
                            <div class="flex flex-wrap items-center gap-3">
                                <div :class="[enigma.niveau===1 ? 'bg-blue-500/10 text-blue-500 border-blue-500/20' : enigma.niveau===2 ? 'bg-yellow-500/10 text-yellow-500 border-yellow-500/20' : 'bg-red-500/10 text-red-500 border-red-500/20']" 
                                    class="px-4 py-1.5 rounded-full text-[9px] font-black uppercase tracking-[0.2em] border">
                                    LVL {{ enigma.niveau }} - {{ enigma.niveau === 1 ? 'FACILE' : enigma.niveau === 2 ? 'INTERMÉDIAIRE' : 'LÉGENDAIRE' }}
                                </div>
                                <span class="text-[10px] font-black dark:text-gray-400 text-gray-500 uppercase tracking-widest">
                                    LIEU : <span class="dark:text-white text-gray-900">{{ enigma.place?.nom }}</span>
                                </span>
                            </div>
                            
                            <p class="dark:text-gray-300 text-gray-700 font-bold italic leading-relaxed text-lg">"{{ enigma.description }}"</p>
                            
                            <div class="flex items-center gap-8 pt-4 border-t dark:border-white/10 border-gray-100">
                                <div class="space-y-1">
                                    <p class="text-[8px] font-black uppercase dark:text-gray-400 text-gray-500 tracking-widest">CODE DE VALIDATION</p>
                                    <p class="text-sm font-black uppercase dark:text-[#FF9F1C] text-gray-900 tracking-tighter">{{ enigma.reponse }}</p>
                                </div>
                                <div v-if="enigma.images && enigma.images.length > 0" class="flex items-center gap-2">
                                    <p class="text-[8px] font-black uppercase dark:text-gray-400 text-gray-500 tracking-widest mr-2">ARTEFACTS</p>
                                    <div class="flex -space-x-2">
                                        <div v-for="(img, idx) in enigma.images" :key="idx" class="h-8 w-8 rounded-lg border-2 dark:border-[#1c183a] border-white overflow-hidden bg-black/40">
                                            <img :src="'/storage/' + img.image_path" class="w-full h-full object-cover" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex lg:flex-col items-center justify-center gap-3">
                            <button @click="openEditModal(enigma)" 
                                class="h-14 w-14 bg-blue-50 border border-blue-100 dark:bg-blue-500/10 dark:border-blue-500/50 rounded-2xl flex items-center justify-center text-blue-600 dark:text-blue-400 hover:bg-blue-600 hover:text-white dark:hover:bg-blue-500 dark:hover:text-white transition-all group shadow-sm"
                                title="Éditer l'énigme">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 group-hover:scale-110 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
                            </button>
                            <button @click="confirmDelete(enigma)" 
                                class="h-14 w-14 bg-red-50 border border-red-100 dark:bg-red-500/10 dark:border-red-500/50 rounded-2xl flex items-center justify-center text-red-600 dark:text-red-400 hover:bg-red-600 hover:text-white dark:hover:bg-red-500 dark:hover:text-white transition-all group shadow-sm"
                                title="Supprimer">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- État vide -->
            <div v-else class="py-20 text-center space-y-6">
                <div class="h-24 w-24 mx-auto dark:bg-white/5 bg-gray-50 rounded-full flex items-center justify-center text-gray-400 border dark:border-white/5 border-gray-100 shadow-inner">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                </div>
                <div class="max-w-md mx-auto">
                    <h3 class="text-2xl font-black uppercase italic tracking-tighter">AUCUN DÉFI DÉTECTÉ</h3>
                    <p class="text-gray-500 font-bold uppercase tracking-widest text-[10px] mt-2 leading-relaxed">
                        LE RADAR NE TROUVE AUCUNE ÉNIGME CORRESPONDANTE DANS LA MATRICE.
                    </p>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
