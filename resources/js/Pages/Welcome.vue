<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

const gsap = window.gsap;

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});

const isMobileMenuOpen = ref(false);

const faqs = ref([
    { question: "Qu'est-ce que Cityplay ?", answer: "Cityplay est une plateforme d'aventure par énigmes géolocalisées qui vous permet de découvrir les trésors cachés du Bénin tout en jouant.", open: false },
    { question: "Comment puis-je accumuler des points ?", answer: "En résolvant des énigmes sur des lieux réels de votre ville ou de chez vous, vous gagnez des points d'XP pour grimper dans le classement.", open: false },
    { question: "Cityplay est-il disponible sur mobile ?", answer: "Oui, Cityplay est optimisé pour tous les navigateurs mobiles modernes avec support GPS en temps réel.", open: false },
    { question: "Qui crée les énigmes ?", answer: "Nos historiens et administrateurs créent des énigmes enrichissantes basées sur le patrimoine culturel local.", open: false },
]);

const toggleFaq = (index) => {
    faqs.value[index].open = !faqs.value[index].open;
};

onMounted(() => {
    // 🎥 Slow, continuous drift & zoom on background video container
    gsap.fromTo('.bg-video-container', 
        { scale: 1.0, rotation: 0 },
        { 
            scale: 1.15, 
            rotation: 1.5, 
            duration: 40, 
            repeat: -1, 
            yoyo: true, 
            ease: 'sine.inOut' 
        }
    );

    // ✨ Stagger reveal animation for the Hero items and Access panel card
    gsap.from('.hero-reveal', {
        y: 50,
        opacity: 0,
        duration: 1.2,
        stagger: 0.15,
        ease: 'power3.out'
    });

    // 🚀 Special float / slide-in from right for the Floating Access card
    gsap.from('.card-reveal', {
        x: 60,
        opacity: 0,
        duration: 1.5,
        delay: 0.4,
        ease: 'back.out(1.2)'
    });
});
</script>

<template>
    <Head title="Cityplay - Explorez le Bénin" />

    <div class="min-h-screen font-sans bg-[#171235] text-white overflow-x-hidden animate-slide-up">
        
        <!-- Navbar (Sleek GeoGuessr Dark #10101c) -->
        <header class="fixed top-0 z-50 w-full border-b bg-[#10101c]/95 border-[#2a245c] backdrop-blur-md">
            <div class="mx-auto flex max-w-screen-2xl items-center justify-between px-6 py-4 lg:py-3">
                <div class="flex items-center gap-8">
                    <Link href="/" class="flex items-center gap-2 group">
                        <div class="bg-[#87d74e] p-2.5 rounded-xl shadow-[0_0_15px_rgba(135,215,78,0.4)] transition-all group-hover:scale-110 duration-200">
                            <span class="text-[#10101c] font-black text-xl">CP</span>
                        </div>
                        <span class="text-xl font-black tracking-tighter uppercase italic text-white text-glow-green">Cityplay</span>
                    </Link>
                    <nav class="hidden lg:flex items-center gap-6">
                        <Link :href="route('how-to-play')" class="text-[10px] font-black uppercase tracking-widest text-gray-400 hover:text-[#87d74e] transition-colors">Comment jouer</Link>
                        <Link href="#" class="text-[10px] font-black uppercase tracking-widest text-gray-400 hover:text-[#87d74e] transition-colors">Rejoindre via un code</Link>
                        <Link :href="route('explore')" class="text-[10px] font-black uppercase tracking-widest text-gray-400 hover:text-[#87d74e] transition-colors">Explorer des villes</Link>
                    </nav>
                </div>

                <div class="flex items-center gap-4 lg:gap-6">
                    <div class="hidden lg:flex items-center gap-4">
                        <Link :href="route('login')" class="text-[10px] font-black uppercase tracking-widest text-gray-400 hover:text-[#87d74e] transition-colors">Connexion</Link>
                        <Link :href="route('register')" class="btn-3d btn-3d-green px-6 py-3 text-[10px] font-black uppercase tracking-widest shadow-[0_4px_0_#5d9933]">Jouer Maintenant</Link>
                    </div>

                    <!-- Mobile Menu Button -->
                    <button @click="isMobileMenuOpen = !isMobileMenuOpen" class="lg:hidden p-2 rounded-xl bg-[#1c183a] border border-[#2a245c] text-white">
                        <svg v-if="isMobileMenuOpen" xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="18" y2="18"/></svg>
                    </button>
                </div>
            </div>

            <!-- Mobile Navigation -->
            <div v-if="isMobileMenuOpen" class="lg:hidden border-t bg-[#10101c] border-[#2a245c] px-6 py-8 space-y-6">
                <nav class="flex flex-col gap-6">
                    <Link :href="route('how-to-play')" class="text-xs font-black uppercase tracking-widest text-white hover:text-[#87d74e]">Comment jouer</Link>
                    <Link href="#" class="text-xs font-black uppercase tracking-widest text-white hover:text-[#87d74e]">Rejoindre via un code</Link>
                    <Link :href="route('explore')" class="text-xs font-black uppercase tracking-widest text-white hover:text-[#87d74e]">Explorer des villes</Link>
                </nav>
                <div class="pt-6 border-t border-[#2a245c] flex flex-col gap-4">
                    <Link :href="route('login')" class="text-center py-4 text-xs font-black uppercase tracking-widest text-white hover:text-[#87d74e]">Connexion</Link>
                    <Link :href="route('register')" class="btn-3d btn-3d-green text-center py-4 rounded-xl text-xs font-black uppercase tracking-widest shadow-[0_4px_0_#5d9933]">Jouer Maintenant</Link>
                </div>
            </div>
        </header>

        <!-- Hero Section -->
        <section class="relative min-h-screen flex items-center justify-center pt-32 pb-20 overflow-hidden">
            <!-- Immersive GSAP Animated Video Background -->
            <div class="bg-video-container absolute inset-0 z-0 scale-110 lg:scale-100 opacity-25">
                <video 
                    autoplay 
                    loop 
                    muted 
                    playsinline 
                    class="h-full w-full object-cover filter brightness-[0.5] contrast-[1.2]"
                >
                    <source src="https://assets.mixkit.co/videos/preview/mixkit-digital-animation-of-a-glowing-world-map-42861-large.mp4" type="video/mp4" />
                </video>
                <div class="absolute inset-0 bg-[#171235]/40 mix-blend-multiply"></div>
            </div>

            <!-- Floating Bobbing Emojis around the page (Drifting gamified feel) -->
            <div class="absolute top-1/4 left-10 text-5xl animate-float opacity-30 select-none pointer-events-none hidden md:block">🧭</div>
            <div class="absolute bottom-1/4 left-1/5 text-5xl animate-float opacity-30 select-none pointer-events-none hidden md:block" style="animation-delay: 1.5s;">🗺️</div>
            <div class="absolute top-1/3 right-12 text-6xl animate-float opacity-30 select-none pointer-events-none hidden md:block" style="animation-delay: 0.8s;">📍</div>
            <div class="absolute bottom-1/3 right-1/4 text-5xl animate-float opacity-30 select-none pointer-events-none hidden md:block" style="animation-delay: 2.2s;">🏆</div>

            <div class="relative z-10 w-full max-w-7xl px-6 flex flex-col lg:flex-row items-center gap-16 lg:gap-24">
                <div class="flex-1 text-center lg:text-left space-y-8 lg:space-y-12 hero-reveal">
                    <div class="space-y-4">
                        <span class="inline-block text-[#87d74e] text-glow-green font-black text-xs lg:text-sm tracking-[0.4em] uppercase italic">Plateforme de Jeu & Énigmes</span>
                        <h1 class="text-4xl sm:text-6xl md:text-7xl lg:text-9xl font-black uppercase italic tracking-tighter leading-none">
                            EXPLOREZ <br /> LE <span class="text-[#87d74e] text-glow-green">BÉNIN !</span>
                        </h1>
                        <p class="text-lg lg:text-xl font-medium text-gray-400 max-w-xl mx-auto lg:mx-0 leading-relaxed">
                            Vivez une aventure géolocalisée unique à travers les rues de Cotonou, les palais d'Abomey et les cités lacustres de Ganvié.
                        </p>
                    </div>
                    <div class="flex flex-col sm:flex-row justify-center lg:justify-start gap-4">
                        <Link :href="route('register')" class="btn-3d btn-3d-green px-10 py-5 rounded-2xl font-black uppercase tracking-widest text-xs shadow-[0_5px_0_#5d9933]">
                            Démarrer l'Aventure 🚀
                        </Link>
                        <button class="px-10 py-5 rounded-2xl font-black uppercase tracking-widest text-xs bg-[#1c183a] border border-[#2a245c] text-white hover:text-[#87d74e] transition-colors">
                            Voir le Classement 🏆
                        </button>
                    </div>
                </div>

                <!-- FLOATING CARD (Access Panel Modal - Strictly Agenced Color Scheme) -->
                <!-- Uses #10101c base, #7751de purple outline & glow, #ffc628 gold headers, #4769b0 & #7751de buttons, #171235 shadows -->
                <div class="w-full max-w-md bg-[#10101c] p-6 sm:p-8 lg:p-10 rounded-[2.5rem] border-2 border-[#7751de] shadow-[0_0_35px_rgba(119,81,222,0.4)] relative hover-lift card-reveal">
                    <div class="absolute -top-10 -left-10 w-32 h-32 bg-[#7751de]/10 rounded-full blur-3xl pointer-events-none"></div>
                    
                    <h3 class="text-xl font-black uppercase italic mb-8 text-[#ffc628] text-glow-yellow text-center tracking-tighter">
                        🎯 Accès à l'aventure
                    </h3>
                    
                    <div class="space-y-4">
                        <!-- Google Link agenced in 3D Blue (#4769b0) -->
                        <button class="w-full btn-3d btn-3d-blue py-4.5 rounded-2xl font-black uppercase text-[10px] tracking-widest shadow-[0_4px_0_#2b3f6b] flex items-center justify-center gap-2">
                            🔵 Continuer via Session Bleue
                        </button>
                        
                        <!-- Direct Link agenced in 3D Purple (#7751de) -->
                        <button class="w-full btn-3d btn-3d-purple py-4.5 rounded-2xl font-black uppercase text-[10px] tracking-widest shadow-[0_4px_0_#4d2f94] flex items-center justify-center gap-2">
                            🟣 Continuer par Session Directe
                        </button>
                    </div>
                    
                    <!-- Divider (Inner shadows with background #171235) -->
                    <div class="relative my-8 flex items-center">
                        <div class="flex-grow border-t border-[#2a245c]"></div>
                        <span class="mx-4 text-[9px] font-black text-gray-500 uppercase tracking-widest">Ou rejoindre</span>
                        <div class="flex-grow border-t border-[#2a245c]"></div>
                    </div>
                    
                    <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                        <Link :href="route('login')" class="text-xs font-black uppercase tracking-widest text-gray-400 hover:text-[#ffc628] transition-colors">Connexion</Link>
                        <div class="hidden sm:block h-1.5 w-1.5 bg-[#2a245c] rounded-full"></div>
                        <Link :href="route('register')" class="text-xs font-black uppercase tracking-widest text-gray-400 hover:text-[#87d74e] transition-colors">Créer un compte</Link>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features (GeoGuessr Grid Cards style) -->
        <section class="py-24 lg:py-32 space-y-32 bg-[#171235]">
            <div v-for="(feature, i) in [
                { title: 'Explorez le pays', text: 'Découvrez des lieux chargés d\'histoire, des palais d\'Abomey aux marchés vibrants de Dantokpa.', img: '/images/image_1.png' },
                { title: 'Jouez entre amis', text: 'Créez des lobbys compétitifs rapides en temps réel ou coopérez pour résoudre la quête.', img: '/images/image_2.png' },
                { title: 'Devenez une légende', text: 'Résolvez les énigmes les plus complexes pour gagner des badges de réussite d\'XP.', img: '/images/image_3.png' }
            ]" :key="i" 
            class="mx-auto max-w-7xl px-6 grid grid-cols-1 lg:grid-cols-2 items-center gap-12 lg:gap-24">
                <div :class="i % 2 !== 0 ? 'lg:order-2' : ''" class="space-y-6 animate-slide-up">
                    <h2 class="text-4xl lg:text-6xl font-black uppercase italic leading-none tracking-tighter">{{ feature.title }}</h2>
                    <p class="text-lg lg:text-xl text-gray-400 font-medium leading-relaxed">{{ feature.text }}</p>
                    <button class="text-[#87d74e] text-glow-green font-black uppercase tracking-widest text-xs border-b-2 border-[#87d74e] pb-2 hover:translate-x-2 transition-transform inline-block">En savoir plus →</button>
                </div>
                <div :class="i % 2 !== 0 ? 'lg:order-1' : ''" class="flex justify-center group">
                    <div class="w-full aspect-video lg:aspect-square max-w-lg bg-[#1c183a] border border-[#2a245c] rounded-[3rem] overflow-hidden shadow-2xl group-hover:scale-102 transition-all duration-500 relative">
                        <img :src="feature.img" class="h-full w-full object-cover transition-all duration-700 opacity-60 group-hover:opacity-80" :alt="feature.title" />
                        <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent"></div>
                    </div>
                </div>
            </div>
        </section>

        <!-- FAQ -->
        <section class="py-24 lg:py-32 bg-[#10101c]/50 border-y border-[#2a245c]">
            <div class="mx-auto max-w-4xl px-6">
                <div class="text-center mb-16 lg:mb-24">
                    <h3 class="text-[10px] font-black uppercase tracking-[0.5em] text-[#87d74e] text-glow-green mb-4 italic">Centre d'aide</h3>
                    <h2 class="text-4xl lg:text-5xl font-black uppercase italic tracking-tighter">Foire aux <span class="text-[#87d74e]">Questions</span></h2>
                </div>
                
                <div class="space-y-4">
                    <div v-for="(faq, index) in faqs" :key="index" 
                         class="bg-[#1c183a] border border-[#2a245c] rounded-3xl overflow-hidden shadow-sm hover-lift">
                        <button @click="toggleFaq(index)" class="w-full px-5 py-5 sm:px-8 sm:py-7 flex justify-between items-center text-left hover:bg-[#10101c] transition-all group">
                            <span class="text-base lg:text-lg font-black uppercase tracking-tight group-hover:text-[#87d74e] transition-colors">{{ faq.question }}</span>
                            <span class="text-gray-400 group-hover:text-[#87d74e] transition-all duration-300" :class="faq.open ? 'rotate-180' : ''">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                            </span>
                        </button>
                        <transition name="fade">
                            <div v-if="faq.open" class="px-5 pb-5 sm:px-8 sm:pb-8 text-gray-400 font-medium leading-relaxed text-sm lg:text-base">
                                {{ faq.answer }}
                            </div>
                        </transition>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="py-20 border-t border-[#2a245c] bg-[#10101c] px-6">
            <div class="mx-auto max-w-7xl flex flex-col lg:flex-row justify-between items-center gap-12">
                <div class="text-center lg:text-left">
                    <div class="flex items-center justify-center lg:justify-start gap-2 mb-6">
                        <div class="bg-[#87d74e] p-2.5 rounded-xl">
                            <span class="text-[#10101c] font-black text-xl">CP</span>
                        </div>
                        <span class="text-xl font-black tracking-tighter uppercase italic text-glow-green">Cityplay</span>
                    </div>
                    <p class="text-gray-500 font-bold uppercase text-[10px] tracking-widest">© 2026 Cityplay Adventure platform • Bénin</p>
                </div>

                <div class="flex flex-col items-center gap-8">
                    <div class="flex gap-8 text-[10px] font-black uppercase tracking-widest text-gray-500">
                        <a href="#" class="hover:text-[#87d74e] transition-colors">Confidentialité</a>
                        <a href="#" class="hover:text-[#87d74e] transition-colors">Conditions</a>
                        <a href="#" class="hover:text-[#87d74e] transition-colors">Aide</a>
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
