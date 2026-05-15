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
});

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
        <div class="space-y-12">
            <!-- Header Section -->
            <div class="flex justify-between items-end">
                <div>
                    <div class="flex items-center gap-4 mb-4">
                        <Link :href="route('admin.places')" class="h-10 w-10 bg-white/5 rounded-xl flex items-center justify-center hover:bg-[#FF9F1C] hover:text-black transition-all group">
                            <span class="group-hover:-translate-x-1 transition-transform">⬅️</span>
                        </Link>
                        <span class="text-[10px] font-black uppercase tracking-[0.3em] text-[#FF9F1C]">Secteur: {{ place.ville }}</span>
                    </div>
                    <h1 class="text-6xl font-black tracking-tighter uppercase italic leading-none">
                        Quest <span class="text-[#FF9F1C]">Editor</span>
                    </h1>
                    <p class="text-gray-500 font-bold uppercase tracking-[0.2em] text-[10px] mt-4">
                        Lieu cible: <span class="text-white">{{ place.nom }}</span>
                    </p>
                </div>
                <button @click="showForm = !showForm" 
                    :class="showForm ? 'bg-red-500 shadow-[0_0_20px_rgba(239,68,68,0.3)]' : 'bg-white shadow-[0_0_20px_rgba(255,255,255,0.1)]'"
                    class="text-black px-10 py-5 rounded-2xl font-black uppercase tracking-widest text-xs transition-all hover:scale-105 active:scale-95">
                    {{ showForm ? 'Fermer l\'Atelier' : 'Forger une Énigme' }}
                </button>
            </div>

            <!-- Add Enigma Workshop (Form) -->
            <transition name="fade">
                <div v-if="showForm" class="bg-[#111113] p-10 rounded-[2.5rem] border border-white/5 shadow-2xl relative">
                    <form @submit.prevent="submit" class="space-y-8">
                        <div class="grid gap-8 md:grid-cols-3">
                            <!-- Difficulty Selector -->
                            <div class="space-y-3">
                                <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500">Niveau d'Énergie</label>
                                <div class="grid grid-cols-3 gap-2">
                                    <button v-for="n in [1,2,3]" :key="n" type="button" @click="form.niveau = n"
                                        :class="[
                                            form.niveau === n ? (n===1 ? 'bg-blue-500 text-black shadow-[0_0_15px_rgba(59,130,246,0.5)]' : n===2 ? 'bg-yellow-500 text-black shadow-[0_0_15px_rgba(234,179,8,0.5)]' : 'bg-red-500 text-black shadow-[0_0_15px_rgba(239,68,68,0.5)]') : 'bg-black/40 border border-white/5 text-gray-500'
                                        ]"
                                        class="py-4 rounded-xl font-black transition-all">
                                        LVL {{ n }}
                                    </button>
                                </div>
                            </div>

                            <!-- Real Answer -->
                            <div class="md:col-span-2 space-y-3">
                                <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500">Code de Validation (Réponse)</label>
                                <input v-model="form.reponse" type="text" class="w-full bg-black/40 border border-white/5 rounded-2xl py-4 px-6 focus:ring-2 focus:ring-[#FF9F1C] focus:border-transparent text-white font-bold" :placeholder="place.nom" />
                            </div>
                        </div>

                        <!-- Riddle Content -->
                        <div class="space-y-3">
                            <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500">Texte de l'Énigme (L'indice)</label>
                            <textarea v-model="form.description" rows="4" class="w-full bg-black/40 border border-white/5 rounded-2xl py-4 px-6 focus:ring-2 focus:ring-[#FF9F1C] focus:border-transparent text-white placeholder-gray-700 font-bold" placeholder="Décrivez l'endroit sans le nommer..."></textarea>
                        </div>

                        <!-- MCQ Options (Only for Level 1 & 2) -->
                        <div v-if="form.niveau < 3" class="space-y-4">
                            <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500">Propositions de Réponses (QCM)</label>
                            <div class="grid gap-4 md:grid-cols-2">
                                <div v-for="(opt, index) in form.mcq_options" :key="index" class="relative">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-[10px] font-black text-[#FF9F1C]">{{ index + 1 }}</span>
                                    <input v-model="form.mcq_options[index]" type="text" class="w-full bg-black/40 border border-white/5 rounded-2xl py-4 pl-10 pr-6 focus:ring-1 focus:ring-[#FF9F1C] text-sm text-white" :placeholder="'Option ' + (index+1)" />
                                </div>
                            </div>
                        </div>

                        <!-- Photos Slots -->
                        <div class="space-y-3">
                            <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500">Artefacts Visuels (Max 3 Photos)</label>
                            <div class="flex gap-4">
                                <div v-for="i in 3" :key="i" class="h-24 w-32 border-2 border-dashed border-white/5 rounded-2xl flex flex-col items-center justify-center cursor-pointer hover:border-[#FF9F1C]/30 transition-all group">
                                    <span class="text-xl group-hover:scale-110 transition-transform">📸</span>
                                    <span class="text-[8px] font-bold text-gray-600 uppercase mt-1">Slot {{ i }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="pt-6">
                            <button type="submit" :disabled="form.processing" class="w-full bg-[#FF9F1C] text-black py-6 rounded-2xl font-black uppercase tracking-[0.2em] text-sm shadow-[0_10px_30px_rgba(255,159,28,0.2)] hover:scale-[1.02] transition-all disabled:opacity-50">
                                Inscrire l'Énigme dans la Matrice
                            </button>
                        </div>
                    </form>
                </div>
            </transition>

            <!-- Enigmas List Grouped by Level -->
            <div class="grid gap-12">
                <div v-for="level in [3, 2, 1]" :key="level" class="space-y-6">
                    <div class="flex items-center gap-4">
                        <div :class="[level===1 ? 'bg-blue-500' : level===2 ? 'bg-yellow-500' : 'bg-red-500']" class="h-1 w-12 rounded-full"></div>
                        <h3 class="text-xl font-black uppercase tracking-widest italic">
                            {{ level === 1 ? 'Facile' : level === 2 ? 'Intermédiaire' : 'Difficile' }}
                        </h3>
                    </div>

                    <div class="grid gap-6 md:grid-cols-2">
                        <div v-for="enigma in enigmas.filter(e => e.niveau === level)" :key="enigma.id" 
                            class="bg-[#111113]/40 border border-white/5 p-8 rounded-[2rem] group hover:border-white/20 transition-all">
                            <div class="flex justify-between items-start mb-6">
                                <div class="text-[8px] font-black uppercase tracking-[0.3em] text-[#FF9F1C] bg-[#FF9F1C]/10 px-3 py-1 rounded-full border border-[#FF9F1C]/20">
                                    ID-{{ enigma.id.toString().padStart(4, '0') }}
                                </div>
                                <div class="flex gap-2">
                                    <button class="h-8 w-8 bg-white/5 rounded-lg flex items-center justify-center hover:bg-white/10 transition-all">✏️</button>
                                    <button class="h-8 w-8 bg-white/5 rounded-lg flex items-center justify-center hover:bg-red-500/20 hover:text-red-500 transition-all">🗑️</button>
                                </div>
                            </div>
                            <p class="text-gray-300 font-medium italic mb-6 leading-relaxed">"{{ enigma.description }}"</p>
                            
                            <div class="flex items-center justify-between pt-6 border-t border-white/5">
                                <div class="space-y-1">
                                    <p class="text-[8px] font-black uppercase text-gray-600 tracking-widest">Réponse</p>
                                    <p class="text-sm font-black uppercase text-white">{{ enigma.reponse }}</p>
                                </div>
                                <div v-if="enigma.mcq_options" class="flex -space-x-2">
                                    <div v-for="opt in 3" :key="opt" class="h-6 w-6 rounded-full bg-gray-800 border-2 border-[#111113] flex items-center justify-center text-[8px] font-bold">
                                        {{ opt }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Empty State for Level -->
                        <div v-if="enigmas.filter(e => e.niveau === level).length === 0" 
                            class="md:col-span-2 py-16 text-center border-2 border-dashed border-white/5 rounded-[2.5rem] flex flex-col items-center justify-center opacity-30">
                            <span class="text-4xl mb-4 grayscale">🔒</span>
                            <p class="text-[10px] font-black uppercase tracking-[0.4em]">Aucune énigme déployée pour ce niveau</p>
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
