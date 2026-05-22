<script setup>
// Importation du layout de base pour l'administration
import AdminLayout from './AdminLayout.vue';
// Importation des utilitaires Inertia pour la gestion du head, des liens et des formulaires
import { Head, Link, useForm, router } from '@inertiajs/vue3';
// Importation des hooks Vue pour la réactivité, les cycles de vie et le prochain tick
import { ref, onMounted, nextTick, watch, computed, onUnmounted } from 'vue';
import axios from 'axios';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';

// Définition des propriétés reçues du contrôleur (Cité parente et liste des lieux)
const props = defineProps({
    city: Object,
    places: Array,
});

const toast = useToast();
const confirm = useConfirm();
const searchQuery = ref('');
const isEditing = ref(false);
const editingPlaceId = ref(null);
const loadingPlaceId = ref(null);

const filteredPlaces = computed(() => {
    if (!searchQuery.value) return props.places;
    const query = searchQuery.value.toLowerCase();
    return props.places.filter(place => 
        place.nom.toLowerCase().includes(query) || 
        (place.verified_description && place.verified_description.toLowerCase().includes(query))
    );
});

// État pour afficher ou masquer le processus d'initialisation
const showForm = ref(false);

// Définition du formulaire avec Inertia useForm
const form = useForm({
    nom: '',                  // Nom du lieu/secteur
    image: null,              // Image du lieu
    verified_description: '', // Description narrative
    lat: 6.3667,              // Latitude par défaut (Cotonou)
    lng: 2.4333,              // Longitude par défaut
    rayon_marge: 5,           // Rayon de détection par défaut
    marge_validation_gps: 10, // Marge de validation GPS (en mètres)
});

// Références pour la gestion de la carte Leaflet
const map = ref(null);
const marker = ref(null);
const suggestions = ref([]);
const isSearching = ref(false);
const imagePreview = ref(null);

// Gestion du changement de fichier image
const onFileChange = (e) => {
    const file = e.target.files[0];
    form.image = file;
    
    // Nettoyer l'ancienne URL pour éviter les fuites de mémoire
    if (imagePreview.value) URL.revokeObjectURL(imagePreview.value);
    
    if (file) {
        imagePreview.value = URL.createObjectURL(file);
    } else {
        imagePreview.value = null;
    }
};

onUnmounted(() => {
    if (imagePreview.value) URL.revokeObjectURL(imagePreview.value);
});

/**
 * Fonction de géocodage pour obtenir des suggestions
 */
let debounceTimeout = null;
const searchLocation = () => {
    if (debounceTimeout) clearTimeout(debounceTimeout);
    
    debounceTimeout = setTimeout(async () => {
        if (!form.nom || form.nom.length < 3) {
            suggestions.value = [];
            return;
        }
        
        isSearching.value = true;
        try {
            const query = `${form.nom}, ${props.city.name}, Bénin`;
            const response = await axios.get(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=5&addressdetails=1`);
            
            suggestions.value = response.data.map(item => ({
                display_name: item.display_name,
                name: item.name || item.display_name.split(',')[0],
                lat: item.lat,
                lon: item.lon
            }));
        } catch (error) {
            console.error('Erreur lors de la recherche de lieu:', error);
        } finally {
            isSearching.value = false;
        }
    }, 800);
};

/**
 * Sélectionner une suggestion
 */
const selectSuggestion = (suggestion) => {
    form.nom = suggestion.name;
    form.lat = parseFloat(suggestion.lat).toFixed(6);
    form.lng = parseFloat(suggestion.lon).toFixed(6);
    suggestions.value = [];
    
    if (map.value) {
        const newPos = [parseFloat(suggestion.lat), parseFloat(suggestion.lon)];
        map.value.setView(newPos, 17);
        if (marker.value) {
            marker.value.setLatLng(newPos);
        }
    }
};

/**
 * Géocodage inversé (obtenir le nom à partir des coordonnées)
 */
const reverseGeocode = async (lat, lng) => {
    try {
        const response = await axios.get(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`);
        if (response.data && response.data.display_name) {
            // On prend le nom le plus précis possible
            const addr = response.data.address;
            form.nom = addr.amenity || addr.building || addr.shop || addr.tourism || addr.historic || addr.road || response.data.name || response.data.display_name.split(',')[0];
        }
    } catch (error) {
        console.error('Erreur lors du géocodage inversé:', error);
    }
};

// Surveillance du nom pour la recherche
let searchTimeout = null
watch(() => form.nom, (newVal, oldVal) => {
    // Si le champ est vide, on vide les suggestions immédiatement
    if (!newVal) {
        suggestions.value = [];
        return;
    }
    
    //On annule la recherche précédente si l'utilisateur continue de taper
    clearTimeout(searchTimeout);

    //On attend 500ms de calme avant de lancer la recherche
    searchTimeout = setTimeout(() => {
        if (showForm.value && !isSearching.value) {
            searchLocation();
        }
    }, 500);
});

// Surveillance des coordonnées pour mettre à jour la carte manuellement
watch([() => form.lat, () => form.lng], ([newLat, newLng]) => {
    if (map.value && marker.value && !debounceTimeout) {
        const pos = [parseFloat(newLat), parseFloat(newLng)];
        if (!isNaN(pos[0]) && !isNaN(pos[1])) {
            marker.value.setLatLng(pos);
            // On ne pan que si on est loin du centre actuel pour éviter les saccades
            const center = map.value.getCenter();
            const dist = Math.sqrt(Math.pow(center.lat - pos[0], 2) + Math.pow(center.lng - pos[1], 2));
            if (dist > 0.001) {
                map.value.panTo(pos);
            }
        }
    }
});

//Initialisation de la carte interactive pour la sélection GPS
const initMap = () => {
    // S'assurer que Leaflet est chargé (via le CDN dans le template)
    if (typeof L === 'undefined') {
        setTimeout(initMap, 100);
        return;
    }

    // S'assurer que l'élément DOM existe
    const mapContainer = document.getElementById('map-selector');
    if (!mapContainer) return;

    // Éviter la ré-initialisation si la carte existe déjà
    if (map.value) {
        map.value.invalidateSize();
        return;
    }

    const initialPos = [parseFloat(form.lat), parseFloat(form.lng)];

    // Création de l'instance Leaflet
    map.value = L.map('map-selector', {
        zoomControl: true,
        scrollWheelZoom: true
    }).setView(initialPos, 13);

    // Ajout de la couche de tuiles OpenStreetMap
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map.value);

    // Création du marqueur déplaçable
    marker.value = L.marker(initialPos, { draggable: true }).addTo(map.value);

    // Mise à jour des coordonnées lors du déplacement du marqueur
    marker.value.on('dragend', async (e) => {
        const { lat, lng } = e.target.getLatLng();
        form.lat = parseFloat(lat).toFixed(6);
        form.lng = parseFloat(lng).toFixed(6);
        await reverseGeocode(lat, lng);
    });

    // Mise à jour du marqueur lors d'un clic sur la carte
    map.value.on('click', async (e) => {
        const { lat, lng } = e.latlng;
        marker.value.setLatLng([lat, lng]);
        form.lat = parseFloat(lat).toFixed(6);
        form.lng = parseFloat(lng).toFixed(6);
        await reverseGeocode(lat, lng);
    });

    // Forcer le redimensionnement après un court délai
    setTimeout(() => {
        if (map.value) map.value.invalidateSize();
    }, 500);
};

// Surveillance de l'affichage du formulaire pour initialiser la carte au bon moment
watch(showForm, (newVal) => {
    if (newVal) {
        nextTick(() => {
            initMap();
        });
        window.addEventListener('click', handleOutsideClick);
    } else {
        window.removeEventListener('click', handleOutsideClick);
    }
});

/**
 * Fermer les suggestions lors d'un clic à l'extérieur
 */
const handleOutsideClick = (e) => {
    const searchContainer = document.querySelector('.search-container');
    if (searchContainer && !searchContainer.contains(e.target)) {
        suggestions.value = [];
    }
};

onMounted(() => {
    if (showForm.value) {
        window.addEventListener('click', handleOutsideClick);
    }
});

const openCreateForm = () => {
    isEditing.value = false;
    editingPlaceId.value = null;
    form.reset();
    showForm.value = true;
};

const openEditForm = (place) => {
    isEditing.value = true;
    editingPlaceId.value = place.id;
    form.nom = place.nom;
    form.verified_description = place.verified_description;
    form.lat = place.lat;
    form.lng = place.lng;
    form.rayon_marge = place.rayon_marge;
    form.marge_validation_gps = place.marge_validation_gps || 10;
    form.image = null;
    showForm.value = true;
};

/**
 * Soumission finale du lieu au serveur
 */
const submit = () => {
    if (isEditing.value) {
        form.post(route('admin.places.update', { place: editingPlaceId.value }), {
            onSuccess: () => {
                showForm.value = false;
                form.reset();
                toast.add({ 
                    severity: 'success', 
                    summary: 'Balise Mise à Jour', 
                    detail: 'Le lieu a été mis à jour avec succès.', 
                    life: 3000 
                });
            },
        });
    } else {
        form.post(route('admin.places.store', { city: props.city.id }), {
            onSuccess: () => {
                showForm.value = false;
                form.reset();
                toast.add({ 
                    severity: 'success', 
                    summary: 'Balise Activée', 
                    detail: 'Le nouveau lieu a été synchronisé avec la matrice.', 
                    life: 3000 
                });
            },
        });
    }
};

const confirmDelete = (place) => {
    confirm.require({
        message: `Voulez-vous vraiment supprimer le lieu "${place.nom}" ? Cette action est irréversible et supprimera toutes les énigmes associées.`,
        header: 'CONFIRMATION DE SUPPRESSION',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'SUPPRIMER',
        rejectLabel: 'ANNULER',
        acceptClass: 'p-button-danger',
        accept: () => {
            form.delete(route('admin.places.delete', { place: place.id }), {
                onSuccess: () => {
                    toast.add({ 
                        severity: 'success', 
                        summary: 'Matrice Nettoyée', 
                        detail: 'Le lieu a été supprimé de la matrice.', 
                        life: 3000 
                    });
                },
            });
        }
    });
};
</script>

<template>
    <Head title="Déploiement Géo-Spatial" />
    
    <!-- Assets Leaflet chargés via CDN pour la carte -->
    <component :is="'style'">
        @import url('https://unpkg.com/leaflet@1.9.4/dist/leaflet.css');
    </component>

    <AdminLayout>
        <div class="space-y-8 lg:space-y-12">
            <!-- En-tête de la matrice -->
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                <div>
                    <!-- Fil d'ariane style Terminal -->
                    <div class="flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-[#FF9F1C] mb-4">
                        <Link :href="route('admin.cities')" class="hover:underline flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                            RETOUR AUX VILLES
                        </Link>
                        <span class="opacity-30">/</span>
                        <span class="text-white">{{ city.name }}</span>
                    </div>
                    <h1 class="text-4xl lg:text-6xl font-black tracking-tighter uppercase italic leading-none dark:text-white text-gray-900">
                        UNIVERS DES <span class="text-[#FF9F1C]">LIEUX</span>
                    </h1>
                    <p class="text-gray-500 font-bold uppercase tracking-[0.3em] text-[10px] mt-4">
                        CONFIGURATION DES LIEUX : POUR {{ city.name.toUpperCase() }}
                    </p>
                </div>

                <!-- Barre de recherche -->
                <div class="relative w-full lg:w-96 group">
                    <div class="absolute inset-y-0 left-6 flex items-center pointer-events-none text-gray-500 group-focus-within:text-[#FF9F1C] transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    </div>
                    <input 
                        v-model="searchQuery"
                        type="text" 
                        placeholder="RECHERCHER UNE BALISE..." 
                        class="w-full bg-white dark:bg-[#111113] border-2 dark:border-white/5 border-gray-200 rounded-2xl py-5 pl-16 pr-8 text-xs font-black uppercase tracking-widest focus:ring-0 focus:border-[#FF9F1C]/50 transition-all placeholder:text-gray-500/50"
                    />
                </div>

                <!-- Bouton d'action principale -->
                <button @click="showForm ? (showForm = false) : openCreateForm()" 
                    :class="showForm ? 'bg-red-500 shadow-lg text-white' : 'bg-[#FF9F1C] shadow-lg text-black'"
                    class="px-10 py-5 rounded-2xl font-black uppercase tracking-widest text-xs transition-all hover:scale-105 active:scale-95">
                    {{ showForm ? 'ANNULER L\'OPÉRATION' : 'creer un lieu' }}
                </button>
            </div>

            <!-- Interface de création de lieu (Formulaire Unique) -->
            <transition name="gaming-slide">
                <div v-if="showForm" class="dark:bg-[#111113] bg-white p-6 lg:p-12 rounded-[2.5rem] border dark:border-white/5 border-gray-200 shadow-2xl relative overflow-hidden">
                    
                    <div class="relative z-10 space-y-12">
                        <div class="space-y-2">
                            <span class="text-[10px] font-black text-[#FF9F1C] tracking-[0.5em] uppercase">Initialisation</span>
                            <h3 class="text-3xl font-black uppercase italic tracking-tighter">CONFIGURER LE <span class="text-[#FF9F1C]">NOUVEAU LIEU</span></h3>
                        </div>

                        <div class="grid gap-12 lg:grid-cols-2">
                            <!-- Colonne Gauche : Identité & Description -->
                            <div class="space-y-8">
                                <div class="space-y-4 relative search-container">
                                    <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 ms-2">Nom du Lieu</label>
                                    <div class="relative">
                                        <input v-model="form.nom" type="text" autofocus
                                            class="w-full text-2xl lg:text-3xl dark:bg-black/40 bg-gray-50 border-none rounded-3xl py-8 px-8 focus:ring-4 focus:ring-[#FF9F1C]/20 dark:text-white text-gray-900 font-black italic tracking-tighter placeholder:opacity-20" 
                                            placeholder="EX: PLACE DES MARTYRS" />
                                        
                                        <!-- Liste des suggestions -->
                                        <transition name="step-fade">
                                            <div v-if="suggestions.length > 0" class="absolute z-[100] top-full left-0 w-full mt-2 dark:bg-[#0A0A0B] bg-white border dark:border-[#FF9F1C]/30 border-gray-200 rounded-3xl shadow-[0_20px_50px_rgba(0,0,0,0.3)] overflow-hidden backdrop-blur-xl">
                                                <div class="p-2">
                                                    <button v-for="(suggestion, index) in suggestions" :key="index"
                                                        @click="selectSuggestion(suggestion)"
                                                        class="w-full text-left p-4 rounded-2xl hover:bg-[#FF9F1C] hover:text-black transition-all flex items-start gap-4 group">
                                                        <div class="mt-1 h-6 w-6 rounded-lg bg-[#FF9F1C]/10 flex items-center justify-center group-hover:bg-black/20">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                                                        </div>
                                                        <div class="flex-1 min-w-0">
                                                            <p class="font-black uppercase italic tracking-tighter text-sm truncate group-hover:text-black dark:text-white text-gray-900">{{ suggestion.name }}</p>
                                                            <p class="text-[8px] font-bold uppercase tracking-widest text-gray-500 truncate group-hover:text-black/60">{{ suggestion.display_name }}</p>
                                                        </div>
                                                    </button>
                                                </div>
                                            </div>
                                        </transition>
                                    </div>
                                    <p class="text-[10px] text-[#FF9F1C] font-bold uppercase tracking-widest px-2 animate-pulse">
                                        <span v-if="isSearching">Analyse du terrain en cours...</span>
                                        <span v-else-if="form.nom.length >= 3">Suggestions actives</span>
                                        <span v-else>Saisissez le nom pour positionner le radar</span>
                                    </p>
                                </div>

                                <div class="space-y-4">
                                    <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 ms-2">Fichier du Lieu</label>
                                    <div class="relative group h-[200px]">
                                        <input type="file" @change="onFileChange" 
                                            class="absolute inset-0 opacity-0 cursor-pointer z-20" />
                                        <div class="h-full dark:bg-black/40 bg-gray-50 border-2 border-dashed dark:border-white/10 border-gray-200 rounded-3xl flex flex-col items-center justify-center p-4 group-hover:border-[#FF9F1C]/50 transition-all overflow-hidden relative">
                                            <template v-if="!form.image">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 mb-2 text-gray-500 group-hover:text-[#FF9F1C] transition-colors" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                                                <p class="text-[10px] font-black uppercase tracking-widest dark:text-white text-gray-900">Charger un fichier</p>
                                            </template>
                                            <template v-else>
                                                <!-- Prévisualisation de l'image -->
                                                <div v-if="imagePreview" class="absolute inset-0 z-10">
                                                    <img :src="imagePreview" class="w-full h-full object-cover opacity-50 group-hover:opacity-30 transition-opacity" />
                                                </div>
                                                <div class="relative z-20 text-center">
                                                    <p class="text-[10px] font-black text-[#FF9F1C] uppercase truncate max-w-full px-4 drop-shadow-md">{{ form.image.name }}</p>
                                                    <p class="text-[8px] text-white font-bold uppercase mt-1 drop-shadow-md">Cliquer pour changer le fichier</p>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-4">
                                    <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 ms-2">Séquence Narrative</label>
                                    <textarea v-model="form.verified_description" rows="4"
                                        class="w-full text-xl dark:bg-black/40 bg-gray-50 border-none rounded-3xl py-6 px-8 focus:ring-4 focus:ring-[#FF9F1C]/20 dark:text-white text-gray-900 font-bold italic tracking-tight placeholder:opacity-20" 
                                        placeholder="L'HISTOIRE DE CE LIEU EST..."></textarea>
                                </div>
                            </div>

                            <!-- Colonne Droite : Radar Géo-Spatial -->
                            <div class="space-y-8">
                                <div class="space-y-4">
                                    <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 ms-2">Localisation map</label>
                                    <div id="map-selector" class="w-full h-[350px] lg:h-[450px] rounded-[2rem] border-4 dark:border-white/5 border-gray-100 overflow-hidden shadow-inner"></div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div class="p-4 rounded-2xl dark:bg-white/5 bg-gray-50 border dark:border-white/5 border-gray-200">
                                        <label class="block text-[8px] font-black uppercase text-gray-500 mb-1">LATITUDE</label>
                                        <input v-model="form.lat" type="number" step="0.000001" 
                                            class="w-full bg-transparent border-none p-0 font-mono font-black text-[#FF9F1C] focus:ring-0 text-sm" />
                                    </div>
                                    <div class="p-4 rounded-2xl dark:bg-white/5 bg-gray-50 border dark:border-white/5 border-gray-200">
                                        <label class="block text-[8px] font-black uppercase text-gray-500 mb-1">LONGITUDE</label>
                                        <input v-model="form.lng" type="number" step="0.000001" 
                                            class="w-full bg-transparent border-none p-0 font-mono font-black text-[#FF9F1C] focus:ring-0 text-sm" />
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 gap-4">
                                    <div class="p-4 rounded-2xl dark:bg-white/5 bg-gray-50 border dark:border-white/5 border-gray-200">
                                        <label class="block text-[8px] font-black uppercase text-gray-500 mb-1">MARGE VALIDATION GPS (M)</label>
                                        <input v-model="form.marge_validation_gps" type="number" min="1"
                                            class="w-full bg-transparent border-none p-0 font-mono font-black text-green-500 focus:ring-0 text-sm" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Bouton de validation finale -->
                        <div class="pt-8 border-t dark:border-white/5 border-gray-100">
                            <button @click="submit" :disabled="form.processing || !form.nom" 
                                class="w-full group flex items-center justify-center gap-4 bg-white text-black px-10 py-8 rounded-[2rem] font-black uppercase tracking-[0.3em] text-sm shadow-[0_0_50px_rgba(255,255,255,0.1)] hover:scale-[1.02] transition-all disabled:opacity-30">
                                INITIALISER LE LIEU
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 animate-pulse" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v4"/><path d="m16.2 7.8 2.9-2.9"/><path d="M18 12h4"/><path d="m16.2 16.2 2.9 2.9"/><path d="M12 18v4"/><path d="m4.9 19.1 2.9-2.9"/><path d="M2 12h4"/><path d="m4.9 4.9 2.9 2.9"/></svg>
                            </button>
                        </div>
                    </div>

                    <!-- Éléments HUD décoratifs -->
                    <!-- <div class="absolute bottom-4 right-8 text-[8px] font-black text-gray-600 uppercase tracking-[0.4em] animate-pulse">GPS Signal: Locked</div> -->
                    <!-- <div class="absolute bottom-4 left-8 text-[8px] font-black text-gray-600 uppercase tracking-[0.4em]">Unit stable</div> -->
                </div>
            </transition>

            <!-- Liste des Balises (Lieux) déployées -->
            <div v-if="filteredPlaces.length > 0" class="grid gap-6">
                <div v-for="place in filteredPlaces" :key="place.id" 
                    class="dark:bg-[#111113]/40 bg-white backdrop-blur-md border dark:border-white/5 border-gray-200 rounded-[2.5rem] p-6 lg:p-8 flex flex-col lg:flex-row lg:items-center justify-between gap-6 group hover:border-[#FF9F1C]/40 transition-all duration-500 shadow-xl">
                    <div class="flex items-center gap-6 lg:gap-8">
                        <!-- Badge d'identification Balise / Image -->
                        <div class="h-20 w-20 dark:bg-black/60 bg-gray-50 rounded-[1.8rem] flex flex-col items-center justify-center border dark:border-white/5 border-gray-200 group-hover:border-[#FF9F1C]/30 transition-all duration-500 overflow-hidden relative">
                            <template v-if="place.image">
                                <img :src="'/storage/' + place.image" class="w-full h-full object-cover" alt="Lieu image" />
                                <div class="absolute inset-0 bg-black/20 group-hover:bg-transparent transition-colors"></div>
                                <span class="absolute bottom-1 right-2 text-[8px] font-black text-white drop-shadow-lg font-mono tracking-tighter">{{ place.id.toString().padStart(3, '0') }}</span>
                            </template>
                            <template v-else>
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 mb-1 text-gray-500 group-hover:text-[#FF9F1C] transition-colors" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                                <span class="text-[10px] font-black text-[#FF9F1C] font-mono tracking-tighter">{{ place.id.toString().padStart(3, '0') }}</span>
                            </template>
                        </div>
                        <div>
                            <div class="flex flex-wrap items-center gap-3 mb-2">
                                <h3 class="text-2xl font-black uppercase italic tracking-tighter dark:text-white text-gray-900 group-hover:text-[#FF9F1C] transition-colors">{{ place.nom }}</h3>
                                <span :class="place.is_active ? 'bg-green-500/10 text-green-500 border-green-500/20' : 'bg-red-500/10 text-red-500 border-red-500/20'" 
                                    class="px-3 py-1 rounded-full text-[8px] font-black uppercase tracking-widest border">
                                    {{ place.is_active ? 'Opérationnel' : 'Hors-Ligne' }}
                                </span>
                                <Link :href="route('admin.places.generate_session', { place: place.id })" method="post" as="button"
                                    @click="loadingPlaceId = 'session-' + place.id"
                                    :disabled="loadingPlaceId === 'session-' + place.id"
                                    class="px-3 py-1 rounded-full text-[8px] font-black uppercase tracking-widest border border-blue-500/20 bg-blue-500/10 text-blue-400 hover:bg-blue-500/20 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                                    <template v-if="loadingPlaceId === 'session-' + place.id">Génération...</template>
                                    <template v-else>Générer Lien Partage</template>
                                </Link>
                            </div>
                            <div class="flex flex-wrap items-center gap-4 lg:gap-6 text-gray-500 font-bold text-[8px] lg:text-[10px] uppercase tracking-widest">
                                <span class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/><path d="M2 12h20"/></svg>
                                    {{ city.name }}
                                </span>
                                <span class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s-8-4.5-8-11.8A8 8 0 0 1 12 2a8 8 0 0 1 8 8.2c0 7.3-8 11.8-8 11.8Z"/><circle cx="12" cy="10" r="3"/></svg>
                                    {{ place.departement || city.departement }}
                                </span>
                                <span class="font-mono text-[#FF9F1C]/60 italic">{{ place.lat }}, {{ place.lng }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-4 w-full lg:w-auto">
                        <!-- Accès aux énigmes -->
                        <Link :href="route('admin.enigmas', { place: place.id })" 
                            @click="loadingPlaceId = place.id"
                            :class="{ 'opacity-50 pointer-events-none cursor-not-allowed': loadingPlaceId === place.id }"
                            class="group/btn flex-1 lg:flex-none flex items-center justify-center gap-3 dark:bg-white/5 bg-gray-100 px-8 py-5 rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all hover:bg-[#FF9F1C] hover:text-black dark:text-white text-gray-700">
                            <template v-if="loadingPlaceId === place.id">
                                <svg class="animate-spin h-4 w-4 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                CHARGEMENT...
                            </template>
                            <template v-else>
                                ÉNIGMES
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 group-hover/btn:translate-x-1 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                            </template>
                        </Link>
                        <!-- Modification -->
                        <button @click="openEditForm(place)" :disabled="form.processing"
                            class="p-5 rounded-2xl border dark:border-white/5 border-gray-200 hover:border-blue-500/50 hover:text-blue-500 transition-all group/btn disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
                        </button>
                        <!-- Suppression -->
                        <button @click="confirmDelete(place)" :disabled="form.processing"
                            class="p-5 rounded-2xl border dark:border-white/5 border-gray-200 hover:border-red-500/50 hover:text-red-500 transition-all group/btn disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- État vide pour la recherche -->
            <div v-else class="py-20 text-center space-y-6">
                <div class="h-24 w-24 mx-auto dark:bg-white/5 bg-gray-50 rounded-full flex items-center justify-center text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                </div>
                <div>
                    <h3 class="text-xl font-black uppercase italic tracking-tighter">AUCUNE BALISE DÉTECTÉE</h3>
                    <p class="text-gray-500 font-bold uppercase tracking-widest text-[10px] mt-2">Ajustez les paramètres de recherche du radar</p>
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

/* Personnalisation pour Leaflet */
:deep(.leaflet-container) {
    background: #f8fafc !important;
    border-radius: 1.5rem;
}
:deep(.leaflet-tile) {
    /* Style clair naturel (Capture 1) */
    filter: none;
}
:deep(.leaflet-control-attribution) {
    display: none;
}
</style>
