<script setup>
import AdminLayout from './AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    place: Object,
    enigmas: Array,
});

const showForm = ref(false);
const form = useForm({
    niveau: 1,
    description: '',
    reponse: '',
    mcq_options: ['', '', '', ''], // 4 options par défaut
    photos: [],
    images: [], // Pour les fichiers
});

const onFileChange = (e) => {
    form.images = Array.from(e.target.files);
};

// Réinitialiser les options si on passe au niveau 3 (pas de QCM en niveau 3 selon votre processus)
watch(() => form.niveau, (newVal) => {
    if (newVal === 3) {
        form.mcq_options = [];
    } else if (form.mcq_options.length === 0) {
        form.mcq_options = ['', '', '', ''];
    }
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
    <Head :title="'Quest Editor - ' + place.nom" />
    <AdminLayout>
        <div class="space-y-8 lg:space-y-12">
            <!-- Header Section -->
            <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-6">
                <div>
                    <div class="flex items-center gap-4 mb-4">
                        <Link :href="route('admin.cities.places', { city: place.city_id })" class="h-10 w-10 dark:bg-white/5 bg-gray-100 rounded-xl flex items-center justify-center hover:bg-[#FF9F1C] hover:text-black transition-all group">
                            <span class="group-hover:-translate-x-1 transition-transform">⬅️</span>
                        </Link>
                        <span class="text-[10px] font-black uppercase tracking-[0.3em] text-[#FF9F1C]">Secteur: {{ place.city?.name }}</span>
                    </div>
                    <h1 class="text-4xl lg:text-6xl font-black tracking-tighter uppercase italic leading-none dark:text-white text-gray-900">
                        Quest <span class="text-[#FF9F1C]">Editor</span>
                    </h1>
                    <p class="text-gray-500 font-bold uppercase tracking-[0.2em] text-[10px] mt-4">
                        Lieu cible: <span class="dark:text-white text-gray-900">{{ place.nom }}</span>
                    </p>
                </div>
                <button @click="showForm = !showForm" 
                    :class="showForm ? 'bg-red-500 shadow-lg' : 'dark:bg-white bg-gray-900 dark:text-black text-white shadow-lg'"
                    class="px-10 py-5 rounded-2xl font-black uppercase tracking-widest text-xs transition-all hover:scale-105 active:scale-95">
                    {{ showForm ? 'Fermer l\'Atelier' : 'Forger une Énigme' }}
                </button>
            </div>

            <!-- Add Enigma Workshop (Form) -->
            <transition name="fade">
                <div v-if="showForm" class="dark:bg-[#111113] bg-white p-6 lg:p-10 rounded-[2rem] lg:rounded-[2.5rem] border dark:border-white/5 border-gray-200 shadow-2xl relative">
                    <form @submit.prevent="submit" class="space-y-6 lg:space-y-8" enctype="multipart/form-data">
                        <div class="grid gap-6 lg:gap-8 grid-cols-1 lg:grid-cols-3">
                            <!-- Difficulty Selector -->
                            <div class="space-y-3">
                                <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500">Niveau d'Énergie</label>
                                <div class="grid grid-cols-3 gap-2">
                                    <button v-for="n in [1,2,3]" :key="n" type="button" @click="form.niveau = n"
                                        :class="[
                                            form.niveau === n ? (n===1 ? 'bg-blue-500 text-black' : n===2 ? 'bg-yellow-500 text-black' : 'bg-red-500 text-black') : 'dark:bg-black/40 bg-gray-50 border dark:border-white/5 border-gray-200 text-gray-500'
                                        ]"
                                        class="py-4 rounded-xl font-black transition-all text-xs">
                                        LVL {{ n }}
                                    </button>
                                </div>
                            </div>

                            <!-- Real Answer -->
                            <div class="lg:col-span-2 space-y-3">
                                <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500">Code de Validation (Réponse)</label>
                                <input v-model="form.reponse" type="text" class="w-full dark:bg-black/40 bg-gray-50 border dark:border-white/5 border-gray-200 rounded-2xl py-4 px-6 focus:ring-2 focus:ring-[#FF9F1C] dark:text-white text-gray-900 font-bold" :placeholder="place.nom" />
                            </div>
                        </div>

                        <!-- Riddle Content -->
                        <div class="space-y-3">
                            <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500">Texte de l'Énigme (L'indice)</label>
                            <textarea v-model="form.description" rows="4" class="w-full dark:bg-black/40 bg-gray-50 border dark:border-white/5 border-gray-200 rounded-2xl py-4 px-6 focus:ring-2 focus:ring-[#FF9F1C] dark:text-white text-gray-900 font-bold" placeholder="Décrivez l'endroit sans le nommer..."></textarea>
                        </div>

                        <!-- MCQ Options (Only for Level 1 & 2) -->
                        <div v-if="form.niveau < 3" class="space-y-4">
                            <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500">Propositions de Réponses (QCM)</label>
                            <div class="grid gap-4 grid-cols-1 md:grid-cols-2">
                                <div v-for="(opt, index) in form.mcq_options" :key="index" class="relative">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-[10px] font-black text-[#FF9F1C]">{{ index + 1 }}</span>
                                    <input v-model="form.mcq_options[index]" type="text" class="w-full dark:bg-black/40 bg-gray-50 border dark:border-white/5 border-gray-200 rounded-2xl py-4 pl-10 pr-6 focus:ring-1 focus:ring-[#FF9F1C] text-sm dark:text-white text-gray-900" :placeholder="'Option ' + (index+1)" />
                                </div>
                            </div>
                        </div>

                        <!-- Photos Slots -->
                        <div class="space-y-3">
                            <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500">Artefacts Visuels (Images)</label>
                            <input type="file" multiple @change="onFileChange" class="w-full dark:bg-black/40 bg-gray-50 border dark:border-white/5 border-gray-200 rounded-2xl py-4 px-6 focus:ring-2 focus:ring-[#FF9F1C] dark:text-white text-gray-900 font-bold text-xs" accept="image/*" />
                            <p class="text-[8px] text-gray-500 uppercase font-black">Sélectionnez plusieurs fichiers pour enrichir l'énigme</p>
                        </div>

                        <div class="pt-6">
                            <button type="submit" :disabled="form.processing" class="w-full bg-[#FF9F1C] text-black py-6 rounded-2xl font-black uppercase tracking-[0.2em] text-sm shadow-xl hover:scale-[1.01] transition-all disabled:opacity-50">
                                Inscrire l'Énigme dans la Matrice
                            </button>
                        </div>
                    </form>
                </div>
            </transition>

            <!-- Enigmas List Grouped by Level -->
            <div class="grid gap-8 lg:gap-12">
                <div v-for="level in [3, 2, 1]" :key="level" class="space-y-6">
                    <div class="flex items-center gap-4">
                        <div :class="[level===1 ? 'bg-blue-500' : level===2 ? 'bg-yellow-500' : 'bg-red-500']" class="h-1 w-12 rounded-full"></div>
                        <h3 class="text-xl font-black uppercase tracking-widest italic dark:text-white text-gray-900">
                            {{ level === 1 ? 'Facile' : level === 2 ? 'Intermédiaire' : 'Difficile' }}
                        </h3>
                    </div>

                    <div class="grid gap-4 lg:gap-6 grid-cols-1 md:grid-cols-2">
                        <div v-for="enigma in enigmas.filter(e => e.niveau === level)" :key="enigma.id" 
                            class="dark:bg-[#111113]/40 bg-white border dark:border-white/5 border-gray-200 p-6 lg:p-8 rounded-[2rem] group hover:border-[#FF9F1C]/20 transition-all shadow-sm dark:shadow-none">
                            <div class="flex justify-between items-start mb-6">
                                <div class="text-[8px] font-black uppercase tracking-[0.3em] text-[#FF9F1C] dark:bg-[#FF9F1C]/10 bg-[#FF9F1C]/5 px-3 py-1 rounded-full border border-[#FF9F1C]/20">
                                    ID-{{ enigma.id.toString().padStart(4, '0') }}
                                </div>
                                <div class="flex gap-2">
                                    <button class="h-8 w-8 dark:bg-white/5 bg-gray-50 rounded-lg flex items-center justify-center hover:bg-[#FF9F1C] hover:text-black transition-all">✏️</button>
                                    <button class="h-8 w-8 dark:bg-white/5 bg-gray-50 rounded-lg flex items-center justify-center hover:bg-red-500/20 hover:text-red-500 transition-all text-gray-400">🗑️</button>
                                </div>
                            </div>
                            <p class="dark:text-gray-300 text-gray-700 font-medium italic mb-6 leading-relaxed text-sm lg:text-base">"{{ enigma.description }}"</p>
                            
                            <div class="flex items-center justify-between pt-6 border-t dark:border-white/5 border-gray-100">
                                <div class="space-y-1">
                                    <p class="text-[8px] font-black uppercase text-gray-500 tracking-widest">Réponse</p>
                                    <p class="text-xs lg:text-sm font-black uppercase dark:text-white text-gray-900">{{ enigma.reponse }}</p>
                                </div>
                                <div v-if="enigma.mcq_options" class="flex -space-x-2">
                                    <div v-for="opt in 4" :key="opt" class="h-6 w-6 rounded-full dark:bg-gray-800 bg-gray-200 border-2 dark:border-[#111113] border-white flex items-center justify-center text-[8px] font-bold">
                                        {{ opt }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Empty State for Level -->
                        <div v-if="enigmas.filter(e => e.niveau === level).length === 0" 
                            class="md:col-span-2 py-12 lg:py-16 text-center border-2 border-dashed dark:border-white/5 border-gray-200 rounded-[2rem] lg:rounded-[2.5rem] flex flex-col items-center justify-center opacity-30">
                            <span class="text-3xl lg:text-4xl mb-4 grayscale">🔒</span>
                            <p class="text-[10px] font-black uppercase tracking-[0.4em] dark:text-white text-gray-900">Aucune énigme déployée</p>
                        </div>
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
