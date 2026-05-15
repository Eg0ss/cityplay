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
    lat: 6.3667,
    lng: 2.4333,
    rayon_marge: 50,
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
    <Head title="Gestion des Lieux" />
    <AdminLayout>
        <div class="space-y-12">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-4xl font-black text-[#1A1A1A]">Gestion des Lieux</h1>
                    <p class="text-gray-500 mt-2">Créez et configurez les zones de jeu au Bénin.</p>
                </div>
                <button @click="showForm = !showForm" class="bg-[#FF9F1C] text-white px-8 py-4 rounded-2xl font-bold shadow-lg shadow-orange-100 hover:scale-105 transition-all">
                    {{ showForm ? 'Annuler' : 'Ajouter un Lieu' }}
                </button>
            </div>

            <!-- Add Place Form -->
            <div v-if="showForm" class="bg-white p-12 rounded-3xl shadow-xl border border-gray-100">
                <form @submit.prevent="submit" class="grid gap-8 md:grid-cols-2">
                    <div class="space-y-2">
                        <label class="block text-xs font-bold uppercase tracking-widest text-gray-400">Nom du Lieu</label>
                        <input v-model="form.nom" type="text" class="w-full border-gray-100 bg-gray-50 rounded-xl py-4 px-6 focus:ring-2 focus:ring-[#FF9F1C]" placeholder="La Porte du Non-Retour" />
                    </div>
                    <div class="space-y-2">
                        <label class="block text-xs font-bold uppercase tracking-widest text-gray-400">Ville</label>
                        <input v-model="form.ville" type="text" class="w-full border-gray-100 bg-gray-50 rounded-xl py-4 px-6 focus:ring-2 focus:ring-[#FF9F1C]" placeholder="Ouidah" />
                    </div>
                    <div class="space-y-2">
                        <label class="block text-xs font-bold uppercase tracking-widest text-gray-400">Département</label>
                        <input v-model="form.departement" type="text" class="w-full border-gray-100 bg-gray-50 rounded-xl py-4 px-6 focus:ring-2 focus:ring-[#FF9F1C]" placeholder="Atlantique" />
                    </div>
                    <div class="space-y-2">
                        <label class="block text-xs font-bold uppercase tracking-widest text-gray-400">Rayon Marge (m)</label>
                        <input v-model="form.rayon_marge" type="number" class="w-full border-gray-100 bg-gray-50 rounded-xl py-4 px-6 focus:ring-2 focus:ring-[#FF9F1C]" />
                    </div>
                    <div class="space-y-2">
                        <label class="block text-xs font-bold uppercase tracking-widest text-gray-400">Latitude (GPS)</label>
                        <input v-model="form.lat" type="number" step="any" class="w-full border-gray-100 bg-gray-50 rounded-xl py-4 px-6 focus:ring-2 focus:ring-[#FF9F1C]" />
                    </div>
                    <div class="space-y-2">
                        <label class="block text-xs font-bold uppercase tracking-widest text-gray-400">Longitude (GPS)</label>
                        <input v-model="form.lng" type="number" step="any" class="w-full border-gray-100 bg-gray-50 rounded-xl py-4 px-6 focus:ring-2 focus:ring-[#FF9F1C]" />
                    </div>
                    <div class="md:col-span-2">
                        <button type="submit" :disabled="form.processing" class="w-full bg-[#1A1A1A] text-white py-5 rounded-2xl font-bold shadow-xl hover:bg-black transition-all disabled:opacity-50">
                            Enregistrer le Lieu
                        </button>
                    </div>
                </form>
            </div>

            <!-- Places Table -->
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-left">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-8 py-6 text-sm font-bold uppercase tracking-widest text-gray-400">Lieu</th>
                            <th class="px-8 py-6 text-sm font-bold uppercase tracking-widest text-gray-400">Ville</th>
                            <th class="px-8 py-6 text-sm font-bold uppercase tracking-widest text-gray-400">Énigmes</th>
                            <th class="px-8 py-6 text-sm font-bold uppercase tracking-widest text-gray-400">Statut</th>
                            <th class="px-8 py-6 text-sm font-bold uppercase tracking-widest text-gray-400">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="place in places" :key="place.id" class="hover:bg-gray-50 transition-colors">
                            <td class="px-8 py-6 font-bold text-lg">{{ place.nom }}</td>
                            <td class="px-8 py-6 text-gray-500">{{ place.ville }} ({{ place.departement }})</td>
                            <td class="px-8 py-6">
                                <span class="bg-gray-100 px-3 py-1 rounded-full text-xs font-bold text-gray-600">{{ place.riddles_count }} énigmes</span>
                            </td>
                            <td class="px-8 py-6">
                                <span :class="place.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'" class="px-3 py-1 rounded-full text-xs font-bold">
                                    {{ place.is_active ? 'Actif' : 'Inactif' }}
                                </span>
                            </td>
                            <td class="px-8 py-6 flex gap-4">
                                <Link :href="route('admin.enigmas', { place: place.id })" class="text-[#FF9F1C] font-bold hover:underline">Gérer les Énigmes</Link>
                                <button @click="$inertia.post(route('admin.places.toggle', { place: place.id }))" class="text-gray-400 font-bold hover:text-[#1A1A1A] transition-colors">
                                    {{ place.is_active ? 'Désactiver' : 'Activer' }}
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AdminLayout>
</template>
