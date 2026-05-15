<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});

const isMobileMenuOpen = ref(false);
const isDark = ref(false);

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
    } else {
        // Default to dark if preferred
        isDark.value = window.matchMedia('(prefers-color-scheme: dark)').matches;
    }
    updateTheme();
});

const faqs = ref([
    { question: "Qu'est-ce que Cityplay ?", answer: "Cityplay est une plateforme d'aventure par énigmes qui vous permet de découvrir les trésors cachés du Bénin tout en jouant.", open: false },
    { question: "Comment puis-je accumuler des points ?", answer: "En résolvant des énigmes sur des lieux spécifiques, vous gagnez des points qui vous permettent de grimper dans le classement.", open: false },
    { question: "Cityplay est-il disponible sur mobile ?", answer: "Oui, Cityplay est optimisé pour tous les navigateurs mobiles et une application dédiée arrive bientôt.", open: false },
    { question: "Qui crée les énigmes ?", answer: "Nos administrateurs et historiens locaux créent des énigmes basées sur des faits réels pour une immersion totale.", open: false },
]);

const toggleFaq = (index) => {
    faqs.value[index].open = !faqs.value[index].open;
};
</script>

<template>
    <Head title="Cityplay - Explorez le Bénin" />

    <div class="min-h-screen font-sans transition-colors duration-300 overflow-x-hidden"
         :class="isDark ? 'bg-[#0A0A0B] text-white' : 'bg-white text-gray-900'">
        
        <!-- Navbar -->
        <header class="fixed top-0 z-50 w-full border-b backdrop-blur-md transition-colors"
                :class="isDark ? 'bg-black/80 border-white/5' : 'bg-white/80 border-gray-100'">
            <div class="mx-auto flex max-w-screen-2xl items-center justify-between px-6 py-4 lg:py-3">
                <div class="flex items-center gap-8">
                    <Link href="/" class="flex items-center gap-2">
                        <div class="bg-[#FF9F1C] p-2 rounded-lg shadow-lg shadow-[#FF9F1C]/20">
                            <span class="text-white font-black text-xl">CP</span>
                        </div>
                        <span class="text-xl font-black tracking-tighter uppercase italic dark:text-white text-gray-900">Cityplay</span>
                    </Link>
                    <nav class="hidden lg:flex items-center gap-6">
                        <Link href="#" class="text-[10px] font-black uppercase tracking-widest hover:text-[#FF9F1C] transition-colors">Championnat</Link>
                        <Link href="#" class="text-[10px] font-black uppercase tracking-widest hover:text-[#FF9F1C] transition-colors">Organisations</Link>
                        <Link href="#" class="text-[10px] font-black uppercase tracking-widest hover:text-[#FF9F1C] transition-colors">Actualités</Link>
                    </nav>
                </div>

                <div class="flex items-center gap-4 lg:gap-6">
                    <!-- Theme Toggle -->
                    <button @click="toggleTheme" class="p-2 rounded-xl dark:bg-white/5 bg-gray-100 text-xl hover:scale-110 transition-all">
                        {{ isDark ? '🌙' : '☀️' }}
                    </button>

                    <div class="hidden lg:flex items-center gap-4">
                        <Link :href="route('login')" class="text-[10px] font-black uppercase tracking-widest hover:text-[#FF9F1C]">Connexion</Link>
                        <Link :href="route('register')" class="bg-[#4CAF50] text-white px-8 py-4 rounded-xl text-[10px] font-black uppercase tracking-widest shadow-xl shadow-green-500/20 hover:scale-105 transition-all">Jouer Maintenant</Link>
                    </div>

                    <!-- Mobile Menu Button -->
                    <button @click="isMobileMenuOpen = !isMobileMenuOpen" class="lg:hidden text-2xl">
                        {{ isMobileMenuOpen ? '✕' : '☰' }}
                    </button>
                </div>
            </div>

            <!-- Mobile Navigation -->
            <div v-if="isMobileMenuOpen" 
                 class="lg:hidden border-t px-6 py-8 space-y-6 transition-all"
                 :class="isDark ? 'bg-[#0D0D0F] border-white/5' : 'bg-white border-gray-100'">
                <nav class="flex flex-col gap-6">
                    <Link href="#" class="text-xs font-black uppercase tracking-widest">Championnat</Link>
                    <Link href="#" class="text-xs font-black uppercase tracking-widest">Organisations</Link>
                    <Link href="#" class="text-xs font-black uppercase tracking-widest">Actualités</Link>
                </nav>
                <div class="pt-6 border-t dark:border-white/5 border-gray-100 flex flex-col gap-4">
                    <Link :href="route('login')" class="text-center py-4 text-xs font-black uppercase tracking-widest">Connexion</Link>
                    <Link :href="route('register')" class="bg-[#4CAF50] text-white text-center py-4 rounded-xl text-xs font-black uppercase tracking-widest shadow-lg">Jouer Maintenant</Link>
                </div>
            </div>
        </header>

        <!-- Hero Section -->
        <section class="relative min-h-screen flex items-center justify-center pt-32 pb-20 overflow-hidden">
            <!-- Map Background -->
            <div class="absolute inset-0 z-0 scale-110 lg:scale-100">
                <img src="https://images.unsplash.com/photo-1526772662000-3f88f10405ff?q=80&w=2000&auto=format&fit=crop" alt="Background" class="h-full w-full object-cover opacity-20 dark:opacity-40 grayscale" />
                <div class="absolute inset-0 transition-colors"
                     :class="isDark ? 'bg-gradient-to-b from-[#0A0A0B]/80 via-[#0A0A0B]/40 to-[#0A0A0B]' : 'bg-gradient-to-b from-white/80 via-white/40 to-white'"></div>
            </div>

            <div class="relative z-10 w-full max-w-7xl px-6 flex flex-col lg:flex-row items-center gap-16 lg:gap-24">
                <div class="flex-1 text-center lg:text-left space-y-8 lg:space-y-12">
                    <div class="space-y-4">
                        <span class="inline-block text-[#FF9F1C] font-black text-xs lg:text-sm tracking-[0.4em] uppercase italic">Adventure Platform</span>
                        <h1 class="text-5xl md:text-7xl lg:text-9xl font-black uppercase italic tracking-tighter leading-none">
                            EXPLOREZ <br /> LE <span class="text-[#FF9F1C]">BÉNIN !</span>
                        </h1>
                        <p class="text-lg lg:text-xl font-medium dark:text-gray-400 text-gray-600 max-w-xl mx-auto lg:mx-0 leading-relaxed">
                            Vivez une aventure unique à travers les rues de Cotonou, les palais d'Abomey et les cités lacustres. Relevez des défis réels.
                        </p>
                    </div>
                    <div class="flex flex-col sm:flex-row justify-center lg:justify-start gap-4">
                        <Link :href="route('register')" class="bg-gray-900 dark:bg-white text-white dark:text-black px-10 py-5 rounded-2xl font-black uppercase tracking-widest text-xs hover:scale-105 transition-all shadow-2xl">
                            Démarrer l'Aventure
                        </Link>
                        <button class="dark:bg-white/5 bg-gray-100 px-10 py-5 rounded-2xl font-black uppercase tracking-widest text-xs hover:bg-[#FF9F1C] hover:text-black transition-all">
                            Voir le Classement
                        </button>
                    </div>
                </div>

                <!-- Floating Card -->
                <div class="w-full max-w-md dark:bg-[#111113]/80 bg-white/80 backdrop-blur-xl p-8 lg:p-10 rounded-[2.5rem] shadow-2xl border dark:border-white/5 border-gray-100">
                    <h3 class="text-xl font-black uppercase italic mb-8 text-[#FF9F1C] text-center tracking-tighter">Accès Terminal</h3>
                    <div class="space-y-4">
                        <button class="w-full flex items-center justify-center gap-4 dark:bg-white/5 bg-gray-50 border dark:border-white/5 border-gray-200 py-5 rounded-2xl font-black uppercase text-[10px] tracking-widest hover:scale-[1.02] transition-all">
                            Continuer avec Google
                        </button>
                        <button class="w-full flex items-center justify-center gap-4 dark:bg-white bg-gray-900 dark:text-black text-white py-5 rounded-2xl font-black uppercase text-[10px] tracking-widest hover:scale-[1.02] transition-all shadow-xl">
                            Continuer avec l'Email
                        </button>
                    </div>
                    <div class="relative my-10 flex items-center">
                        <div class="flex-grow border-t dark:border-white/5 border-gray-100"></div>
                        <span class="mx-4 text-[10px] font-black text-gray-500 uppercase tracking-widest">Ou rejoindre</span>
                        <div class="flex-grow border-t dark:border-white/5 border-gray-100"></div>
                    </div>
                    <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                        <Link :href="route('login')" class="text-xs font-black uppercase tracking-widest hover:text-[#FF9F1C] transition-colors">Connexion</Link>
                        <div class="hidden sm:block h-1 w-1 bg-gray-500 rounded-full"></div>
                        <Link :href="route('register')" class="text-xs font-black uppercase tracking-widest hover:text-[#FF9F1C] transition-colors">Créer un compte</Link>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features -->
        <section class="py-24 lg:py-32 space-y-32">
            <div v-for="(feature, i) in [
                { title: 'Explorez le pays', text: 'Découvrez des lieux chargés d\'histoire, des palais royaux aux marchés vibrants.', emoji: '🏛️' },
                { title: 'Jouez entre amis', text: 'Créez des guildes, lancez des défis et grimpez ensemble vers le sommet du classement.', emoji: '🤝' },
                { title: 'Devenez une légende', text: 'Résolvez les énigmes les plus complexes pour gagner des badges épiques et des récompenses.', emoji: '👑' }
            ]" :key="i" 
            class="mx-auto max-w-7xl px-6 grid grid-cols-1 lg:grid-cols-2 items-center gap-12 lg:gap-24">
                <div :class="i % 2 !== 0 ? 'lg:order-2' : ''" class="space-y-6">
                    <div class="text-5xl mb-4">{{ feature.emoji }}</div>
                    <h2 class="text-4xl lg:text-6xl font-black uppercase italic leading-none tracking-tighter">{{ feature.title }}</h2>
                    <p class="text-lg lg:text-xl dark:text-gray-400 text-gray-600 font-medium leading-relaxed">{{ feature.text }}</p>
                    <button class="text-[#FF9F1C] font-black uppercase tracking-widest text-xs border-b-2 border-[#FF9F1C] pb-2 hover:translate-x-2 transition-transform inline-block">En savoir plus →</button>
                </div>
                <div :class="i % 2 !== 0 ? 'lg:order-1' : ''" class="flex justify-center group">
                    <div class="w-full aspect-video lg:aspect-square max-w-lg dark:bg-white/5 bg-gray-100 rounded-[3rem] overflow-hidden shadow-2xl group-hover:scale-105 transition-transform duration-700">
                        <img :src="`https://images.unsplash.com/photo-${1500000000000 + i}?q=80&w=800`" class="h-full w-full object-cover mix-blend-overlay opacity-50 grayscale group-hover:grayscale-0 transition-all duration-700" alt="Feature" />
                    </div>
                </div>
            </div>
        </section>

        <!-- FAQ -->
        <section class="py-24 lg:py-32 dark:bg-white/5 bg-gray-50 border-y dark:border-white/5 border-gray-100">
            <div class="mx-auto max-w-4xl px-6">
                <div class="text-center mb-16 lg:mb-24">
                    <h3 class="text-[10px] font-black uppercase tracking-[0.5em] text-[#FF9F1C] mb-4 italic">Support Center</h3>
                    <h2 class="text-4xl lg:text-5xl font-black uppercase italic tracking-tighter">Foire aux <span class="text-[#FF9F1C]">Questions</span></h2>
                </div>
                
                <div class="space-y-4">
                    <div v-for="(faq, index) in faqs" :key="index" 
                         class="dark:bg-[#0A0A0B] bg-white rounded-3xl overflow-hidden border dark:border-white/5 border-gray-100 shadow-sm">
                        <button @click="toggleFaq(index)" class="w-full px-8 py-7 flex justify-between items-center text-left hover:bg-gray-50 dark:hover:bg-white/5 transition-all group">
                            <span class="text-base lg:text-lg font-black uppercase tracking-tight group-hover:text-[#FF9F1C] transition-colors">{{ faq.question }}</span>
                            <span class="text-2xl font-light text-gray-400 group-hover:rotate-90 transition-transform duration-300">{{ faq.open ? '−' : '+' }}</span>
                        </button>
                        <transition name="fade">
                            <div v-if="faq.open" class="px-8 pb-8 dark:text-gray-400 text-gray-600 font-medium leading-relaxed text-sm lg:text-base">
                                {{ faq.answer }}
                            </div>
                        </transition>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="py-20 lg:py-32 px-6">
            <div class="mx-auto max-w-7xl flex flex-col lg:flex-row justify-between items-center gap-12">
                <div class="text-center lg:text-left">
                    <div class="flex items-center justify-center lg:justify-start gap-2 mb-6">
                        <div class="bg-[#FF9F1C] p-2 rounded-lg">
                            <span class="text-white font-black text-xl">CP</span>
                        </div>
                        <span class="text-xl font-black tracking-tighter uppercase italic">Cityplay</span>
                    </div>
                    <p class="text-gray-500 font-bold uppercase text-[10px] tracking-widest">© 2026 Cityplay Adventure platform • Bénin</p>
                </div>

                <div class="flex flex-col items-center gap-8">
                    <div class="flex gap-4">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/3/3c/Download_on_the_App_Store_Badge.svg" class="h-10 hover:scale-105 transition-transform cursor-pointer" alt="App Store" />
                        <img src="https://upload.wikimedia.org/wikipedia/commons/7/78/Google_Play_Store_badge_EN.svg" class="h-10 hover:scale-105 transition-transform cursor-pointer" alt="Play Store" />
                    </div>
                    <div class="flex gap-8 text-[10px] font-black uppercase tracking-widest text-gray-500">
                        <a href="#" class="hover:text-[#FF9F1C] transition-colors">Confidentialité</a>
                        <a href="#" class="hover:text-[#FF9F1C] transition-colors">Conditions</a>
                        <a href="#" class="hover:text-[#FF9F1C] transition-colors">Aide</a>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800;900&display=swap');

.font-sans { font-family: 'Outfit', sans-serif; }

.fade-enter-active, .fade-leave-active {
    transition: opacity 0.3s ease;
}
.fade-enter-from, .fade-leave-to {
    opacity: 0;
}
</style>
