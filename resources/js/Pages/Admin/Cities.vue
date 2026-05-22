<script setup>
import AdminLayout from './AdminLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';

// Définition des propriétés reçues du contrôleur Laravel
const props = defineProps({
    cities: Array,
});

const toast = useToast();
const confirm = useConfirm();
const searchQuery = ref('');
const isEditing = ref(false);
const editingCityId = ref(null);
const loadingCityId = ref(null);
const statusLoadingId = ref(null);

// Fonction pour changer le statut d'une cité
const toggleStatus = (city) => {
    statusLoadingId.value = city.id;
    router.post(route('admin.cities.toggle', { city: city.id }), {}, {
        preserveScroll: true,
        onFinish: () => {
            statusLoadingId.value = null;
        },
    });
};

//Filtrage des cités en fonction de la requête de recherche
const filteredCities = computed(() => {
    if (!searchQuery.value) return props.cities;
    const query = searchQuery.value.toLowerCase();
    return props.cities.filter(city => 
        city.name.toLowerCase().includes(query) || 
        city.departement.toLowerCase().includes(query)
    );
});

//afficher ou masquer le formulaire de création cité
const showForm = ref(false);

// suivre l'étape actuelle du processus de création
const currentStep = ref(1);

// formulaire avec Inertia
const form = useForm({
    name: '',        
    description: '', 
    departement: '', 
});

// Fonction pour passer à l'étape suivante du processus de création cité
const nextStep = () => {
    if (currentStep.value < 3) currentStep.value++;
};

// Fonction pour revenir à l'étape précédente
const prevStep = () => {
    if (currentStep.value > 1) currentStep.value--;
};
// Fonction pour ouvrir le formulaire de création d'une nouvelle cité
const openCreateForm = () => {
    isEditing.value = false;
    editingCityId.value = null;
    form.reset();
    currentStep.value = 1;
    showForm.value = true;
};

// Fonction pour ouvrir le formulaire de modification d'une cité existante
const openEditForm = (city) => {
    isEditing.value = true;
    editingCityId.value = city.id;
    form.name = city.name;
    form.description = city.description;
    form.departement = city.departement;
    currentStep.value = 1;
    showForm.value = true;
};

// Fonction de soumission finale du formulaire vers le serveur
const submit = () => {
    if (isEditing.value) {
        form.post(route('admin.cities.update', { city: editingCityId.value }), {
            onSuccess: () => {
                showForm.value = false;
                form.reset();
                toast.add({ 
                    severity: 'success', 
                    summary: 'Matrice Mise à Jour', 
                    detail: 'La cité a été mise à jour avec succès.', 
                    life: 3000 
                });
            },
        });
    } else {
        form.post(route('admin.cities.store'), {
            onSuccess: () => {
                showForm.value = false;
                currentStep.value = 1;
                form.reset();
                toast.add({ 
                    severity: 'success', 
                    summary: 'Matrice Initialisée', 
                    detail: 'La cité a été déployée avec succès.', 
                    life: 3000 
                });
            },
        });
    }
};

//confirmation de supression
const confirmDelete = (city) => {
    confirm.require({
        message: `Voulez-vous vraiment supprimer la cité "${city.name}" ? Cette action est irréversible et supprimera tous les lieux associés.`,
        header: 'CONFIRMATION DE SUPPRESSION',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'SUPPRIMER',
        rejectLabel: 'ANNULER',
        acceptClass: 'p-button-danger',
        accept: () => {
            form.delete(route('admin.cities.delete', { city: city.id }), {
                onSuccess: () => {
                    toast.add({ 
                        severity: 'success', 
                        summary: 'Matrice Nettoyée', 
                        detail: 'La cité a été supprimée de la matrice.', 
                        life: 3000 
                    });
                },
            });
        }
    });
};

// Calcul du pourcentage de progression pour la barre de statut
const progressWidth = computed(() => {
    return ((currentStep.value - 1) / 2) * 100 + '%';
});
</script>

<template>
    <!-- Titre de la page dans l'onglet du navigateur -->
    <Head title="Matrice des Cités" />
    
    <AdminLayout>
        <div class="space-y-8 lg:space-y-12">
            <!-- Section d'en-tête principale -->
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                <div>
                    <!-- Titre principal avec style terminal/gaming -->
                    <h1 class="text-4xl lg:text-6xl font-black tracking-tighter uppercase italic leading-none dark:text-white text-gray-900">
                        UNIVERS DES <span class="text-[#2fc276]">CITÉS</span>
                    </h1>
                    <!-- Sous-titre descriptif -->
                    <p class="text-gray-500 font-bold uppercase tracking-[0.3em] text-[10px] mt-4">
                        INITIALISATION DES ZONES URBAINES
                    </p>
                </div>

                <!-- Barre de recherche -->
                <div class="relative w-full lg:w-96 group">
                    <div class="absolute inset-y-0 left-6 flex items-center pointer-events-none text-gray-500 group-focus-within:text-[#2fc276] transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    </div>
                    <input 
                        v-model="searchQuery"
                        type="text" 
                        placeholder="RECHERCHER UNE CITÉ..." 
                        class="w-full bg-white dark:bg-[#111113] border-2 dark:border-white/5 border-gray-200 rounded-2xl py-5 pl-16 pr-8 text-xs font-black uppercase tracking-widest focus:ring-0 focus:border-[#2fc276]/50 transition-all placeholder:text-gray-500/50"
                    />
                </div>

                <!-- Bouton d'action pour lancer ou annuler l'initialisation -->
                <button @click="showForm ? (showForm = false) : openCreateForm()" 
                    :class="showForm ? 'bg-red-500 shadow-lg text-white' : 'bg-[#2fc276] shadow-lg text-black'"
                    class="px-10 py-5 rounded-2xl font-black uppercase tracking-widest text-xs transition-all hover:scale-105 active:scale-95">
                    {{ showForm ? 'ANNULER L\'OPÉRATION' : 'INITIALISER UNE CITÉ' }}
                </button>
            </div>

            <!-- Processus d'initialisation interactif (Style Jeu Vidéo) -->
            <transition name="gaming-slide">
                <div v-if="showForm" class="dark:bg-[#111113] bg-white p-6 lg:p-12 rounded-[2.5rem] border dark:border-white/5 border-gray-200 shadow-2xl relative overflow-hidden">
                    
                    <!-- Barre de progression visuelle -->
                    <div class="absolute top-0 left-0 w-full h-1.5 bg-gray-100 dark:bg-white/5 overflow-hidden">
                        <div class="h-full bg-[#2fc276] transition-all duration-700 ease-out shadow-[0_0_15px_#2fc276]" :style="{ width: progressWidth }"></div>
                    </div>

                    <div class="relative z-10">
                        <!-- Étape 1 : Nom de la Cité -->
                        <transition name="step-fade" mode="out-in">
                            <div v-if="currentStep === 1" key="step1" class="space-y-8 py-4">
                                <div class="space-y-2">
                                    <span class="text-[10px] font-black text-[#2fc276] tracking-[0.5em] uppercase">Phase 01</span>
                                    <h3 class="text-3xl font-black uppercase italic tracking-tighter">INITIALISATION DE LA <span class="text-[#2fc276]">CITE</span></h3>
                                </div>
                                <div class="space-y-4">
                                    <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 ms-2">Désignation de la Cité</label>
                                    <input 
                                        v-model="form.name" 
                                        type="text" 
                                        autofocus
                                        class="w-full text-2xl lg:text-4xl dark:bg-black/40 bg-gray-50 border-none rounded-3xl py-8 px-8 focus:ring-4 focus:ring-[#2fc276]/20 dark:text-white text-gray-900 font-black italic tracking-tighter placeholder:opacity-20" 
                                        placeholder="EX: OUIDAH LA MYSTIQUE" 
                                    />
                                </div>
                                <button @click="nextStep" :disabled="!form.name" class="group flex items-center gap-4 bg-[#2fc276] text-black px-10 py-6 rounded-2xl font-black uppercase tracking-widest text-sm shadow-xl hover:scale-105 transition-all disabled:opacity-30">
                                    CONTINUER
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 group-hover:translate-x-2 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                                </button>
                            </div>

                            <!-- Étape 2 : Localisation -->
                            <div v-else-if="currentStep === 2" key="step2" class="space-y-8 py-4">
                                <div class="space-y-2">
                                    <span class="text-[10px] font-black text-[#2fc276] tracking-[0.5em] uppercase">Phase 02</span>
                                    <h3 class="text-3xl font-black uppercase italic tracking-tighter">DEPARTEMENT <span class="text-[#2fc276]">RATACHE</span></h3>
                                </div>
                                <div class="space-y-4">
                                    <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 ms-2">Département (Optionnel)</label>
                                    <input 
                                        v-model="form.departement" 
                                        type="text" 
                                        autofocus
                                        class="w-full text-2xl lg:text-4xl dark:bg-black/40 bg-gray-50 border-none rounded-3xl py-8 px-8 focus:ring-4 focus:ring-[#2fc276]/20 dark:text-white text-gray-900 font-black italic tracking-tighter placeholder:opacity-20" 
                                        placeholder="EX: ATLANTIQUE" 
                                    />
                                </div>
                                <div class="flex gap-4">
                                    <button @click="prevStep" class="px-8 py-6 rounded-2xl border-2 dark:border-white/10 border-gray-200 font-black uppercase tracking-widest text-xs hover:bg-white hover:text-black transition-all">RETOUR</button>
                                    <button @click="nextStep" class="group flex-1 flex items-center justify-center gap-4 bg-[#2fc276] text-black px-10 py-6 rounded-2xl font-black uppercase tracking-widest text-sm shadow-xl hover:scale-105 transition-all">
                                        CONTINUER
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 group-hover:translate-x-2 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Étape 3 : Narratif -->
                            <div v-else-if="currentStep === 3" key="step3" class="space-y-8 py-4">
                                <div class="space-y-2">
                                    <span class="text-[10px] font-black text-[#2fc276] tracking-[0.5em] uppercase">Phase 03</span>
                                    <h3 class="text-3xl font-black uppercase italic tracking-tighter">DESCRIPTION DE LA <span class="text-[#2fc276]">CITÉ</span></h3>
                                </div>
                                <div class="space-y-4">
                                    <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 ms-2">Séquence d'introduction (Description - Optionnelle)</label>
                                    <textarea 
                                        v-model="form.description" 
                                        rows="4"
                                        autofocus
                                        class="w-full text-xl lg:text-2xl dark:bg-black/40 bg-gray-50 border-none rounded-3xl py-8 px-8 focus:ring-4 focus:ring-[#2fc276]/20 dark:text-white text-gray-900 font-bold italic tracking-tight placeholder:opacity-20" 
                                        placeholder="L'HISTOIRE COMMENCE ICI..."
                                    ></textarea>
                                </div>
                                <div class="flex gap-4">
                                    <button @click="prevStep" class="px-8 py-6 rounded-2xl border-2 dark:border-white/10 border-gray-200 font-black uppercase tracking-widest text-xs hover:bg-white hover:text-black transition-all">RETOUR</button>
                                    <button @click="submit" :disabled="form.processing" class="group flex-1 flex items-center justify-center gap-4 bg-white text-black px-10 py-6 rounded-2xl font-black uppercase tracking-widest text-sm shadow-[0_0_30px_rgba(255,255,255,0.3)] hover:scale-105 transition-all disabled:opacity-30">
                                        INITIALISER LA CITE
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 animate-pulse" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v4"/><path d="m16.2 7.8 2.9-2.9"/><path d="M18 12h4"/><path d="m16.2 16.2 2.9 2.9"/><path d="M12 18v4"/><path d="m4.9 19.1 2.9-2.9"/><path d="M2 12h4"/><path d="m4.9 4.9 2.9 2.9"/></svg>
                                    </button>
                                </div>
                            </div>
                        </transition>
                    </div>

                    <!-- Éléments décoratifs style "HUD" -->
                    <!-- <div class="absolute bottom-4 right-8 text-[8px] font-black text-gray-600 uppercase tracking-[0.4em] animate-pulse">System Link: Stable</div> -->
                    <div class="absolute bottom-4 left-8 text-[8px] font-black text-gray-600 uppercase tracking-[0.4em]">Step {{ currentStep }} / 03</div>
                </div>
            </transition>

            <!-- Liste des Cités existantes -->
            <div v-if="filteredCities.length > 0" class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
                <div v-for="city in filteredCities" :key="city.id" 
                    class="dark:bg-[#111113]/40 bg-white border dark:border-white/5 border-gray-200 rounded-[2.5rem] overflow-hidden flex flex-col group hover:border-[#2fc276]/40 transition-all duration-500 shadow-xl hover:-translate-y-2">
                    <div class="p-8 space-y-6 flex-1">
                        <div class="flex justify-between items-start">
                            <!-- Remplacement de l'emoji par une icône SVG Pro -->
                            <div class="h-14 w-14 dark:bg-black/60 bg-gray-50 rounded-2xl flex items-center justify-center border dark:border-white/5 border-gray-200 group-hover:border-[#2fc276]/30 group-hover:text-[#2fc276] transition-all duration-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22V10a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v12"/><path d="M18 22H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h13a2 2 0 0 1 2 2v4.5"/><path d="M22 22V15a2 2 0 0 0-2-2h-2"/><rect width="4" height="4" x="6" y="12" rx="1"/><rect width="4" height="4" x="10" y="12" rx="1"/></svg>
                            </div>
                            <div class="flex flex-col items-end gap-2">
                                <span class="bg-[#2fc276]/10 text-[#2fc276] px-4 py-1.5 rounded-full text-[8px] font-black uppercase tracking-widest border border-[#2fc276]/20">
                                    {{ city.places_count }} SECTEURS DÉPLOYÉS
                                </span>
                                <div class="flex gap-2">
                                    <button @click.stop="toggleStatus(city)" 
                                        :disabled="statusLoadingId === city.id"
                                        :class="city.status === 'active' ? 'bg-green-500/10 text-green-500 border-green-500/20 hover:bg-green-500 hover:text-white' : 'bg-gray-500/10 text-gray-500 border-gray-500/20 hover:bg-gray-500 hover:text-white'"
                                        class="p-2 rounded-lg border transition-all disabled:opacity-50"
                                        :title="city.status === 'active' ? 'Désactiver' : 'Activer'">
                                        <svg v-if="statusLoadingId === city.id" class="animate-spin h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/><path d="m9 12 2 2 4-4"/></svg>
                                    </button>
                                    <button @click.stop="openEditForm(city)" class="p-2 rounded-lg bg-blue-500/10 text-blue-500 border border-blue-500/20 hover:bg-blue-500 hover:text-white transition-all">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
                                    </button>
                                    <button @click.stop="confirmDelete(city)" :disabled="form.processing" class="p-2 rounded-lg bg-red-500/10 text-red-500 border border-red-500/20 hover:bg-red-500 hover:text-white transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div>
                            <h3 class="text-2xl font-black uppercase italic tracking-tighter dark:text-white text-gray-900 mb-2 group-hover:text-[#2fc276] transition-colors">{{ city.name }}</h3>
                            <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-4 italic">{{ city.departement }}</p>
                            <p class="text-xs dark:text-gray-400 text-gray-600 line-clamp-3 leading-relaxed font-medium">
                                {{ city.description }}
                            </p>
                        </div>
                    </div>
                    <div class="p-2">
                        <Link :href="route('admin.cities.places', { city: city.id })" 
                            @click="loadingCityId = city.id"
                            :class="{ 'opacity-50 pointer-events-none cursor-not-allowed': loadingCityId === city.id }"
                            class="w-full flex items-center justify-center gap-3 bg-gray-100 dark:bg-white/5 dark:text-white text-gray-700 py-6 rounded-[1.8rem] text-[10px] font-black uppercase tracking-widest hover:bg-[#2fc276] hover:text-black transition-all group-hover:shadow-[0_0_20px_rgba(47,194,118,0.2)]">
                            <template v-if="loadingCityId === city.id">
                                <svg class="animate-spin h-4 w-4 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                CHARGEMENT...
                            </template>
                            <template v-else>
                                ACCÉDER AUX LIEUX
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 group-hover:translate-x-1 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                            </template>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- État vide pour la recherche -->
            <div v-else class="py-20 text-center space-y-6">
                <div class="h-24 w-24 mx-auto dark:bg-white/5 bg-gray-50 rounded-full flex items-center justify-center text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                </div>
                <div>
                    <h3 class="text-xl font-black uppercase italic tracking-tighter">AUCUNE CITÉ DÉTECTÉE</h3>
                    <!-- <p class="text-gray-500 font-bold uppercase tracking-widest text-[10px] mt-2">Ajustez les paramètres de recherche du radar</p> -->
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
/* Animations style Gaming */
.gaming-slide-enter-active, .gaming-slide-leave-active {
    transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1);
}
.gaming-slide-enter-from, .gaming-slide-leave-to {
    opacity: 0;
    transform: scale(0.95) translateY(30px);
}

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

/* Style des inputs focus type Gaming */
input:focus, textarea:focus {
    outline: none;
    box-shadow: 0 0 0 4px rgba(47, 194, 118, 0.1);
}
</style>
