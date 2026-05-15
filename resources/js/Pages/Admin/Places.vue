<script setup>
import AdminLayout from './AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, onMounted, nextTick } from 'vue';

const props = defineProps({
    city: Object,
    places: Array,
});

const showForm = ref(false);
const form = useForm({
    nom: '',
    verified_description: '',
    lat: 6.3667,
    lng: 2.4333,
    rayon_marge: 5,
});

// Map Logic
const map = ref(null);
const marker = ref(null);
const mapContainer = ref(null);

const initMap = () => {
    if (map.value) return;

    // Center of Benin approximately
    const beninCenter = [9.3077, 2.3158];
    const initialPos = [form.lat, form.lng];

    map.value = L.map('map-selector').setView(initialPos, 7);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map.value);

    marker.value = L.marker(initialPos, { draggable: true }).addTo(map.value);

    // Update form when marker is dragged
    marker.value.on('dragend', (e) => {
        const { lat, lng } = e.target.getLatLng();
        form.lat = lat.toFixed(6);
        form.lng = lng.toFixed(6);
    });

    // Update marker and form when map is clicked
    map.value.on('click', (e) => {
        const { lat, lng } = e.latlng;
        marker.value.setLatLng([lat, lng]);
        form.lat = lat.toFixed(6);
        form.lng = lng.toFixed(6);
    });

    // Invalidate size to ensure map renders correctly in transition
    setTimeout(() => {
        map.value.invalidateSize();
    }, 200);
};

watch(showForm, (newVal) => {
    if (newVal) {
        nextTick(() => {
            initMap();
        });
    }
});

const submit = () => {
    form.post(route('admin.places.store', { city: props.city.id }), {
        onSuccess: () => {
            showForm.value = false;
            form.reset();
        },
    });
};
</script>

<template>
    <Head title="Déploiement Géo" />
    
    <!-- Leaflet Assets -->
    <component :is="'style'">
        @import url('https://unpkg.com/leaflet@1.9.4/dist/leaflet.css');
    </component>

    <AdminLayout>
        <div class="space-y-8 lg:space-y-12">
            <!-- Header Section -->
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                <div>
                    <div class="flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-[#FF9F1C] mb-4">
                        <Link :href="route('admin.cities')" class="hover:underline">Matrice</Link>
                        <span>/</span>
                        <span>{{ city.name }}</span>
                    </div>
                    <h1 class="text-4xl lg:text-6xl font-black tracking-tighter uppercase italic leading-none dark:text-white text-gray-900">
                        Radar <span class="text-[#FF9F1C]">Géo-Spatial</span>
                    </h1>
                    <p class="text-gray-500 font-bold uppercase tracking-[0.3em] text-[10px] mt-4">
                        Configuration des balises pour la cité de {{ city.name }}
                    </p>
                </div>
                <button @click="showForm = !showForm" 
                    :class="showForm ? 'bg-red-500 shadow-lg text-white' : 'bg-[#FF9F1C] shadow-lg text-black'"
                    class="px-10 py-5 rounded-2xl font-black uppercase tracking-widest text-xs transition-all hover:scale-105 active:scale-95">
                    {{ showForm ? 'Annuler l\'Opération' : 'Déployer une Balise' }}
                </button>
            </div>

            <!-- Add Place Form -->
            <transition name="fade">
                <div v-if="showForm" class="dark:bg-[#111113] bg-white p-6 lg:p-10 rounded-[2rem] lg:rounded-[2.5rem] border dark:border-white/5 border-gray-200 shadow-2xl relative overflow-hidden">
                    <div class="grid gap-8 lg:grid-cols-2 relative z-10">
                        <!-- Left: Form Fields -->
                        <div class="space-y-6 lg:space-y-8">
                            <form @submit.prevent="submit" class="grid gap-6 grid-cols-1">
                                <div class="space-y-3">
                                    <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500">Nom de la Balise (Secteur)</label>
                                    <input v-model="form.nom" type="text" class="w-full dark:bg-black/40 bg-gray-50 border dark:border-white/5 border-gray-200 rounded-2xl py-4 px-6 focus:ring-2 focus:ring-[#FF9F1C] dark:text-white text-gray-900 font-bold" placeholder="Ex: Place des Martyrs" />
                                </div>
                                <div class="space-y-3">
                                    <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500">Révélation Narrative (Description)</label>
                                    <textarea v-model="form.verified_description" rows="4" class="w-full dark:bg-black/40 bg-gray-50 border dark:border-white/5 border-gray-200 rounded-2xl py-4 px-6 focus:ring-2 focus:ring-[#FF9F1C] dark:text-white text-gray-900 font-bold" placeholder="La description qui sera débloquée par le joueur..."></textarea>
                                </div>
                                <div class="grid grid-cols-2 gap-6">
                                    <div class="space-y-3">
                                        <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500">Latitude</label>
                                        <input v-model="form.lat" type="number" step="any" class="w-full dark:bg-black/20 bg-gray-100 border dark:border-white/5 border-gray-200 rounded-2xl py-4 px-6 dark:text-[#FF9F1C] text-gray-900 font-black font-mono text-xs" />
                                    </div>
                                    <div class="space-y-3">
                                        <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500">Longitude</label>
                                        <input v-model="form.lng" type="number" step="any" class="w-full dark:bg-black/20 bg-gray-100 border dark:border-white/5 border-gray-200 rounded-2xl py-4 px-6 dark:text-[#FF9F1C] text-gray-900 font-black font-mono text-xs" />
                                    </div>
                                </div>
                                <div class="pt-4">
                                    <button type="submit" :disabled="form.processing" class="w-full bg-gray-900 dark:bg-white text-white dark:text-black py-6 rounded-2xl font-black uppercase tracking-[0.2em] text-sm shadow-xl hover:bg-[#FF9F1C] hover:text-black transition-all disabled:opacity-50">
                                        Activer la Balise
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Right: GPS Map Selector -->
                        <div class="space-y-4">
                            <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-[#FF9F1C]">Localisateur Satellite (Cliquer pour définir)</label>
                            <div id="map-selector" class="w-full h-[400px] lg:h-full min-h-[400px] rounded-[2.5rem] border-4 dark:border-white/5 border-gray-100 overflow-hidden z-0"></div>
                        </div>
                    </div>
                </div>
            </transition>

            <!-- Places List -->
            <div class="grid gap-4 lg:gap-6">
                <div v-for="place in places" :key="place.id" 
                    class="dark:bg-[#111113]/40 bg-white backdrop-blur-md border dark:border-white/5 border-gray-200 rounded-[2rem] p-6 lg:p-8 flex flex-col lg:flex-row lg:items-center justify-between gap-6 group hover:border-[#FF9F1C]/20 transition-all duration-500 shadow-sm dark:shadow-none">
                    <div class="flex items-center gap-6 lg:gap-8">
                        <div class="h-16 w-16 lg:h-20 lg:w-20 dark:bg-black/60 bg-gray-50 rounded-[1.5rem] flex flex-col items-center justify-center border dark:border-white/5 border-gray-200 group-hover:border-[#FF9F1C]/30 transition-colors">
                            <span class="text-xl lg:text-2xl mb-1">📍</span>
                            <span class="text-[8px] lg:text-[10px] font-black text-[#FF9F1C] font-mono">{{ place.id.toString().padStart(3, '0') }}</span>
                        </div>
                        <div>
                            <div class="flex flex-wrap items-center gap-3 mb-2">
                                <h3 class="text-xl lg:text-2xl font-black uppercase italic tracking-tighter dark:text-white text-gray-900">{{ place.nom }}</h3>
                                <span :class="place.is_active ? 'bg-green-500/10 text-green-500 border-green-500/20' : 'bg-red-500/10 text-red-500 border-red-500/20'" 
                                    class="px-3 py-1 rounded-full text-[8px] font-black uppercase tracking-widest border">
                                    {{ place.is_active ? 'Actif' : 'Inactif' }}
                                </span>
                            </div>
                            <div class="flex flex-wrap items-center gap-4 lg:gap-6 text-gray-500 font-bold text-[8px] lg:text-[10px] uppercase tracking-widest">
                                <span>🏙️ {{ place.city?.name || 'Ville inconnue' }}</span>
                                <span>🗺️ {{ place.departement }}</span>
                                <span>🛰️ {{ place.lat }}, {{ place.lng }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-4 w-full lg:w-auto">
                        <Link :href="route('admin.enigmas', { place: place.id })" 
                            class="flex-1 lg:flex-none text-center dark:bg-white/5 bg-gray-100 dark:hover:bg-[#FF9F1C] hover:bg-[#FF9F1C] dark:hover:text-black hover:text-black px-6 py-4 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all dark:text-white text-gray-700">
                            Énigmes
                        </Link>
                        <button @click="$inertia.post(route('admin.places.toggle', { place: place.id }))" 
                            class="p-4 rounded-xl border dark:border-white/5 border-gray-200 hover:border-red-500/50 hover:text-red-500 transition-all group/btn">
                            <span class="text-lg group-hover/btn:scale-110 block transition-transform text-white">⚙️</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active {
    transition: all 0.5s ease;
}
.fade-enter-from, .fade-leave-to {
    opacity: 0;
    transform: translateY(-20px);
}

/* Custom Leaflet Dark Mode Adjustments */
:deep(.leaflet-container) {
    background: #000 !important;
    border-radius: 2rem;
}
:deep(.leaflet-tile) {
    filter: brightness(0.8) contrast(1.2);
}
.dark :deep(.leaflet-tile) {
    filter: invert(100%) hue-rotate(180deg) brightness(0.9) contrast(0.9);
}
:deep(.leaflet-control-attribution) {
    display: none;
}
</style>
