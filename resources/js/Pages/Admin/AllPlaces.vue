<script setup>
import AdminLayout from './AdminLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed, watch, nextTick, onMounted } from 'vue';

import axios from 'axios';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';

const props = defineProps({
    places: {
        type: Array,
        default: () => []
    },
    cities: {
        type: Array,
        default: () => []
    },
});

const toast = useToast();
const confirm = useConfirm();
const searchQuery = ref('');
const selectedCityFilter = ref('');
const showForm = ref(false);

// Définition du formulaire avec Inertia useForm
const form = useForm({
    city_id: '',            // Cité parente
    nom: '',                  // Nom du lieu/secteur
    image: null,              // Image du lieu
    verified_description: '', // Description narrative
    lat: 6.3667,              // Latitude par défaut (Cotonou)
    lng: 2.4333,              // Longitude par défaut
    rayon_marge: 5,           // Rayon de détection par défaut
});

// Références pour la gestion de la carte Leaflet
const map = ref(null);
const marker = ref(null);
const suggestions = ref([]);
const isSearching = ref(false);

// Gestion du changement de fichier image
const onFileChange = (e) => {
    if (e.target.files && e.target.files.length > 0) {
        form.image = e.target.files[0];
    }
};

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
            // On cherche dans la cité sélectionnée si possible
            const selectedCity = props.cities.find(c => c.id == form.city_id);
            const cityName = selectedCity ? selectedCity.name : '';
            const query = `${form.nom}${cityName ? ', ' + cityName : ''}, Bénin`;
            const response = await axios.get(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=5&addressdetails=1`);
            
            if (response.data) {
                suggestions.value = response.data.map(item => ({
                    display_name: item.display_name,
                    name: item.name || item.display_name.split(',')[0],
                    lat: item.lat,
                    lon: item.lon
                }));
            }
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
            const addr = response.data.address;
            form.nom = addr.amenity || addr.building || addr.shop || addr.tourism || addr.historic || addr.road || response.data.name || response.data.display_name.split(',')[0];
        }
    } catch (error) {
        console.error('Erreur lors du géocodage inversé:', error);
    }
};

// Surveillance du nom pour la recherche
watch(() => form.nom, (newVal, oldVal) => {
    if (!newVal) {
        suggestions.value = [];
        return;
    }
    if (showForm.value && newVal !== oldVal && suggestions.value.length === 0 && !isSearching.value) {
        searchLocation();
    }
});

// Surveillance des coordonnées pour mettre à jour la carte manuellement
watch([() => form.lat, () => form.lng], ([newLat, newLng]) => {
    if (map.value && marker.value && !debounceTimeout) {
        const pos = [parseFloat(newLat), parseFloat(newLng)];
        if (!isNaN(pos[0]) && !isNaN(pos[1])) {
            marker.value.setLatLng(pos);
            const center = map.value.getCenter();
            const dist = Math.sqrt(Math.pow(center.lat - pos[0], 2) + Math.pow(center.lng - pos[1], 2));
            if (dist > 0.001) {
                map.value.panTo(pos);
            }
        }
    }
});

/**
 * Initialisation de la carte
 */
const initMap = () => {
    // On utilise L de l'objet window (chargé via script tag dans app.blade.php)
    const L = window.L;
    if (!L) {
        setTimeout(initMap, 100);
        return;
    }

    const mapContainer = document.getElementById('map-selector');
    if (!mapContainer) return;

    if (map.value) {
        map.value.invalidateSize();
        return;
    }

    const initialPos = [parseFloat(form.lat), parseFloat(form.lng)];

    map.value = L.map('map-selector', {
        zoomControl: true,
        scrollWheelZoom: true
    }).setView(initialPos, 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map.value);

    marker.value = L.marker(initialPos, { draggable: true }).addTo(map.value);

    marker.value.on('dragend', async (e) => {
        const { lat, lng } = e.target.getLatLng();
        form.lat = parseFloat(lat).toFixed(6);
        form.lng = parseFloat(lng).toFixed(6);
        await reverseGeocode(lat, lng);
    });

    map.value.on('click', async (e) => {
        const { lat, lng } = e.latlng;
        marker.value.setLatLng([lat, lng]);
        form.lat = parseFloat(lat).toFixed(6);
        form.lng = parseFloat(lng).toFixed(6);
        await reverseGeocode(lat, lng);
    });

    setTimeout(() => {
        if (map.value) map.value.invalidateSize();
    }, 500);
};

// Surveillance de l'affichage du formulaire pour initialiser la carte
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

/**
 * Soumission du formulaire
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
        form.post(route('admin.places.store_global'), {
            onSuccess: () => {
                showForm.value = false;
                form.reset();
                toast.add({ 
                    severity: 'success', 
                    summary: 'Balise Activée', 
                    detail: 'Le nouveau lieu a été synchronisé avec la cité sélectionnée.', 
                    life: 3000 
                });
            },
        });
    }
};

const isEditing = ref(false);
const editingPlaceId = ref(null);

const openCreateForm = () => {
    isEditing.value = false;
    editingPlaceId.value = null;
    form.reset();
    showForm.value = true;
};

const openEditForm = (place) => {
    isEditing.value = true;
    editingPlaceId.value = place.id;
    form.city_id = place.city_id;
    form.nom = place.nom;
    form.verified_description = place.verified_description;
    form.lat = place.lat;
    form.lng = place.lng;
    form.rayon_marge = place.rayon_marge;
    form.image = null;
    showForm.value = true;
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
            router.delete(route('admin.places.delete', { place: place.id }), {
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

const filteredPlaces = computed(() => {
    const places = props.places || [];
    let result = [...places];

    if (selectedCityFilter.value) {
        result = result.filter(place => place && place.city_id == selectedCityFilter.value);
    }

    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter(place => {
            if (!place) return false;
            const nomMatch = place.nom && place.nom.toLowerCase().includes(query);
            const cityMatch = place.city && place.city.name && place.city.name.toLowerCase().includes(query);
            const deptMatch = place.departement && typeof place.departement === 'string' && place.departement.toLowerCase().includes(query);
            const cityDeptMatch = !deptMatch && place.city && place.city.departement && place.city.departement.toLowerCase().includes(query);
            
            return nomMatch || cityMatch || deptMatch || cityDeptMatch;
        });
    }

    return result;
});
</script>

<template>
    <Head title="Répertoire des Balises" />
    
    <AdminLayout>
        <div class="space-y-8 lg:space-y-12">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                <div>
                    <h1 class="text-4xl lg:text-6xl font-black tracking-tighter uppercase italic leading-none dark:text-white text-gray-900">
                        RÉPERTOIRE <span class="text-[#FF9F1C]">GLOBAL</span>
                    </h1>
                    <p class="text-gray-500 font-bold uppercase tracking-[0.3em] text-[10px] mt-4">
                        LISTE INTÉGRALE DES BALISES DÉPLOYÉES SUR LE TERRITOIRE
                    </p>
                </div>

                <div class="flex flex-col md:flex-row items-center gap-4 w-full lg:w-auto">
                    <!-- Filtre par Cité -->
                    <div class="w-full md:w-64">
                        <select 
                            v-model="selectedCityFilter" 
                            class="w-full bg-white dark:bg-[#111113] border-2 dark:border-white/5 border-gray-100 rounded-2xl py-4 px-6 text-[10px] md:text-xs font-black uppercase tracking-widest focus:ring-0 focus:border-[#FF9F1C]/50 transition-all appearance-none cursor-pointer"
                        >
                            <option value="">TOUTES LES CITÉS</option>
                            <option v-for="city in cities" :key="city.id" :value="city.id">
                                {{ city.name.toUpperCase() }}
                            </option>
                        </select>
                    </div>

                    <!-- Barre de recherche stylisée -->
                    <div class="relative w-full md:w-80 group">
                        <div class="absolute inset-y-0 left-6 flex items-center pointer-events-none text-gray-500 group-focus-within:text-[#FF9F1C] transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 md:w-5 md:h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                        </div>
                        <input 
                            v-model="searchQuery"
                            type="text" 
                            placeholder="RECHERCHER..." 
                            class="w-full bg-white dark:bg-[#111113] border-2 dark:border-white/5 border-gray-100 rounded-2xl py-4 md:py-5 pl-14 md:pl-16 pr-8 text-[10px] md:text-xs font-black uppercase tracking-widest focus:ring-0 focus:border-[#FF9F1C]/50 transition-all placeholder:text-gray-500/50"
                        />
                    </div>

                    <!-- Bouton d'action principale -->
                    <button @click="showForm ? (showForm = false) : openCreateForm()" 
                        :class="showForm ? 'bg-red-500 shadow-lg text-white' : 'bg-[#FF9F1C] shadow-lg text-black'"
                        class="w-full md:w-auto px-8 py-4 md:py-5 rounded-2xl font-black uppercase tracking-widest text-[10px] transition-all hover:scale-105 active:scale-95 whitespace-nowrap">
                        {{ showForm ? 'ANNULER' : 'creer un lieu' }}
                    </button>
                </div>
            </div>

            <!-- Interface de création de lieu (Formulaire Unique Global) -->
            <transition name="gaming-slide">
                <div v-if="showForm" class="dark:bg-[#111113] bg-white p-6 lg:p-12 rounded-[2.5rem] border dark:border-white/5 border-gray-200 shadow-2xl relative overflow-hidden">
                    
                    <div class="relative z-10 space-y-12">
                        <div class="space-y-2">
                            <span class="text-[10px] font-black text-[#FF9F1C] tracking-[0.5em] uppercase">{{ isEditing ? 'Mise à jour' : 'Initialisation Globale' }}</span>
                            <h3 class="text-3xl font-black uppercase italic tracking-tighter">
                                {{ isEditing ? 'ÉDITER LA' : 'CONFIGURER LE' }} <span class="text-[#FF9F1C]">{{ isEditing ? 'BALISE' : 'NOUVEAU LIEU' }}</span>
                            </h3>
                        </div>

                        <div class="grid gap-12 lg:grid-cols-2">
                            <!-- Colonne Gauche : Identité & Description -->
                            <div class="space-y-8">
                                <!-- Champ Select pour la Cité -->
                                <div class="space-y-4">
                                    <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 ms-2">Assigner à une Cité</label>
                                    <select 
                                        v-model="form.city_id" 
                                        class="w-full bg-gray-50 dark:bg-black/40 border-none rounded-3xl py-6 px-8 text-sm font-black uppercase tracking-widest focus:ring-4 focus:ring-[#FF9F1C]/20 transition-all appearance-none cursor-pointer"
                                    >
                                        <option value="" disabled>CHOISIR LA CITÉ DE DESTINATION</option>
                                        <option v-for="city in cities" :key="city.id" :value="city.id">
                                            {{ city.name.toUpperCase() }}
                                        </option>
                                    </select>
                                </div>

                                <div class="space-y-4 relative search-container">
                                    <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 ms-2">Nom du Lieu</label>
                                    <div class="relative">
                                        <input v-model="form.nom" type="text" 
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
                                    <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 ms-2">Image du Lieu (Optionnel)</label>
                                    <div class="relative group h-[150px]">
                                        <input type="file" @change="onFileChange" 
                                            class="absolute inset-0 opacity-0 cursor-pointer z-20" accept="image/*" />
                                        <div class="h-full dark:bg-black/40 bg-gray-50 border-2 border-dashed dark:border-white/10 border-gray-200 rounded-3xl flex flex-col items-center justify-center p-4 group-hover:border-[#FF9F1C]/50 transition-all overflow-hidden">
                                            <template v-if="!form.image">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 mb-2 text-gray-500 group-hover:text-[#FF9F1C] transition-colors" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                                                <p class="text-[10px] font-black uppercase tracking-widest dark:text-white text-gray-900">Charger depuis le gestionnaire</p>
                                            </template>
                                            <template v-else>
                                                <p class="text-[10px] font-black text-[#FF9F1C] uppercase truncate max-w-full px-4">{{ form.image.name }}</p>
                                                <p class="text-[8px] text-gray-500 uppercase mt-1">Cliquer pour changer l'image</p>
                                            </template>
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-4">
                                    <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 ms-2">Séquence Narrative (Optionnel)</label>
                                    <textarea v-model="form.verified_description" rows="3"
                                        class="w-full text-xl dark:bg-black/40 bg-gray-50 border-none rounded-3xl py-6 px-8 focus:ring-4 focus:ring-[#FF9F1C]/20 dark:text-white text-gray-900 font-bold italic tracking-tight placeholder:opacity-20" 
                                        placeholder="L'HISTOIRE DE CE LIEU EST..."></textarea>
                                </div>
                            </div>

                            <!-- Colonne Droite : Radar Géo-Spatial -->
                            <div class="space-y-8">
                                <div class="space-y-4">
                                    <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500 ms-2">Radar Satellite</label>
                                    <div id="map-selector" class="w-full h-[400px] lg:h-[500px] rounded-[2rem] border-4 dark:border-white/5 border-gray-100 overflow-hidden shadow-inner"></div>
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
                            </div>
                        </div>

                        <!-- Bouton de validation finale -->
                        <div class="pt-8 border-t dark:border-white/5 border-gray-100">
                            <button @click="submit" :disabled="form.processing || !form.nom || !form.city_id" 
                                class="w-full group flex items-center justify-center gap-4 bg-white text-black px-10 py-8 rounded-[2rem] font-black uppercase tracking-[0.3em] text-sm shadow-[0_0_50px_rgba(255,255,255,0.1)] hover:scale-[1.02] transition-all disabled:opacity-30">
                                {{ isEditing ? 'METTRE À JOUR DANS LA MATRICE' : 'DÉPLOYER DANS LA CITÉ SÉLECTIONNÉE' }}
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 animate-pulse" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v4"/><path d="m16.2 7.8 2.9-2.9"/><path d="M18 12h4"/><path d="m16.2 16.2 2.9 2.9"/><path d="M12 18v4"/><path d="m4.9 19.1 2.9-2.9"/><path d="M2 12h4"/><path d="m4.9 4.9 2.9 2.9"/></svg>
                            </button>
                        </div>
                    </div>

                    <!-- Éléments HUD décoratifs -->
                    <div class="absolute bottom-4 right-8 text-[8px] font-black text-gray-600 uppercase tracking-[0.4em] animate-pulse">GPS Signal: Locked</div>
                    <div class="absolute bottom-4 left-8 text-[8px] font-black text-gray-600 uppercase tracking-[0.4em]">Unit stable</div>
                </div>
            </transition>

            <!-- Liste des lieux -->
            <div v-if="filteredPlaces.length > 0" class="grid gap-4 md:gap-6">
                <div v-for="place in filteredPlaces" :key="place.id" 
                    class="dark:bg-[#111113]/40 bg-white backdrop-blur-md border dark:border-white/5 border-gray-200 rounded-[2rem] md:rounded-[2.5rem] p-4 md:p-8 flex flex-col lg:flex-row lg:items-center justify-between gap-4 md:gap-6 group hover:border-[#FF9F1C]/40 transition-all duration-500 shadow-xl">
                    <div class="flex items-center gap-4 md:gap-8">
                        <div class="h-16 w-16 md:h-20 md:w-20 dark:bg-black/60 bg-gray-50 rounded-[1.2rem] md:rounded-[1.8rem] flex flex-col items-center justify-center border dark:border-white/5 border-gray-200 group-hover:border-[#FF9F1C]/30 transition-all duration-500 overflow-hidden relative flex-shrink-0">
                            <template v-if="place.image">
                                <img :src="'/storage/' + place.image" class="w-full h-full object-cover" alt="Lieu image" />
                                <div class="absolute inset-0 bg-black/20 group-hover:bg-transparent transition-colors"></div>
                            </template>
                            <template v-else>
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 md:w-6 md:h-6 mb-1 text-gray-500 group-hover:text-[#FF9F1C] transition-colors" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                            </template>
                            <span class="absolute bottom-1 right-2 text-[6px] md:text-[8px] font-black text-white drop-shadow-lg font-mono tracking-tighter">{{ place.id.toString().padStart(3, '0') }}</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2 md:gap-3 mb-1 md:mb-2">
                                <h3 class="text-lg md:text-2xl font-black uppercase italic tracking-tighter dark:text-white text-gray-900 group-hover:text-[#FF9F1C] transition-colors truncate">{{ place.nom }}</h3>
                                <span :class="place.is_active ? 'bg-green-500/10 text-green-500 border-green-500/20' : 'bg-red-500/10 text-red-500 border-red-500/20'" 
                                    class="px-2 py-0.5 md:px-3 md:py-1 rounded-full text-[6px] md:text-[8px] font-black uppercase tracking-widest border">
                                    {{ place.is_active ? 'En Ligne' : 'Off' }}
                                </span>
                            </div>
                            <div class="flex flex-wrap items-center gap-3 md:gap-6 text-gray-500 font-bold text-[7px] md:text-[10px] uppercase tracking-widest">
                                <span class="flex items-center gap-1.5 md:gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-2.5 h-2.5 md:w-3 md:h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/><path d="M2 12h20"/></svg>
                                    {{ place.city ? place.city.name : 'N/A' }}
                                </span>
                                <span class="hidden xs:flex items-center gap-1.5 md:gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-2.5 h-2.5 md:w-3 md:h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s-8-4.5-8-11.8A8 8 0 0 1 12 2a8 8 0 0 1 8 8.2c0 7.3-8 11.8-8 11.8Z"/><circle cx="12" cy="10" r="3"/></svg>
                                    {{ place.departement || (place.city ? place.city.departement : 'N/A') }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 md:gap-4 w-full lg:w-auto">
                        <Link :href="route('admin.enigmas', { place: place.id })" 
                            class="group/btn flex-1 lg:flex-none flex items-center justify-center gap-2 md:gap-3 dark:bg-white/5 bg-gray-100 px-4 md:px-8 py-3 md:py-5 rounded-xl md:rounded-2xl text-[8px] md:text-[10px] font-black uppercase tracking-widest transition-all hover:bg-[#FF9F1C] hover:text-black dark:text-white text-gray-700">
                            ÉNIGMES
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 md:w-4 md:h-4 group-hover/btn:translate-x-1 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                        </Link>

                        <div class="flex items-center gap-2">
                            <!-- Modification -->
                            <button @click="openEditForm(place)" 
                                class="p-3 md:p-5 rounded-xl md:rounded-2xl border dark:border-white/5 border-gray-200 hover:border-blue-500/50 hover:text-blue-500 transition-all group/btn">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 md:w-5 md:h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
                            </button>

                            <!-- Suppression -->
                            <button @click="confirmDelete(place)" 
                                class="p-3 md:p-5 rounded-xl md:rounded-2xl border dark:border-white/5 border-gray-200 hover:border-red-500/50 hover:text-red-500 transition-all group/btn">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 md:w-5 md:h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
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
                    <h3 class="text-2xl font-black uppercase italic tracking-tighter">
                        {{ props.places.length === 0 ? 'MATRICE VIDE' : 'AUCUN RÉSULTAT' }}
                    </h3>
                    <p class="text-gray-500 font-bold uppercase tracking-widest text-[10px] mt-2 leading-relaxed">
                        {{ props.places.length === 0 
                            ? 'AUCUNE BALISE N\'A ÉTÉ DÉPLOYÉE SUR LE RÉSEAU. INITIALISEZ VOTRE PREMIER LIEU DÈS MAINTENANT.' 
                            : 'LE RADAR NE DÉTECTE AUCUNE CORRESPONDANCE. ESSAYEZ D\'AUTRES COORDONNÉES OU FILTRES.' }}
                    </p>
                    
                    <div class="pt-8">
                        <button @click="openCreateForm()" 
                            class="bg-[#FF9F1C] text-black px-10 py-5 rounded-2xl font-black uppercase tracking-widest text-[10px] shadow-[0_0_30px_rgba(255,159,28,0.2)] hover:scale-105 active:scale-95 transition-all">
                            {{ props.places.length === 0 ? 'DÉPLOYER UNE BALISE' : 'CRÉER UN NOUVEAU LIEU' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>

.gaming-slide-enter-active, .gaming-slide-leave-active {
    transition: all 0.8s cubic-bezier(0.16, 1, 0.3, 1);
}
.gaming-slide-enter-from, .gaming-slide-leave-to {
    opacity: 0;
    transform: translateY(100px) scale(0.95);
    filter: blur(10px);
}

.step-fade-enter-active, .step-fade-leave-active {
    transition: all 0.4s ease;
}
.step-fade-enter-from, .step-fade-leave-to {
    opacity: 0;
    transform: translateX(20px);
}

/* Custom styles for Leaflet to match gaming theme */
:deep(.leaflet-container) {
    background: #0A0A0B;
    font-family: inherit;
}

:deep(.leaflet-tile) {
    filter: invert(100%) hue-rotate(180deg) brightness(95%) contrast(90%);
}

:deep(.leaflet-control-attribution) {
    display: none;
}
</style>
