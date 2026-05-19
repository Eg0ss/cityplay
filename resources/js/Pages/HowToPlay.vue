<script setup>
import { onMounted, ref, markRaw } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { 
    MapPin, 
    Puzzle, 
    Trophy, 
    Moon, 
    Sun,
    ChevronLeft,
    Rocket
} from 'lucide-vue-next';

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

const steps = [
    { title: "Choisissez un lieu", desc: "Explorez la carte et sélectionnez un monument ou un endroit emblématique du Bénin.", icon: markRaw(MapPin) },
    { title: "Résolvez l'énigme", desc: "Utilisez les indices textuels et visuels pour deviner l'histoire ou le nom caché.", icon: markRaw(Puzzle) },
    { title: "Gagnez des points", desc: "Accumulez des points pour chaque bonne réponse et grimpez dans le classement mondial.", icon: markRaw(Trophy) },
];
</script>

<template>
    <Head title="Comment Jouer - Cityplay" />
    <div class="min-h-screen font-sans transition-colors duration-300 pb-20"
         :class="isDark ? 'bg-[#0A0A0B] text-white' : 'bg-gray-50 text-gray-900'">
        
        <!-- Navigation Header -->
        <nav class="max-w-7xl mx-auto px-6 py-10 flex justify-between items-center">
            <Link href="/" class="flex items-center gap-3">
                <div class="bg-[#FF9F1C] p-2 rounded-lg">
                    <span class="text-white font-black text-xl">CP</span>
                </div>
                <span class="text-2xl font-black tracking-tighter uppercase italic">Cityplay</span>
            </Link>
            <button @click="toggleTheme" class="h-12 w-12 rounded-2xl dark:bg-white/5 bg-white shadow-xl flex items-center justify-center hover:scale-110 transition-all border dark:border-white/5 border-gray-100">
                <Moon v-if="isDark" :size="20" />
                <Sun v-else :size="20" />
            </button>
        </nav>

        <div class="max-w-7xl mx-auto px-6 space-y-24">
            <!-- Hero -->
            <div class="text-center space-y-6">
                <h1 class="text-5xl lg:text-8xl font-black tracking-tighter uppercase italic leading-none">
                    LE GUIDE DE <br /><span class="text-[#FF9F1C]">L'EXPLORATEUR</span>
                </h1>
                <p class="text-xl font-medium dark:text-gray-400 text-gray-600 max-w-2xl mx-auto">
                    Devenez un maître de la découverte en suivant notre protocole d'aventure en trois phases.
                </p>
            </div>

            <!-- Steps -->
            <div class="grid gap-10 md:grid-cols-3">
                <div v-for="(step, i) in steps" :key="i" 
                    class="dark:bg-[#111113] bg-white p-12 rounded-[3rem] border dark:border-white/5 border-gray-100 shadow-sm hover:shadow-2xl transition-all group relative overflow-hidden">
                    <div class="absolute -top-4 -right-4 text-8xl opacity-5 font-black italic">0{{ i + 1 }}</div>
                    
                    <div class="h-20 w-20 dark:bg-white/5 bg-gray-50 rounded-[2rem] flex items-center justify-center mb-8 group-hover:scale-110 transition-transform duration-500 border dark:border-white/10 border-gray-200">
                        <component :is="step.icon" :size="40" class="text-[#FF9F1C]" />
                    </div>
                    <h3 class="text-3xl font-black uppercase italic tracking-tighter mb-4 dark:text-white text-gray-900">{{ step.title }}</h3>
                    <p class="text-lg dark:text-gray-500 text-gray-500 font-medium leading-relaxed">{{ step.desc }}</p>
                </div>
            </div>

            <!-- CTA Section -->
            <div class="relative dark:bg-[#1A1A1A] bg-gray-900 p-12 lg:p-24 rounded-[4rem] text-center space-y-10 overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-full opacity-10 pointer-events-none">
                    <div class="absolute -top-[20%] -left-[10%] w-[50%] h-[50%] rounded-full bg-[#FF9F1C] blur-[120px]"></div>
                </div>

                <h2 class="relative z-10 text-4xl lg:text-7xl font-black text-white uppercase italic tracking-tighter leading-none">VOTRE AVENTURE <br />COMMENCE ICI.</h2>
                <p class="relative z-10 text-xl text-gray-400 max-w-2xl mx-auto font-medium">Rejoignez la matrice et commencez à décoder l'histoire du Bénin dès aujourd'hui.</p>
                <div class="relative z-10 pt-4 flex flex-col sm:flex-row items-center justify-center gap-6">
                    <Link :href="route('register')" class="w-full sm:w-auto bg-[#FF9F1C] text-black px-16 py-6 rounded-2xl font-black uppercase tracking-[0.2em] text-xs shadow-2xl hover:scale-105 transition-all flex items-center justify-center gap-3">
                        <Rocket :size="18" />
                        Créer un Profil
                    </Link>
                    <Link href="/" class="text-white font-black uppercase tracking-widest text-xs hover:text-[#FF9F1C] transition-colors flex items-center gap-2">
                        <ChevronLeft :size="14" />
                        Retour au Hub
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800;900&display=swap');
.font-sans { font-family: 'Outfit', sans-serif; }
</style>
