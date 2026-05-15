<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

defineProps({
    canResetPassword: { type: Boolean },
    status: { type: String },
});

const isDark = ref(true);

onMounted(() => {
    const savedTheme = localStorage.getItem('cityplay-theme');
    if (savedTheme) {
        isDark.value = savedTheme === 'dark';
    }
    if (isDark.value) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Connexion - Cityplay Bénin" />

    <div class="min-h-screen font-sans transition-colors duration-300 overflow-hidden relative"
         :class="isDark ? 'bg-[#0A0A0B] text-white' : 'bg-white text-gray-900'">
        
        <!-- Background Image -->
        <div class="absolute inset-0 z-0">
            <img src="/images/image_0.png" alt="Background" class="h-full w-full object-cover" />
            <div class="absolute inset-0 transition-colors"
                 :class="isDark ? 'bg-[#0A0A0B]/40' : 'bg-white/30'"></div>
        </div>

        <!-- Content Container -->
        <div class="relative z-10 min-h-screen flex items-center justify-center p-6">
            <div class="w-full max-w-7xl flex flex-col lg:flex-row items-center gap-12 lg:gap-24">
                
                <!-- Left Side: Text Branding (Visible on Desktop) -->
                <div class="hidden lg:flex flex-1 flex-col space-y-8">
                    <div class="space-y-4">
                        <span class="inline-block text-[#FF9F1C] font-black text-sm tracking-[0.4em] uppercase italic">Adventure Platform</span>
                        <h1 class="text-7xl lg:text-9xl font-black uppercase italic tracking-tighter leading-none"
                            :class="isDark ? 'text-white' : 'text-gray-900'">
                            EXPLOREZ <br /> LE <span class="text-[#FF9F1C]">BÉNIN !</span>
                        </h1>
                        <p class="text-xl font-medium max-w-xl leading-relaxed"
                           :class="isDark ? 'text-gray-400' : 'text-gray-600'">
                            Vivez une aventure unique à travers les rues de Cotonou, les palais d'Abomey et les cités lacustres. Relevez des défis réels.
                        </p>
                    </div>
                    <div class="flex gap-4">
                        <Link href="/" class="bg-gray-900 dark:bg-white text-white dark:text-black px-10 py-5 rounded-2xl font-black uppercase tracking-widest text-xs hover:scale-105 transition-all shadow-2xl">
                            Démarrer l'Aventure
                        </Link>
                        <button class="dark:bg-white/5 bg-gray-100 px-10 py-5 rounded-2xl font-black uppercase tracking-widest text-xs hover:bg-[#FF9F1C] hover:text-black transition-all border dark:border-white/10 border-gray-200">
                            Voir le Classement
                        </button>
                    </div>
                </div>

                <!-- Right Side: Login Card -->
                <div class="w-full max-w-md dark:bg-[#111113]/90 bg-white/90 backdrop-blur-xl p-8 lg:p-10 rounded-[2.5rem] shadow-2xl border dark:border-white/5 border-gray-100 relative">
                    <!-- Back Button -->
                    <Link href="/" class="absolute top-6 right-8 text-[10px] font-black uppercase tracking-widest text-gray-400 hover:text-[#FF9F1C] transition-colors">
                        ← Retour
                    </Link>

                    <div class="text-center mb-8">
                        <div class="md:hidden flex justify-center mb-6">
                            <div class="bg-[#FF9F1C] p-2 rounded-lg">
                                <span class="text-white font-black text-xl">CP</span>
                            </div>
                        </div>
                        <h2 class="text-2xl font-black uppercase italic tracking-tighter text-[#FF9F1C]">Accès Terminal</h2>
                        <p class="text-[10px] font-black uppercase tracking-widest text-gray-500 mt-2">Authentification Requise</p>
                    </div>

                    <div v-if="status" class="mb-6 bg-[#4CAF50]/10 border border-[#4CAF50]/20 p-4 rounded-xl text-[#4CAF50] text-[10px] font-black uppercase tracking-widest">
                        {{ status }}
                    </div>

                    <form @submit.prevent="submit" class="space-y-5">
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black uppercase tracking-widest text-gray-400 ms-2">Identifiant (Email)</label>
                            <input 
                                v-model="form.email"
                                type="email" 
                                required
                                class="w-full dark:bg-white/5 bg-gray-50 border dark:border-white/5 border-gray-200 rounded-2xl py-4 px-6 focus:ring-2 focus:ring-[#FF9F1C] transition-all font-bold dark:text-white text-gray-900"
                                placeholder="votre@email.com"
                            />
                            <div v-if="form.errors.email" class="text-red-500 text-[9px] font-black uppercase mt-1 ms-2 tracking-widest">{{ form.errors.email }}</div>
                        </div>

                        <div class="space-y-2">
                            <div class="flex justify-between px-2">
                                <label class="block text-[10px] font-black uppercase tracking-widest text-gray-400">Code d'Accès</label>
                                <Link v-if="canResetPassword" :href="route('password.request')" class="text-[10px] font-black uppercase tracking-widest text-[#FF9F1C] hover:underline">Perdu ?</Link>
                            </div>
                            <input 
                                v-model="form.password"
                                type="password" 
                                required
                                class="w-full dark:bg-white/5 bg-gray-50 border dark:border-white/5 border-gray-200 rounded-2xl py-4 px-6 focus:ring-2 focus:ring-[#FF9F1C] transition-all font-bold dark:text-white text-gray-900"
                                placeholder="••••••••"
                            />
                            <div v-if="form.errors.password" class="text-red-500 text-[9px] font-black uppercase mt-1 ms-2 tracking-widest">{{ form.errors.password }}</div>
                        </div>

                        <div class="flex items-center px-2">
                            <input v-model="form.remember" type="checkbox" class="rounded-lg border-gray-300 text-[#FF9F1C] focus:ring-[#FF9F1C] dark:bg-white/5" />
                            <span class="ms-3 text-[10px] font-black uppercase tracking-widest text-gray-400">Maintenir la Session</span>
                        </div>

                        <button 
                            type="submit" 
                            :disabled="form.processing"
                            class="w-full rounded-2xl dark:bg-white bg-gray-900 dark:text-black text-white py-5 text-xs font-black uppercase tracking-[0.3em] shadow-xl transition-all hover:scale-[1.02] active:scale-95 disabled:opacity-50"
                        >
                            Initialiser
                        </button>
                    </form>

                    <div class="relative my-8 flex items-center">
                        <div class="flex-grow border-t dark:border-white/5 border-gray-100"></div>
                        <span class="mx-4 text-[9px] font-black text-gray-500 uppercase tracking-widest">Ou rejoindre</span>
                        <div class="flex-grow border-t dark:border-white/5 border-gray-100"></div>
                    </div>

                    <div class="text-center">
                        <p class="text-[10px] font-black uppercase tracking-widest text-gray-500">
                            Nouveau ? 
                            <Link :href="route('register')" class="text-[#FF9F1C] hover:underline">Créer un profil</Link>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800;900&display=swap');
.font-sans { font-family: 'Outfit', sans-serif; }
</style>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800;900&display=swap');
.font-sans { font-family: 'Outfit', sans-serif; }
</style>
