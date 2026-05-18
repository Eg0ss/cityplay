<script setup>
import { onMounted, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';

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

const features = [
    { title: "Engagement Historique", desc: "Chaque énigme est vérifiée par des historiens locaux pour garantir l'authenticité.", icon: "📚" },
    { title: "Immersion Totale", desc: "Une interface conçue pour vous plonger au cœur de l'aventure béninoise.", icon: "🎭" },
    { title: "Communauté Active", desc: "Rejoignez des milliers d'explorateurs et partagez vos découvertes.", icon: "🤝" },
];
</script>

<template>
    <Head title="À Propos - Cityplay" />
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
            <!-- Hero Section -->
            <div class="grid gap-20 lg:grid-cols-2 items-center">
                <div class="space-y-10 order-2 lg:order-1">
                    <div class="space-y-4">
                        <span class="text-[10px] font-black uppercase tracking-[0.4em] text-[#FF9F1C]">Notre ADN</span>
                        <h1 class="text-5xl lg:text-8xl font-black tracking-tighter uppercase italic leading-none">
                            VALORISER LE <br /><span class="text-[#FF9F1C]">PATRIMOINE</span>
                        </h1>
                    </div>
                    
                    <p class="text-xl dark:text-gray-400 text-gray-600 font-medium leading-relaxed">
                        Cityplay est né de la volonté de métamorphoser la découverte du patrimoine culturel du Bénin en une épopée ludique. Nous fusionnons histoire et technologie pour créer des souvenirs inoubliables.
                    </p>

                    <div class="space-y-6">
                        <div v-for="(feat, i) in features" :key="i" 
                            class="flex items-start gap-6 p-6 dark:bg-white/5 bg-white rounded-3xl border dark:border-white/5 border-gray-100 shadow-sm hover:translate-x-2 transition-transform">
                            <div class="text-3xl">{{ feat.icon }}</div>
                            <div>
                                <h4 class="font-black uppercase italic tracking-tighter text-lg dark:text-white text-gray-900">{{ feat.title }}</h4>
                                <p class="dark:text-gray-500 text-gray-500 text-sm font-medium">{{ feat.desc }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="relative order-1 lg:order-2">
                    <img src="https://images.unsplash.com/photo-1578301978693-85fa9c0320b9?q=80&w=1200" class="rounded-[4rem] shadow-2xl grayscale-[20%] hover:grayscale-0 transition-all duration-700 aspect-[4/5] object-cover" alt="About Image" />
                    <div class="absolute -bottom-10 -right-4 lg:-right-10 bg-[#FF9F1C] p-10 lg:p-14 rounded-[3rem] shadow-2xl text-black hidden sm:block rotate-3 animate-pulse">
                        <div class="text-6xl font-black italic tracking-tighter leading-none">100%</div>
                        <div class="text-[10px] font-black uppercase tracking-widest mt-2">Origine Bénin</div>
                    </div>
                </div>
            </div>

            <!-- Philosophy Section -->
            <div class="dark:bg-[#111113] bg-white p-12 lg:p-24 rounded-[4rem] text-center space-y-12 border dark:border-white/5 border-gray-100">
                <h2 class="text-4xl lg:text-6xl font-black uppercase italic tracking-tighter dark:text-white text-gray-900 leading-none">UNE PHILOSOPHIE <br /><span class="text-[#FF9F1C]">EXPÉRIENTIELLE</span></h2>
                <div class="grid md:grid-cols-3 gap-12 text-left">
                    <div class="space-y-4">
                        <span class="text-3xl">🧩</span>
                        <h4 class="text-xl font-black uppercase italic dark:text-white text-gray-900">Éducation Ludique</h4>
                        <p class="dark:text-gray-500 text-gray-500 font-medium">Nous croyons que l'on retient mieux ce que l'on découvre en s'amusant.</p>
                    </div>
                    <div class="space-y-4">
                        <span class="text-3xl">🌍</span>
                        <h4 class="text-xl font-black uppercase italic dark:text-white text-gray-900">Rayonnement</h4>
                        <p class="dark:text-gray-500 text-gray-500 font-medium">Porter l'histoire du Bénin au-delà des frontières grâce au numérique.</p>
                    </div>
                    <div class="space-y-4">
                        <span class="text-3xl">⚡</span>
                        <h4 class="text-xl font-black uppercase italic dark:text-white text-gray-900">Innovation</h4>
                        <p class="dark:text-gray-500 text-gray-500 font-medium">Utiliser le meilleur de la tech pour magnifier le passé.</p>
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