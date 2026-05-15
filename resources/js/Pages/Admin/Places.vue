<script setup>
import AdminLayout from './AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    places: Array,
});

const showForm = ref(false);
const form = useForm({
    nom: '',
    ville: '',
    departement: '',
    verified_description: '',
    lat: 6.3667,
    lng: 2.4333,
    rayon_marge: 5,
});

const submit = () => {
    form.post(route('admin.places.store'), {
        onSuccess: () => {
            showForm.value = false;
            form.reset();
        },
    });
};
</script>

<template>
    <Head title="Déploiement Géo" />
    <AdminLayout>
        <div class="space-y-12">
            <!-- Header Section -->
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-6xl font-black tracking-tighter uppercase italic leading-none">
                        Radar <span class="text-[#FF9F1C]">Géo-Spatial</span>
                    </h1>
                    <p class="text-gray-500 font-bold uppercase tracking-[0.3em] text-[10px] mt-4">
                        Configuration des zones d'exploration et des balises
                    </p>
                </div>
                <button @click="showForm = !showForm" 
                    :class="showForm ? 'bg-red-500 shadow-[0_0_20px_rgba(239,68,68,0.3)]' : 'bg-[#FF9F1C] shadow-[0_0_20px_rgba(255,159,28,0.3)]'"
                    class="text-black px-10 py-5 rounded-2xl font-black uppercase tracking-widest text-xs transition-all hover:scale-105 active:scale-95">
                    {{ showForm ? 'Annuler l\'Opération' : 'Déployer un Nouveau Lieu' }}
                </button>
            </div>

            <!-- Add Place Form -->
            <transition name="fade">
                <div v-if="showForm" class="bg-[#111113] p-10 rounded-[2.5rem] border border-white/5 shadow-2xl relative overflow-hidden">
                    <div class="absolute top-0 right-0 p-8 opacity-10 pointer-events-none">
                        <span class="text-9xl font-black uppercase italic tracking-tighter">New Zone</span>
                    </div>

                    <form @submit.prevent="submit" class="grid gap-8 md:grid-cols-3 relative z-10">
                        <div class="space-y-3">
                            <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500">Nom du Secteur</label>
                            <input v-model="form.nom" type="text" class="w-full bg-black/40 border border-white/5 rounded-2xl py-4 px-6 focus:ring-2 focus:ring-[#FF9F1C] focus:border-transparent text-white placeholder-gray-700 font-bold" placeholder="Ex: Palais de Béhanzin" />
                        </div>
                        <div class="space-y-3">
                            <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500">Ville de Référence</label>
                            <input v-model="form.ville" type="text" class="w-full bg-black/40 border border-white/5 rounded-2xl py-4 px-6 focus:ring-2 focus:ring-[#FF9F1C] focus:border-transparent text-white placeholder-gray-700 font-bold" placeholder="Abomey" />
                        </div>
                        <div class="space-y-3">
                            <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500">Département</label>
                            <input v-model="form.departement" type="text" class="w-full bg-black/40 border border-white/5 rounded-2xl py-4 px-6 focus:ring-2 focus:ring-[#FF9F1C] focus:border-transparent text-white placeholder-gray-700 font-bold" placeholder="Zou" />
                        </div>
                        <div class="md:col-span-2 space-y-3">
                            <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500">Description Archivée (Vérifiée)</label>
                            <textarea v-model="form.verified_description" rows="3" class="w-full bg-black/40 border border-white/5 rounded-2xl py-4 px-6 focus:ring-2 focus:ring-[#FF9F1C] focus:border-transparent text-white placeholder-gray-700 font-bold" placeholder="La description complète qui sera révélée au joueur victorieux..."></textarea>
                        </div>
                        <div class="space-y-3">
                            <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500">Rayon d'Impact (m)</label>
                            <input v-model="form.rayon_marge" type="number" class="w-full bg-black/40 border border-white/5 rounded-2xl py-4 px-6 focus:ring-2 focus:ring-[#FF9F1C] focus:border-transparent text-white font-bold" />
                        </div>
                        <div class="space-y-3">
                            <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500">Latitude (GPS)</label>
                            <input v-model="form.lat" type="number" step="any" class="w-full bg-black/40 border border-white/5 rounded-2xl py-4 px-6 focus:ring-2 focus:ring-[#FF9F1C] focus:border-transparent text-white font-bold font-mono" />
                        </div>
                        <div class="space-y-3">
                            <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500">Longitude (GPS)</label>
                            <input v-model="form.lng" type="number" step="any" class="w-full bg-black/40 border border-white/5 rounded-2xl py-4 px-6 focus:ring-2 focus:ring-[#FF9F1C] focus:border-transparent text-white font-bold font-mono" />
                        </div>
                        <div class="md:col-span-3 pt-4">
                            <button type="submit" :disabled="form.processing" class="w-full bg-white text-black py-6 rounded-2xl font-black uppercase tracking-[0.2em] text-sm shadow-xl hover:bg-[#FF9F1C] transition-all disabled:opacity-50">
                                Finaliser le Déploiement
                            </button>
                        </div>
                    </form>
                </div>
            </transition>

            <!-- Places List -->
            <div class="grid gap-6">
                <div v-for="place in places" :key="place.id" 
                    class="bg-[#111113]/40 backdrop-blur-md border border-white/5 rounded-[2rem] p-8 flex items-center justify-between group hover:border-[#FF9F1C]/20 transition-all duration-500">
                    <div class="flex items-center gap-8">
                        <div class="h-20 w-20 bg-black/60 rounded-[1.5rem] flex flex-col items-center justify-center border border-white/5 group-hover:border-[#FF9F1C]/30 transition-colors">
                            <span class="text-2xl mb-1">📍</span>
                            <span class="text-[10px] font-black text-[#FF9F1C] font-mono">{{ place.id.toString().padStart(3, '0') }}</span>
                        </div>
                        <div>
                            <div class="flex items-center gap-3 mb-2">
                                <h3 class="text-2xl font-black uppercase italic tracking-tighter">{{ place.nom }}</h3>
                                <span :class="place.is_active ? 'bg-green-500/10 text-green-500 border-green-500/20' : 'bg-red-500/10 text-red-500 border-red-500/20'" 
                                    class="px-3 py-1 rounded-full text-[8px] font-black uppercase tracking-widest border">
                                    {{ place.is_active ? 'Actif' : 'Inactif' }}
                                </span>
                            </div>
                            <div class="flex items-center gap-6 text-gray-500 font-bold text-[10px] uppercase tracking-widest">
                                <span>🏙️ {{ place.ville }}</span>
                                <span>🗺️ {{ place.departement }}</span>
                                <span>🧩 {{ place.riddles_count }} Énigmes</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <Link :href="route('admin.enigmas', { place: place.id })" 
                            class="bg-white/5 hover:bg-[#FF9F1C] hover:text-black px-6 py-4 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all">
                            Configurer Énigmes
                        </Link>
                        <button @click="$inertia.post(route('admin.places.toggle', { place: place.id }))" 
                            class="p-4 rounded-xl border border-white/5 hover:border-red-500/50 hover:text-red-500 transition-all group/btn">
                            <span class="text-lg group-hover/btn:scale-110 block transition-transform">⚙️</span>
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
</style>
