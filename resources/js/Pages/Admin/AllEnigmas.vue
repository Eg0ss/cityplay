<script setup>
import AdminLayout from './AdminLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
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
                    <h1 class="text-4xl lg:text-6xl font-black tracking-tighter uppercase italic leading-none dark:text-white text-gray-900">
                        MATRICE <span class="text-[#FF9F1C]">ÉNIGMES</span>
                    </h1>
                    <p class="text-gray-500 font-bold uppercase tracking-[0.3em] text-[10px] mt-4">
                        LISTE INTÉGRALE DES DÉFIS DÉPLOYÉS SUR LE RÉSEAU
                    </p>
                </div>

                <div class="flex flex-col md:flex-row items-center gap-4 w-full lg:w-auto">
                    <!-- Bouton Créer une énigme -->
                    <button @click="showCreateModal = true" 
                        class="w-full md:w-auto bg-[#FF9F1C] text-black px-8 py-4 rounded-2xl font-black uppercase tracking-widest text-[10px] shadow-lg hover:scale-105 active:scale-95 transition-all">
                        FORGER UNE ÉNIGME
                    </button>

                    <!-- Filtre par Lieu -->
                    <div class="w-full md:w-64">
                        <select 
                            v-model="selectedPlaceFilter" 
                            class="w-full bg-white dark:bg-[#111113] border-2 dark:border-white/5 border-gray-100 rounded-2xl py-4 px-6 text-xs font-black uppercase tracking-widest focus:ring-0 focus:border-[#FF9F1C]/50 transition-all appearance-none cursor-pointer"
                        >
                            <option value="">TOUS LES LIEUX</option>
                            <option v-for="place in places" :key="place.id" :value="place.id">
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
                            class="w-full bg-white dark:bg-[#111113] border-2 dark:border-white/5 border-gray-100 rounded-2xl py-5 pl-16 pr-8 text-xs font-black uppercase tracking-widest focus:ring-0 focus:border-[#FF9F1C]/50 transition-all placeholder:text-gray-500/50"
                        />
                    </div>
                </div>
            </div>

            <!-- Modal de sélection de lieu pour création -->
            <transition name="step-fade">
                <div v-if="showCreateModal" class="fixed inset-0 z-[100] flex items-center justify-center p-6 bg-black/80 backdrop-blur-md">
                    <div class="w-full max-w-xl dark:bg-[#111113] bg-white rounded-[2.5rem] p-10 border dark:border-white/5 border-gray-100 shadow-2xl relative">
                        <button @click="showCreateModal = false" class="absolute top-8 right-8 text-gray-500 hover:text-white transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                        </button>
                        
                        <div class="space-y-8">
                            <div class="space-y-2">
                                <span class="text-[10px] font-black text-[#FF9F1C] tracking-[0.5em] uppercase">Initialisation</span>
                                <h3 class="text-3xl font-black uppercase italic tracking-tighter">CIBLER UNE <span class="text-[#FF9F1C]">UNITÉ</span></h3>
                                <p class="text-gray-500 font-bold uppercase tracking-widest text-[10px]">Sélectionnez le lieu où déployer ce nouveau défi.</p>
                            </div>

                            <div class="space-y-4">
                                <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 ms-2">Lieu de Déploiement</label>
                                <select v-model="selectedPlaceFilter"
                                    class="w-full bg-gray-50 dark:bg-black/40 border-none rounded-2xl py-6 px-8 text-sm font-black uppercase tracking-widest focus:ring-4 focus:ring-[#FF9F1C]/20 transition-all cursor-pointer appearance-none">
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

            <!-- Liste des énigmes -->
            <div v-if="filteredEnigmas.length > 0" class="grid gap-6">
                <div v-for="enigma in filteredEnigmas" :key="enigma.id" 
                    class="dark:bg-[#111113]/40 bg-white border dark:border-white/5 border-gray-200 p-8 rounded-[2.5rem] group hover:border-[#FF9F1C]/40 transition-all duration-500 shadow-xl">
                    <div class="flex flex-col lg:flex-row justify-between gap-6">
                        <div class="flex-1 space-y-4">
                            <div class="flex flex-wrap items-center gap-3">
                                <div :class="[enigma.niveau===1 ? 'bg-blue-500/10 text-blue-500 border-blue-500/20' : enigma.niveau===2 ? 'bg-yellow-500/10 text-yellow-500 border-yellow-500/20' : 'bg-red-500/10 text-red-500 border-red-500/20']" 
                                    class="px-4 py-1.5 rounded-full text-[9px] font-black uppercase tracking-[0.2em] border">
                                    LVL {{ enigma.niveau }} - {{ enigma.niveau === 1 ? 'FACILE' : enigma.niveau === 2 ? 'INTERMÉDIAIRE' : 'LÉGENDAIRE' }}
                                </div>
                                <span class="text-[10px] font-black text-gray-500 uppercase tracking-widest">
                                    LIEU : <span class="dark:text-white text-gray-900">{{ enigma.place?.nom }}</span>
                                </span>
                            </div>
                            
                            <p class="dark:text-gray-300 text-gray-700 font-bold italic leading-relaxed text-lg">"{{ enigma.description }}"</p>
                            
                            <div class="flex items-center gap-8 pt-4 border-t dark:border-white/5 border-gray-100">
                                <div class="space-y-1">
                                    <p class="text-[8px] font-black uppercase text-gray-500 tracking-widest">CODE DE VALIDATION</p>
                                    <p class="text-sm font-black uppercase dark:text-[#FF9F1C] text-gray-900 tracking-tighter">{{ enigma.reponse }}</p>
                                </div>
                                <div v-if="enigma.images && enigma.images.length > 0" class="flex items-center gap-2">
                                    <p class="text-[8px] font-black uppercase text-gray-500 tracking-widest mr-2">ARTEFACTS</p>
                                    <div class="flex -space-x-2">
                                        <div v-for="(img, idx) in enigma.images" :key="idx" class="h-8 w-8 rounded-lg border-2 dark:border-[#111113] border-white overflow-hidden bg-black/40">
                                            <img :src="'/storage/' + img.image_path" class="w-full h-full object-cover" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex lg:flex-col items-center justify-center gap-3">
                            <Link :href="route('admin.enigmas', { place: enigma.place_id })" 
                                class="h-14 w-14 dark:bg-white/5 bg-gray-50 rounded-2xl flex items-center justify-center hover:bg-[#FF9F1C] hover:text-black transition-all group shadow-lg"
                                title="Éditer dans l'atelier">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 group-hover:scale-110 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
                            </Link>
                            <button @click="confirmDelete(enigma)" 
                                class="h-14 w-14 dark:bg-white/5 bg-gray-50 rounded-2xl flex items-center justify-center hover:bg-red-500/20 hover:text-red-500 transition-all group shadow-lg text-gray-400"
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
