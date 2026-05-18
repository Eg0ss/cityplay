<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { onMounted } from 'vue';

const gsap = window.gsap;

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};

onMounted(() => {
    // 🎥 Slow, continuous drift & zoom on background video container
    gsap.fromTo('.bg-video-container', 
        { scale: 1.0, rotation: 0 },
        { 
            scale: 1.15, 
            rotation: 1.5, 
            duration: 35, 
            repeat: -1, 
            yoyo: true, 
            ease: 'sine.inOut' 
        }
    );

    // ⚡ Left Side panel elements stagger reveal
    gsap.from('.left-panel-reveal', {
        x: -40,
        opacity: 0,
        duration: 1.2,
        stagger: 0.15,
        ease: 'power3.out'
    });

    // 🏆 Right Side registration card rebound entry
    gsap.from('.form-card-reveal', {
        y: 50,
        opacity: 0,
        duration: 1.4,
        ease: 'back.out(1.2)'
    });
});
</script>

<template>
    <Head title="Inscription - Cityplay" />

    <div class="min-h-screen font-sans flex flex-col lg:flex-row bg-[#171235] text-white overflow-hidden relative">
        
        <!-- Ambient Background glow spots (GeoGuessr arcade neon feel) -->
        <div class="absolute top-0 left-1/4 w-96 h-96 bg-[#87d74e]/5 rounded-full blur-[150px] pointer-events-none -z-10"></div>
        <div class="absolute bottom-0 right-1/4 w-96 h-96 bg-[#7751de]/10 rounded-full blur-[150px] pointer-events-none -z-10"></div>

        <!-- Global background video for mobile / under-form feel -->
        <div class="bg-video-container absolute inset-0 w-full h-full overflow-hidden pointer-events-none -z-20">
            <video 
                autoplay 
                loop 
                muted 
                playsinline 
                class="w-full h-full object-cover opacity-15 filter brightness-[0.4] contrast-[1.2]"
            >
                <source src="https://assets.mixkit.co/videos/preview/mixkit-digital-animation-of-a-glowing-world-map-42861-large.mp4" type="video/mp4" />
            </video>
            <div class="absolute inset-0 bg-gradient-to-b from-[#10101c]/30 to-[#171235]"></div>
        </div>

        <!-- Left Side: Branding/Map Image (Desktop Only) -->
        <div class="hidden lg:flex lg:w-1/2 bg-[#10101c] relative overflow-hidden p-12 lg:p-20 flex-col justify-between border-r border-[#2a245c]">
            <!-- World Map Vignette background -->
            <div class="absolute inset-0 z-0">
                <video 
                    autoplay 
                    loop 
                    muted 
                    playsinline 
                    class="h-full w-full object-cover opacity-35 filter brightness-[0.6] contrast-[1.1]"
                >
                    <source src="https://assets.mixkit.co/videos/preview/mixkit-digital-animation-of-a-glowing-world-map-42861-large.mp4" type="video/mp4" />
                </video>
                <div class="absolute inset-0 bg-gradient-to-b from-[#10101c]/30 to-[#171235]"></div>
            </div>

            <!-- Floating Bobbing Emojis on Left Side -->
            <div class="absolute top-1/4 left-1/4 text-6xl animate-float opacity-30 select-none pointer-events-none">🎮</div>
            <div class="absolute bottom-1/4 right-1/4 text-6xl animate-float opacity-30 select-none pointer-events-none" style="animation-delay: 1.5s;">🏆</div>
            
            <Link href="/" class="relative z-10 flex items-center gap-3 group left-panel-reveal">
                <div class="bg-[#87d74e] p-2.5 rounded-xl shadow-[0_0_15px_rgba(135,215,78,0.4)] transition-all group-hover:scale-110 duration-200">
                    <span class="text-[#10101c] font-black text-xl">CP</span>
                </div>
                <span class="text-3xl font-black tracking-tighter uppercase italic text-white text-glow-green">Cityplay</span>
            </Link>

            <div class="relative z-10 space-y-6 left-panel-reveal">
                <span class="inline-block text-[#87d74e] text-glow-green font-black text-xs tracking-[0.4em] uppercase italic">Création Profil</span>
                <h1 class="text-6xl lg:text-7xl font-black text-white leading-none tracking-tighter italic uppercase">
                    REJOIGNEZ <br />L'ÉLITE !
                </h1>
                <p class="text-lg text-gray-400 max-w-md font-medium leading-relaxed">
                    Créez votre compte en quelques secondes, affrontez d'autres joueurs en temps réel et devenez le roi de la boussole.
                </p>
            </div>

            <div class="relative z-10 text-gray-500 text-[10px] font-black uppercase tracking-[0.4em] left-panel-reveal">
                🛡️ SECURE PROTOCOL: ACTIVE
            </div>
        </div>

        <!-- Right Side: Register Form -->
        <div class="flex-1 flex items-center justify-center p-6 sm:p-12 lg:p-20 relative z-10 overflow-y-auto">
            <!-- Back Button -->
            <Link href="/" class="absolute top-8 right-8 flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-gray-400 hover:text-[#87d74e] transition-all">
                <span>←</span>
                <span>Retour à l'accueil</span>
            </Link>

            <!-- Gaming Panel Card -->
            <div class="w-full max-w-md space-y-8 panel-glass p-8 sm:p-10 rounded-[2.5rem] border border-[#2a245c] shadow-2xl relative my-12 hover-lift form-card-reveal">
                <div class="absolute -top-10 -left-10 w-32 h-32 bg-[#87d74e]/5 rounded-full blur-3xl pointer-events-none"></div>

                <div class="lg:hidden flex justify-center mb-6">
                    <Link href="/" class="flex items-center gap-2">
                        <div class="bg-[#87d74e] p-2.5 rounded-xl shadow-[0_0_15px_rgba(135,215,78,0.4)]">
                            <span class="text-[#10101c] font-black text-xl">CP</span>
                        </div>
                        <span class="text-2xl font-black tracking-tighter uppercase italic text-glow-green text-white">Cityplay</span>
                    </Link>
                </div>

                <div class="space-y-3">
                    <h2 class="text-3xl lg:text-4xl font-black uppercase italic tracking-tighter">
                        Nouveau <span class="text-[#87d74e]">Joueur</span>
                    </h2>
                    <p class="text-gray-400 text-sm font-medium">Configurez votre identité d'explorateur.</p>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black uppercase tracking-widest text-gray-400">Nom de Code (Pseudo)</label>
                        <input 
                            v-model="form.name"
                            type="text" 
                            required
                            autofocus
                            class="w-full bg-[#10101c] border border-[#2a245c] rounded-2xl py-4.5 px-6 focus:ring-2 focus:ring-[#87d74e] focus:border-[#87d74e] transition-all font-bold text-white placeholder-gray-500"
                            placeholder="Jean Dupont"
                        />
                        <div v-if="form.errors.name" class="text-red-500 text-[10px] font-black uppercase mt-2 tracking-widest">{{ form.errors.name }}</div>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-[10px] font-black uppercase tracking-widest text-gray-400">Canal de Liaison (Email)</label>
                        <input 
                            v-model="form.email"
                            type="email" 
                            required
                            class="w-full bg-[#10101c] border border-[#2a245c] rounded-2xl py-4.5 px-6 focus:ring-2 focus:ring-[#87d74e] focus:border-[#87d74e] transition-all font-bold text-white placeholder-gray-500"
                            placeholder="votre@email.com"
                        />
                        <div v-if="form.errors.email" class="text-red-500 text-[10px] font-black uppercase mt-2 tracking-widest">{{ form.errors.email }}</div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black uppercase tracking-widest text-gray-400">Mot de passe</label>
                            <input 
                                v-model="form.password"
                                type="password" 
                                required
                                class="w-full bg-[#10101c] border border-[#2a245c] rounded-2xl py-4 px-5 focus:ring-2 focus:ring-[#87d74e] focus:border-[#87d74e] transition-all font-bold text-white placeholder-gray-500"
                                placeholder="••••••••"
                            />
                            <div v-if="form.errors.password" class="text-red-500 text-[10px] font-black uppercase mt-2 tracking-widest">{{ form.errors.password }}</div>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-[10px] font-black uppercase tracking-widest text-gray-400">Confirmation</label>
                            <input 
                                v-model="form.password_confirmation"
                                type="password" 
                                required
                                class="w-full bg-[#10101c] border border-[#2a245c] rounded-2xl py-4 px-5 focus:ring-2 focus:ring-[#87d74e] focus:border-[#87d74e] transition-all font-bold text-white placeholder-gray-500"
                                placeholder="••••••••"
                            />
                            <div v-if="form.errors.password_confirmation" class="text-red-500 text-[10px] font-black uppercase mt-2 tracking-widest">{{ form.errors.password_confirmation }}</div>
                        </div>
                    </div>

                    <button 
                        type="submit" 
                        :disabled="form.processing"
                        class="w-full btn-3d btn-3d-green py-5 rounded-2xl font-black uppercase text-xs tracking-widest shadow-[0_5px_0_#5d9933] flex items-center justify-center gap-2"
                    >
                        🏆 Forger mon Destin
                    </button>
                </form>

                <div class="text-center pt-8 border-t border-[#2a245c]">
                    <p class="text-gray-400 font-medium text-sm">Déjà joueur ? 
                        <Link :href="route('login')" class="text-[#ffc628] font-black uppercase tracking-widest hover:underline ml-1">Se connecter</Link>
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
