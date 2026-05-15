<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
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

    <div class="min-h-screen font-sans text-[#1A1A1A] relative overflow-x-hidden bg-white">
        <!-- Navbar (GeoGuessr Style but Light) -->
        <header class="fixed top-0 z-50 w-full bg-white/80 backdrop-blur-md border-b border-gray-100">
            <div class="mx-auto flex max-w-screen-2xl items-center justify-between px-6 py-3">
                <div class="flex items-center gap-8">
                    <Link href="/" class="flex items-center gap-2">
                        <div class="bg-[#FF9F1C] p-2 rounded-lg">
                            <span class="text-white font-black text-xl">CP</span>
                        </div>
                        <span class="text-xl font-bold tracking-tighter uppercase italic">Cityplay</span>
                    </Link>
                    <nav class="hidden items-center gap-6 lg:flex">
                        <Link :href="route('how-to-play')" class="text-xs font-black uppercase tracking-widest hover:text-[#FF9F1C] transition-colors">Championnat</Link>
                        <Link :href="route('about')" class="text-xs font-black uppercase tracking-widest hover:text-[#FF9F1C] transition-colors">Organisations</Link>
                        <Link :href="route('blog')" class="text-xs font-black uppercase tracking-widest hover:text-[#FF9F1C] transition-colors">Actualités</Link>
                    </nav>
                </div>

                <div class="flex items-center gap-4">
                    <button class="hidden md:block text-xs font-black uppercase tracking-widest px-4 py-2 border-2 border-gray-100 rounded-full hover:bg-gray-50 transition-all">Rejoindre via un code</button>
                    <Link :href="route('login')" class="text-xs font-black uppercase tracking-widest hover:text-[#FF9F1C]">Connexion</Link>
                    <Link :href="route('register')" class="bg-[#4CAF50] text-white px-6 py-3 rounded-xl text-xs font-black uppercase tracking-widest shadow-lg shadow-green-100 hover:scale-105 transition-all">Jouer Maintenant</Link>
                </div>
            </div>
        </header>

        <!-- Hero Section with Map Background -->
        <section class="relative h-screen min-h-[800px] flex items-center justify-center pt-20">
            <div class="absolute inset-0 z-0">
                <img src="/images/map_benin.png" alt="Bénin Map" class="h-full w-full object-cover" />
                <div class="absolute inset-0 bg-gradient-to-b from-white/40 via-white/10 to-white"></div>
            </div>
            <div class="relative z-10 w-full max-w-5xl px-6 text-center space-y-12">
                <div class="space-y-4">
                    <div class="flex justify-center mb-4">
                        <span class="text-[#FF9F1C] font-black text-4xl tracking-tighter uppercase italic drop-shadow-sm">CITYPLAY</span>
                    </div>
                    <h1 class="text-6xl md:text-8xl font-black uppercase italic tracking-tighter text-[#1A1A1A] drop-shadow-xl">
                        EXPLOREZ LE <span class="text-[#FF9F1C]">BÉNIN !</span>
                    </h1>
                    <p class="text-xl md:text-2xl font-bold text-gray-800 max-w-3xl mx-auto leading-tight">
                        Faites-vous déposer n'importe où, des rues animées de Cotonou aux cités lacustres de Ganvié. <br />
                    </p>
                </div>

                <!-- Registration/Login Card (GeoGuessr Style) -->
                <div class="mx-auto w-full max-w-md bg-white/95 backdrop-blur-md p-8 rounded-[2rem] shadow-[0_20px_50px_rgba(0,0,0,0.1)] border border-gray-100">
                    <h3 class="text-lg font-black uppercase italic mb-8 text-[#FF9F1C]">Inscrivez-vous pour jouer</h3>
                    
                    <div class="space-y-4">
                        <button class="w-full flex items-center justify-center gap-4 bg-white border-2 border-gray-100 py-4 rounded-2xl font-bold hover:bg-gray-50 transition-all shadow-sm">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
                            Continuer avec Google
                        </button>
                        <button class="w-full flex items-center justify-center gap-4 bg-[#1A1A1A] text-white py-4 rounded-2xl font-bold hover:bg-black transition-all shadow-xl">
                            <svg class="h-5 w-5 fill-current text-white" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M17.05 20.28c-.96.95-2.04 1.43-3.23 1.43-1.16 0-2.14-.42-2.94-1.27-.8-.85-1.78-1.27-2.94-1.27-1.16 0-2.23.48-3.21 1.43C3.75 19.46 3.26 18.3 3.26 16.8c0-1.54.54-2.87 1.62-3.99 1.08-1.12 2.37-1.68 3.86-1.68 1.12 0 2.11.41 2.97 1.22.86.81 1.84 1.22 2.94 1.22 1.1 0 2.08-.41 2.94-1.22.86-.81 1.84-1.22 2.94-1.22 1.49 0 2.78.56 3.86 1.68 1.08 1.12 1.62 2.45 1.62 3.99 0 1.5-.49 2.66-1.47 3.48zM12 10.36c-.16-1.18.25-2.25 1.23-3.21.98-.96 2.08-1.44 3.31-1.44.13 1.16-.27 2.21-1.21 3.16-.94.95-2.05 1.42-3.33 1.49z"/></svg>
                            Continuer avec Apple
                        </button>
                        <button class="w-full flex items-center justify-center gap-4 bg-[#1877F2] text-white py-4 rounded-2xl font-bold hover:bg-[#166fe5] transition-all shadow-lg">
                            <svg class="h-5 w-5 fill-current text-white" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                            Continuer avec Facebook
                        </button>
                        <button class="w-full flex items-center justify-center gap-4 bg-gray-100 py-4 rounded-2xl font-bold hover:bg-gray-200 transition-all">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><path d="M22 6l-10 7L2 6"/></svg>
                            Continuer avec l'adresse mail
                        </button>
                    </div>

                    <div class="relative my-8">
                        <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-gray-100"></div></div>
                        <div class="relative flex justify-center text-xs uppercase font-bold text-gray-400"><span class="bg-white px-4">ou</span></div>
                    </div>

                    <div class="flex items-center justify-center gap-4">
                        <span class="text-sm font-medium text-gray-500">Vous avez déjà un compte ?</span>
                        <Link :href="route('login')" class="bg-[#F3F4F6] px-6 py-2 rounded-full text-xs font-black uppercase tracking-widest hover:bg-gray-200 transition-all">SE CONNECTER</Link>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section (GeoGuessr Style rows) -->
        <section class="py-32 space-y-32">
            <!-- Row 1 -->
            <div class="mx-auto max-w-7xl px-6 grid md:grid-cols-2 items-center gap-12">
                <div class="space-y-6">
                    <h2 class="text-4xl font-black uppercase italic leading-none">Explorez le monde</h2>
                    <p class="text-xl text-gray-600 font-medium">Faites-vous déposer n'importe où, des rues animées de Cotonou aux magnifiques paysages de la Pendjari.</p>
                </div>
                <div class="flex justify-center">
                    <img src="/images/image_2.png" class="w-full max-w-sm rounded-[3rem] shadow-2xl" alt="Explore" />
                </div>
            </div>

            <!-- Row 2 -->
            <div class="mx-auto max-w-7xl px-6 grid md:grid-cols-2 items-center gap-12">
                <div class="order-2 md:order-1 flex justify-center">
                    <img src="/images/image_1.png" class="w-full max-w-sm rounded-[3rem] shadow-2xl" alt="Friends" />
                </div>
                <div class="order-1 md:order-2 space-y-6">
                    <h2 class="text-4xl font-black uppercase italic leading-none">Jouez avec des amis</h2>
                    <p class="text-xl text-gray-600 font-medium">Mettez vos compétences à l'épreuve contre vos amis et votre famille. Créez votre propre fête privée et jouez ensemble.</p>
                </div>
            </div>

            <!-- Row 3 -->
            <div class="mx-auto max-w-7xl px-6 grid md:grid-cols-2 items-center gap-12">
                <div class="space-y-6">
                    <h2 class="text-4xl font-black uppercase italic leading-none">Mesurez-vous aux autres</h2>
                    <p class="text-xl text-gray-600 font-medium">Testez vos capacités contre des joueurs du Bénin entier. Gagnez des badges et affrontez d'autres joueurs lors de tournois !</p>
                </div>
                <div class="flex justify-center">
                    <img src="/images/image_4.png" class="w-full max-w-sm rounded-[3rem] shadow-2xl" alt="Compete" />
                </div>
            </div>
        </section>

        <!-- FAQ Section -->
        <section class="py-32">
            <div class="mx-auto max-w-4xl px-6">
                <h3 class="text-xs font-black uppercase tracking-widest text-gray-400 mb-12">Foire aux questions</h3>
                
                <div class="space-y-4">
                    <div v-for="(faq, index) in faqs" :key="index" class="bg-white/80 backdrop-blur-sm rounded-2xl overflow-hidden shadow-sm border border-gray-100">
                        <button @click="toggleFaq(index)" class="w-full px-8 py-6 flex justify-between items-center text-left hover:bg-gray-50 transition-all">
                            <span class="text-lg font-bold">{{ faq.question }}</span>
                            <span class="text-2xl font-light text-gray-400">{{ faq.open ? '−' : '+' }}</span>
                        </button>
                        <div v-if="faq.open" class="px-8 pb-6 text-gray-600 font-medium leading-relaxed">
                            {{ faq.answer }}
                        </div>
                    </div>
                </div>

                <div class="flex justify-center gap-6 mt-20">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/3/3c/Download_on_the_App_Store_Badge.svg" class="h-12" alt="App Store" />
                    <img src="https://upload.wikimedia.org/wikipedia/commons/7/78/Google_Play_Store_badge_EN.svg" class="h-12" alt="Play Store" />
                </div>

                <div class="mt-20 text-center text-xs font-bold text-gray-400 uppercase tracking-widest space-x-8">
                    <a href="#" class="hover:text-[#FF9F1C]">Confidentialité</a>
                    <a href="#" class="hover:text-[#FF9F1C]">Conditions générales</a>
                </div>
            </div>
        </section>
    </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800;900&display=swap');
.font-sans { font-family: 'Outfit', sans-serif; }

h1, h2, h3 {
    letter-spacing: -0.05em;
}
</style>
