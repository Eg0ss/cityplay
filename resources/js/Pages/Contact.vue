<script setup>
import { onMounted, ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const isDark = ref(true);

const toggleTheme = () => {
    isDark.value = !isDark.value;
    localStorage.setItem('cityplay-theme', isDark.value ? 'dark' : 'light');
    updateTheme();
};

const updateTheme = () => {
    if (isDark.value) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
};

onMounted(() => {
    const savedTheme = localStorage.getItem('cityplay-theme');
    if (savedTheme) {
        isDark.value = savedTheme === 'dark';
    }
    updateTheme();
});

const form = useForm({
    name: '',
    email: '',
    message: '',
});

const submit = () => {
    // Logic for form submission
};
</script>

<template>
    <Head title="Contact - Cityplay" />
    <div class="min-h-screen font-sans transition-colors duration-300 pb-20"
         :class="isDark ? 'bg-[#0A0A0B] text-white' : 'bg-gray-50 text-gray-900'">
        
        <!-- Navigation Header -->
        <nav class="max-w-7xl mx-auto px-6 py-10 flex justify-between items-center">
            <Link href="/" class="flex items-center gap-3">
                <div class="h-10 w-10 flex items-center justify-center">
                    <img src="/images/cityplay.png" class="h-full w-full object-contain" alt="Logo" />
                </div>
                <span class="text-2xl font-black tracking-tighter uppercase italic">Cityplay</span>
            </Link>
            <button @click="toggleTheme" class="h-12 w-12 rounded-2xl dark:bg-white/5 bg-white shadow-xl flex items-center justify-center text-xl hover:scale-110 transition-all border dark:border-white/5 border-gray-100">
                {{ isDark ? '🌙' : '☀️' }}
            </button>
        </nav>

        <div class="max-w-7xl mx-auto px-6 space-y-24">
            <!-- Header -->
            <div class="text-center space-y-6">
                <h1 class="text-5xl lg:text-8xl font-black tracking-tighter uppercase italic leading-none">
                    STATION DE <br /><span class="text-[#FF9F1C]">LIAISON</span>
                </h1>
                <p class="text-xl font-medium dark:text-gray-400 text-gray-600 max-w-2xl mx-auto">
                    Une question ? Une suggestion d'énigme ? Notre équipe d'archivistes est prête à recevoir votre message.
                </p>
            </div>

            <!-- Contact Form Card -->
            <div class="mx-auto max-w-4xl dark:bg-[#111113] bg-white p-8 lg:p-20 rounded-[4rem] border dark:border-white/5 border-gray-100 shadow-2xl relative overflow-hidden">
                <!-- Background Decoration -->
                <div class="absolute top-0 right-0 p-12 opacity-5 pointer-events-none">
                    <span class="text-9xl">✉️</span>
                </div>

                <form @submit.prevent="submit" class="space-y-12 relative z-10">
                    <div class="grid gap-10 md:grid-cols-2">
                        <div class="space-y-4">
                            <label class="text-[10px] font-black uppercase tracking-[0.3em] dark:text-gray-400 text-gray-500 ml-4 italic">Identité de l'Expéditeur</label>
                            <input v-model="form.name" type="text" 
                                class="w-full dark:bg-white/5 bg-gray-50 border-2 dark:border-white/5 border-gray-100 rounded-3xl py-6 px-8 font-black uppercase tracking-widest focus:ring-2 focus:ring-[#FF9F1C] focus:border-transparent transition-all dark:text-white text-gray-900" 
                                placeholder="VOTRE NOM" />
                        </div>
                        <div class="space-y-4">
                            <label class="text-[10px] font-black uppercase tracking-[0.3em] dark:text-gray-400 text-gray-500 ml-4 italic">Canal de Retour (Email)</label>
                            <input v-model="form.email" type="email" 
                                class="w-full dark:bg-white/5 bg-gray-50 border-2 dark:border-white/5 border-gray-100 rounded-3xl py-6 px-8 font-black uppercase tracking-widest focus:ring-2 focus:ring-[#FF9F1C] focus:border-transparent transition-all dark:text-white text-gray-900" 
                                placeholder="VOTRE@EMAIL.COM" />
                        </div>
                    </div>
                    <div class="space-y-4">
                        <label class="text-[10px] font-black uppercase tracking-[0.3em] dark:text-gray-400 text-gray-500 ml-4 italic">Contenu de la Transmission</label>
                        <textarea v-model="form.message" 
                            class="w-full dark:bg-white/5 bg-gray-50 border-2 dark:border-white/5 border-gray-100 rounded-3xl py-6 px-8 font-black uppercase tracking-widest focus:ring-2 focus:ring-[#FF9F1C] focus:border-transparent transition-all h-48 dark:text-white text-gray-900" 
                            placeholder="ÉCRIVEZ VOTRE MESSAGE ICI..."></textarea>
                    </div>
                    <div class="pt-6">
                        <button type="submit" 
                            class="w-full dark:bg-white bg-gray-900 dark:text-black text-white py-8 rounded-3xl font-black uppercase tracking-[0.4em] text-xs shadow-2xl hover:scale-[1.02] active:scale-95 transition-all">
                            Initialiser l'Envoi
                        </button>
                    </div>
                </form>
            </div>

            <!-- Info Cards -->
            <div class="grid md:grid-cols-2 gap-8 max-w-4xl mx-auto">
                <div class="p-10 dark:bg-white/5 bg-white rounded-[3rem] border dark:border-white/5 border-gray-100 flex items-center gap-8">
                    <span class="text-4xl">📍</span>
                    <div>
                        <h4 class="font-black uppercase italic tracking-tighter dark:text-white text-gray-900">Quartier Général</h4>
                        <p class="text-gray-500 font-medium">Cotonou, Bénin</p>
                    </div>
                </div>
                <div class="p-10 dark:bg-white/5 bg-white rounded-[3rem] border dark:border-white/5 border-gray-100 flex items-center gap-8">
                    <span class="text-4xl">🛰️</span>
                    <div>
                        <h4 class="font-black uppercase italic tracking-tighter dark:text-white text-gray-900">Support 24/7</h4>
                        <p class="text-gray-500 font-medium">contact@cityplay.com</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800;900&display=swap');
.font-sans { font-family: 'Outfit', sans-serif; }
</style>