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

    <div class="min-h-screen font-sans flex flex-col md:flex-row transition-colors duration-300"
         :class="isDark ? 'bg-[#0A0A0B] text-white' : 'bg-white text-gray-900'">
        
        <!-- Left Side: Branding/Image -->
        <div class="hidden md:flex md:w-1/2 bg-[#1A1A1A] relative overflow-hidden p-12 lg:p-20 flex-col justify-between border-r dark:border-white/5 border-gray-100">
            <div class="absolute inset-0 z-0 opacity-40">
                <img src="https://images.unsplash.com/photo-1523050335192-ce67a276d112?q=80&w=1200" alt="Aventure" class="h-full w-full object-cover grayscale" />
                <div class="absolute inset-0 bg-gradient-to-br from-[#FF9F1C]/40 to-black/80"></div>
            </div>
            
            <Link href="/" class="relative z-10 flex items-center gap-3">
                <div class="bg-[#FF9F1C] p-2 rounded-lg">
                    <span class="text-white font-black text-xl">CP</span>
                </div>
                <span class="text-3xl font-black tracking-tighter uppercase italic text-white">Cityplay</span>
            </Link>

            <div class="relative z-10 space-y-6">
                <h1 class="text-6xl lg:text-8xl font-black text-white leading-none tracking-tighter italic">BON <br />RETOUR !</h1>
                <p class="text-xl text-gray-300 max-w-md font-medium leading-relaxed">Reprenez votre quête là où vous l'avez laissée et dominez le classement.</p>
            </div>

            <div class="relative z-10 text-gray-500 text-[10px] font-black uppercase tracking-[0.4em]">
                System Status: Online
            </div>
        </div>

        <!-- Right Side: Login Form -->
        <div class="flex-1 flex items-center justify-center p-8 lg:p-20 relative overflow-y-auto">
            <!-- Back to Home Button -->
            <Link href="/" class="absolute top-8 right-8 flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-gray-400 hover:text-[#FF9F1C] transition-all">
                <span>←</span>
                <span>Retour à l'accueil</span>
            </Link>

            <div class="w-full max-w-md space-y-12">
                <div class="md:hidden flex justify-center mb-12">
                    <Link href="/" class="flex items-center gap-2">
                        <div class="bg-[#FF9F1C] p-2 rounded-lg">
                            <span class="text-white font-black text-xl">CP</span>
                        </div>
                        <span class="text-3xl font-black tracking-tighter uppercase italic">Cityplay</span>
                    </Link>
                </div>

                <div class="space-y-4">
                    <h2 class="text-4xl lg:text-5xl font-black uppercase italic tracking-tighter">Accès <span class="text-[#FF9F1C]">Terminal</span></h2>
                    <p class="text-gray-500 font-medium">Entrez vos identifiants pour synchroniser vos données.</p>
                </div>

                <div v-if="status" class="bg-[#4CAF50]/10 border border-[#4CAF50]/20 p-5 rounded-2xl text-[#4CAF50] text-xs font-black uppercase tracking-widest">
                    {{ status }}
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black uppercase tracking-widest text-gray-400">Identifiant Matrix (Email)</label>
                        <input 
                            v-model="form.email"
                            type="email" 
                            required
                            class="w-full dark:bg-white/5 bg-gray-50 border dark:border-white/5 border-gray-100 rounded-2xl py-5 px-6 focus:ring-2 focus:ring-[#FF9F1C] transition-all font-bold dark:text-white text-gray-900"
                            placeholder="votre@email.com"
                        />
                        <div v-if="form.errors.email" class="text-red-500 text-[10px] font-black uppercase mt-2 tracking-widest">{{ form.errors.email }}</div>
                    </div>

                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <label class="block text-[10px] font-black uppercase tracking-widest text-gray-400">Code d'Accès (Password)</label>
                            <Link v-if="canResetPassword" :href="route('password.request')" class="text-[10px] font-black uppercase tracking-widest text-[#FF9F1C] hover:underline">Oublié ?</Link>
                        </div>
                        <input 
                            v-model="form.password"
                            type="password" 
                            required
                            class="w-full dark:bg-white/5 bg-gray-50 border dark:border-white/5 border-gray-100 rounded-2xl py-5 px-6 focus:ring-2 focus:ring-[#FF9F1C] transition-all font-bold dark:text-white text-gray-900"
                            placeholder="••••••••"
                        />
                        <div v-if="form.errors.password" class="text-red-500 text-[10px] font-black uppercase mt-2 tracking-widest">{{ form.errors.password }}</div>
                    </div>

                    <div class="flex items-center">
                        <input v-model="form.remember" type="checkbox" class="rounded-lg border-gray-300 text-[#FF9F1C] focus:ring-[#FF9F1C] dark:bg-white/5" />
                        <span class="ms-3 text-xs font-black uppercase tracking-widest text-gray-400">Maintenir la Session</span>
                    </div>

                    <button 
                        type="submit" 
                        :disabled="form.processing"
                        class="w-full rounded-2xl dark:bg-white bg-gray-900 dark:text-black text-white py-6 text-xs font-black uppercase tracking-[0.3em] shadow-2xl transition-all hover:scale-[1.02] active:scale-95 disabled:opacity-50"
                    >
                        Initialiser la Connexion
                    </button>
                </form>

                <div class="text-center pt-8 border-t dark:border-white/5 border-gray-100">
                    <p class="text-gray-500 font-medium">Nouveau sujet ? 
                        <Link :href="route('register')" class="text-[#FF9F1C] font-black uppercase tracking-widest hover:underline">Créer un profil</Link>
                    </p>
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
