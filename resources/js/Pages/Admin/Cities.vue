<script setup>
import AdminLayout from './AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    cities: Array,
});

const showForm = ref(false);
const form = useForm({
    name: '',
    description: '',
    departement: '',
});

const submit = () => {
    form.post(route('admin.cities.store'), {
        onSuccess: () => {
            showForm.value = false;
            form.reset();
        },
    });
};
</script>

<template>
    <Head title="Matrice des Cités" />
    <AdminLayout>
        <div class="space-y-8 lg:space-y-12">
            <!-- Header Section -->
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                <div>
                    <h1 class="text-4xl lg:text-6xl font-black tracking-tighter uppercase italic leading-none dark:text-white text-gray-900">
                        Matrice des <span class="text-[#FF9F1C]">Cités</span>
                    </h1>
                    <p class="text-gray-500 font-bold uppercase tracking-[0.3em] text-[10px] mt-4">
                        Initialisation des zones de déploiement urbain
                    </p>
                </div>
                <button @click="showForm = !showForm" 
                    :class="showForm ? 'bg-red-500 shadow-lg text-white' : 'bg-[#FF9F1C] shadow-lg text-black'"
                    class="px-10 py-5 rounded-2xl font-black uppercase tracking-widest text-xs transition-all hover:scale-105 active:scale-95">
                    {{ showForm ? 'Annuler l\'Opération' : 'Initialiser une Cité' }}
                </button>
            </div>

            <!-- Add City Form -->
            <transition name="fade">
                <div v-if="showForm" class="dark:bg-[#111113] bg-white p-6 lg:p-10 rounded-[2rem] lg:rounded-[2.5rem] border dark:border-white/5 border-gray-200 shadow-2xl relative overflow-hidden">
                    <form @submit.prevent="submit" class="space-y-8 relative z-10">
                        <div class="grid gap-6 grid-cols-1 md:grid-cols-2">
                            <div class="space-y-3">
                                <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500">Nom de la Cité</label>
                                <input v-model="form.name" type="text" class="w-full dark:bg-black/40 bg-gray-50 border dark:border-white/5 border-gray-200 rounded-2xl py-4 px-6 focus:ring-2 focus:ring-[#FF9F1C] dark:text-white text-gray-900 font-bold" placeholder="Ex: Ouidah la Mystique" />
                            </div>
                            <div class="space-y-3">
                                <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500">Département</label>
                                <input v-model="form.departement" type="text" class="w-full dark:bg-black/40 bg-gray-50 border dark:border-white/5 border-gray-200 rounded-2xl py-4 px-6 focus:ring-2 focus:ring-[#FF9F1C] dark:text-white text-gray-900 font-bold" placeholder="Atlantique" />
                            </div>
                        </div>
                        <div class="space-y-3">
                            <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-gray-500">Narratif de la Cité (Description)</label>
                            <textarea v-model="form.description" rows="4" class="w-full dark:bg-black/40 bg-gray-50 border dark:border-white/5 border-gray-200 rounded-2xl py-4 px-6 focus:ring-2 focus:ring-[#FF9F1C] dark:text-white text-gray-900 font-bold" placeholder="L'histoire et l'ambiance qui accueillent l'explorateur..."></textarea>
                        </div>
                        <div class="pt-4">
                            <button type="submit" :disabled="form.processing" class="w-full bg-gray-900 dark:bg-white text-white dark:text-black py-6 rounded-2xl font-black uppercase tracking-[0.2em] text-sm shadow-xl hover:bg-[#FF9F1C] hover:text-black transition-all disabled:opacity-50">
                                Finaliser l'Initialisation
                            </button>
                        </div>
                    </form>
                </div>
            </transition>

            <!-- Cities List -->
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                <div v-for="city in cities" :key="city.id" 
                    class="dark:bg-[#111113]/40 bg-white border dark:border-white/5 border-gray-200 rounded-[2.5rem] overflow-hidden flex flex-col group hover:border-[#FF9F1C]/20 transition-all duration-500 shadow-sm">
                    <div class="p-8 space-y-6 flex-1">
                        <div class="flex justify-between items-start">
                            <div class="h-14 w-14 dark:bg-black/60 bg-gray-50 rounded-2xl flex items-center justify-center text-2xl border dark:border-white/5 border-gray-200 group-hover:border-[#FF9F1C]/30 transition-colors">
                                🏙️
                            </div>
                            <span class="bg-[#FF9F1C]/10 text-[#FF9F1C] px-4 py-1.5 rounded-full text-[8px] font-black uppercase tracking-widest border border-[#FF9F1C]/20">
                                {{ city.places_count }} Secteurs
                            </span>
                        </div>
                        <div>
                            <h3 class="text-2xl font-black uppercase italic tracking-tighter dark:text-white text-gray-900 mb-2">{{ city.name }}</h3>
                            <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-4 italic">{{ city.departement }}</p>
                            <p class="text-xs dark:text-gray-400 text-gray-600 line-clamp-3 leading-relaxed font-medium">
                                {{ city.description }}
                            </p>
                        </div>
                    </div>
                    <div class="p-2">
                        <Link :href="route('admin.cities.places', { city: city.id })" 
                            class="w-full flex items-center justify-center gap-3 bg-gray-100 dark:bg-white/5 dark:text-white text-gray-700 py-6 rounded-[1.8rem] text-[10px] font-black uppercase tracking-widest hover:bg-[#FF9F1C] hover:text-black transition-all">
                            Accéder aux Secteurs
                            <span class="text-lg">→</span>
                        </Link>
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
