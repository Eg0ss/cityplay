<script setup>
import AdminLayout from './AdminLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    place: Object,
    enigmas: Array,
});

const showForm = ref(false);
const form = useForm({
    level: 1,
    description: '',
    answer: '',
    latitude: props.place.latitude,
    longitude: props.place.longitude,
});

const submit = () => {
    form.post(route('admin.enigmas.store', { place: props.place.id }), {
        onSuccess: () => {
            showForm.value = false;
            form.reset();
        },
    });
};
</script>

<template>
    <Head :title="'Énigmes - ' + place.name" />
    <AdminLayout>
        <div class="space-y-12">
            <div class="flex justify-between items-center">
                <div>
                    <div class="flex items-center gap-4">
                        <Link :href="route('admin.places')" class="text-[#FF9F1C] font-bold hover:underline">← Retour</Link>
                        <h1 class="text-4xl font-black text-[#1A1A1A]">Énigmes : {{ place.name }}</h1>
                    </div>
                    <p class="text-gray-500 mt-2">Gérez les défis par niveau pour ce lieu spécifique.</p>
                </div>
                <button @click="showForm = !showForm" class="bg-[#1A1A1A] text-white px-8 py-4 rounded-2xl font-bold shadow-lg hover:bg-black transition-all">
                    {{ showForm ? 'Annuler' : 'Ajouter une Énigme' }}
                </button>
            </div>

            <!-- Add Enigma Form -->
            <div v-if="showForm" class="bg-white p-12 rounded-3xl shadow-xl border border-gray-100">
                <form @submit.prevent="submit" class="grid gap-8 md:grid-cols-2">
                    <div class="space-y-2">
                        <label class="block text-xs font-bold uppercase tracking-widest text-gray-400">Niveau de difficulté</label>
                        <select v-model="form.level" class="w-full border-gray-100 bg-gray-50 rounded-xl py-4 px-6 focus:ring-2 focus:ring-[#FF9F1C]">
                            <option :value="1">🔵 Niveau 1 (Débutant)</option>
                            <option :value="2">🟡 Niveau 2 (Intermédiaire)</option>
                            <option :value="3">🔴 Niveau 3 (Expert)</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="block text-xs font-bold uppercase tracking-widest text-gray-400">Réponse attendue</label>
                        <input v-model="form.answer" type="text" class="w-full border-gray-100 bg-gray-50 rounded-xl py-4 px-6 focus:ring-2 focus:ring-[#FF9F1C]" placeholder="Ex: Dahomey" />
                    </div>
                    <div class="md:col-span-2 space-y-2">
                        <label class="block text-xs font-bold uppercase tracking-widest text-gray-400">Description / Énigme</label>
                        <textarea v-model="form.description" class="w-full border-gray-100 bg-gray-50 rounded-xl py-4 px-6 focus:ring-2 focus:ring-[#FF9F1C] h-40" placeholder="Écrivez le texte de l'énigme ici..."></textarea>
                    </div>
                    <div class="space-y-2">
                        <label class="block text-xs font-bold uppercase tracking-widest text-gray-400">Latitude Street View (Optionnel)</label>
                        <input v-model="form.latitude" type="number" step="any" class="w-full border-gray-100 bg-gray-50 rounded-xl py-4 px-6 focus:ring-2 focus:ring-[#FF9F1C]" />
                    </div>
                    <div class="space-y-2">
                        <label class="block text-xs font-bold uppercase tracking-widest text-gray-400">Longitude Street View (Optionnel)</label>
                        <input v-model="form.longitude" type="number" step="any" class="w-full border-gray-100 bg-gray-50 rounded-xl py-4 px-6 focus:ring-2 focus:ring-[#FF9F1C]" />
                    </div>
                    <div class="md:col-span-2">
                        <button type="submit" :disabled="form.processing" class="w-full bg-[#FF9F1C] text-white py-5 rounded-2xl font-bold shadow-xl hover:bg-[#e68a00] transition-all disabled:opacity-50">
                            Créer l'Énigme
                        </button>
                    </div>
                </form>
            </div>

            <!-- Enigmas List grouped by Level -->
            <div class="grid gap-8">
                <div v-for="level in [1, 2, 3]" :key="level" class="space-y-6">
                    <h3 class="text-2xl font-bold flex items-center gap-2">
                        <span v-if="level === 1">🔵 Niveau 1</span>
                        <span v-else-if="level === 2">🟡 Niveau 2</span>
                        <span v-else>🔴 Niveau 3</span>
                    </h3>
                    <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                        <div v-for="enigma in enigmas.filter(e => e.level === level)" :key="enigma.id" class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100">
                            <p class="text-gray-700 italic mb-4 line-clamp-3">"{{ enigma.description }}"</p>
                            <div class="flex items-center justify-between pt-4 border-t border-gray-50">
                                <span class="text-xs font-bold uppercase tracking-widest text-gray-400">Réponse: {{ enigma.answer }}</span>
                                <div class="flex gap-2">
                                    <button class="text-gray-400 hover:text-[#1A1A1A]">✏️</button>
                                    <button class="text-gray-400 hover:text-red-500">🗑️</button>
                                </div>
                            </div>
                        </div>
                        <div v-if="enigmas.filter(e => e.level === level).length === 0" class="md:col-span-3 py-12 text-center border-2 border-dashed border-gray-100 rounded-3xl text-gray-400">
                            Aucune énigme pour ce niveau.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
