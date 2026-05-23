<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { onMounted, ref, watch } from 'vue';
import Modal from '@/Components/Modal.vue';

const gsap = window.gsap;

const props = defineProps({
    canResetPassword: { type: Boolean },
    status: { type: String },
    requires2fa: { type: Boolean, default: false },
    email: { type: String, default: '' },
    remember: { type: Boolean, default: false },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const show2faModal = ref(false);

const form2fa = useForm({
    email: '',
    code: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => {
            form.reset('password');
        },
    });
};

const submit2fa = () => {
    form2fa.post(route('login.2fa'), {
        onFinish: () => form2fa.reset('code'),
    });
};

watch(() => props.requires2fa, (newVal) => {
    if (newVal) {
        form2fa.email = props.email;
        form2fa.remember = props.remember;
        show2faModal.value = true;
    }
}, { immediate: true });

onMounted(() => {
    if (!gsap) return;

    // 🎥 Subtle brightness pulse on video
    gsap.to('.bg-video-container video', {
        opacity: 0.55,
        duration: 6,
        repeat: -1,
        yoyo: true,
        ease: 'sine.inOut'
    });

    // ⚡ Left Side panel elements stagger reveal
    gsap.from('.left-panel-reveal', {
        x: -40,
        opacity: 0,
        duration: 1.2,
        stagger: 0.15,
        ease: 'power3.out'
    });

    // 🎮 Right Side login card back rebound entry
    gsap.from('.form-card-reveal', {
        y: 50,
        opacity: 0,
        duration: 1.4,
        ease: 'back.out(1.2)'
    });
});
</script>

<template>
    <Head title="Connexion - Cityplay" />

    <div class="min-h-screen font-sans flex flex-col lg:flex-row bg-[#171235] text-white overflow-hidden relative">
        
        <!-- Ambient Background glow spots -->
        <div class="absolute top-0 left-1/4 w-96 h-96 bg-[#87d74e]/5 rounded-full blur-[150px] pointer-events-none z-10"></div>
        <div class="absolute bottom-0 right-1/4 w-96 h-96 bg-[#7751de]/10 rounded-full blur-[150px] pointer-events-none z-10"></div>

        <!-- Global background video -->
        <div class="bg-video-container absolute inset-0 z-0 w-full h-full overflow-hidden">
            <video 
                autoplay 
                loop 
                muted 
                playsinline 
                class="w-full h-full object-cover opacity-40 filter brightness-[0.85] contrast-[1.2]"
            >
                <source src="/videos/glowing-map.mp4" type="video/mp4" />
            </video>
            <div class="absolute inset-0 bg-gradient-to-br from-[#10101c]/60 via-[#171235]/50 to-[#171235]/90"></div>
        </div>

        <!-- Left Side: Branding (Desktop Only) -->
        <div class="hidden lg:flex lg:w-1/2 bg-[#10101c]/80 relative overflow-hidden p-12 lg:p-20 flex-col justify-between border-r border-[#2a245c]">
            <!-- World Map Vignette background -->
            <div class="absolute inset-0 z-0">
                <video 
                    autoplay 
                    loop 
                    muted 
                    playsinline 
                    class="h-full w-full object-cover opacity-50 filter brightness-[0.9] contrast-[1.1]"
                >
                    <source src="/videos/glowing-map.mp4" type="video/mp4" />
                </video>
                <div class="absolute inset-0 bg-gradient-to-b from-[#10101c]/30 to-[#171235]/60"></div>
            </div>

            <!-- Floating Bobbing Emojis on Left Side -->
            <div class="absolute top-1/4 left-1/4 text-6xl animate-float opacity-30 select-none pointer-events-none">🧭</div>
            <div class="absolute bottom-1/4 right-1/4 text-6xl animate-float opacity-30 select-none pointer-events-none" style="animation-delay: 1.5s;">🗺️</div>
            
            <Link href="/" class="relative z-10 flex items-center gap-3">
                <div class="h-10 w-10 flex items-center justify-center">
                    <img src="/images/cityplay.png" class="h-full w-full object-contain" alt="Logo" />
                </div>
                <span class="text-3xl font-black tracking-tighter uppercase italic text-white text-glow-green">Cityplay</span>
            </Link>

            <div class="relative z-10 space-y-6 left-panel-reveal">
                <span class="inline-block text-[#87d74e] text-glow-green font-black text-xs tracking-[0.4em] uppercase italic">Console d'accès</span>
                <h1 class="text-6xl lg:text-7xl font-black text-white leading-none tracking-tighter italic uppercase">
                    PRÊT À <br />RÉSOUDRE ?
                </h1>
                <p class="text-lg text-gray-400 max-w-md font-medium leading-relaxed">
                    Connectez-vous pour rejoindre vos amis, valider vos énigmes géolocalisées et revendiquer le haut du podium.
                </p>
            </div>

            <div class="relative z-10 text-gray-500 text-[10px] font-black uppercase tracking-[0.4em] left-panel-reveal">
                👾 SYSTEM STATUS: READY
            </div>
        </div>

        <!-- Right Side: Login Form -->
        <div class="flex-1 flex items-center justify-center p-6 sm:p-12 lg:p-20 relative z-10 overflow-y-auto">
            <!-- Back Button -->
            <Link href="/" class="absolute top-8 right-8 flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-gray-400 hover:text-[#87d74e] transition-all">
                <span>←</span>
                <span>Retour à l'accueil</span>
            </Link>

            <!-- Gaming Panel Card -->
            <div class="w-full max-w-md space-y-10 panel-glass p-8 sm:p-10 rounded-[2.5rem] border border-[#2a245c] shadow-2xl relative hover-lift form-card-reveal">
                <div class="absolute -top-10 -left-10 w-32 h-32 bg-[#87d74e]/5 rounded-full blur-3xl pointer-events-none"></div>

                <div class="lg:hidden flex justify-center mb-6">
                    <Link href="/" class="flex items-center gap-2">
                        <div class="h-10 w-10 flex items-center justify-center">
                            <img src="/images/cityplay.png" class="h-full w-full object-contain" alt="Logo" />
                        </div>
                        <span class="text-2xl font-black tracking-tighter uppercase italic text-glow-green text-white">Cityplay</span>
                    </Link>
                </div>

                <div class="space-y-3">
                    <h2 class="text-3xl lg:text-4xl font-black uppercase italic tracking-tighter">
                        Accès <span class="text-[#87d74e]">Explorateur</span>
                    </h2>
                    <p class="text-gray-400 text-sm font-medium">Entrez vos identifiants pour démarrer la session.</p>
                </div>

                <div v-if="status" class="bg-[#87d74e]/10 border border-[#87d74e]/20 p-5 rounded-2xl text-[#87d74e] text-xs font-black uppercase tracking-widest">
                    {{ status }}
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black uppercase tracking-widest text-gray-400">Identifiant (Email)</label>
                        <input 
                            v-model="form.email"
                            type="email" 
                            required
                            class="w-full bg-[#10101c] border border-[#2a245c] rounded-2xl py-4.5 px-6 focus:ring-2 focus:ring-[#87d74e] focus:border-[#87d74e] transition-all font-bold text-white placeholder-gray-500"
                            placeholder="votre@email.com"
                        />
                        <div v-if="form.errors.email" class="text-red-500 text-[10px] font-black uppercase mt-2 tracking-widest">{{ form.errors.email }}</div>
                    </div>

                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <label class="block text-[10px] font-black uppercase tracking-widest text-gray-400">Mot de passe</label>
                            <Link v-if="canResetPassword" :href="route('password.request')" class="text-[10px] font-black uppercase tracking-widest text-[#ffc628] hover:underline">Oublié ?</Link>
                        </div>
                        <input 
                            v-model="form.password"
                            type="password" 
                            required
                            class="w-full bg-[#10101c] border border-[#2a245c] rounded-2xl py-4.5 px-6 focus:ring-2 focus:ring-[#87d74e] focus:border-[#87d74e] transition-all font-bold text-white placeholder-gray-500"
                            placeholder="••••••••"
                        />
                        <div v-if="form.errors.password" class="text-red-500 text-[10px] font-black uppercase mt-2 tracking-widest">{{ form.errors.password }}</div>
                    </div>

                    <div class="flex items-center">
                        <input v-model="form.remember" type="checkbox" class="rounded-lg border-[#2a245c] text-[#87d74e] focus:ring-[#87d74e] bg-[#10101c]" />
                        <span class="ms-3 text-xs font-black uppercase tracking-widest text-gray-400">Maintenir la Session</span>
                    </div>

                    <button 
                        type="submit" 
                        :disabled="form.processing"
                        class="w-full btn-3d btn-3d-green py-5 rounded-2xl font-black uppercase text-xs tracking-widest shadow-[0_5px_0_#5d9933] flex items-center justify-center gap-2"
                    >
                        🎮 Initialiser la Connexion
                    </button>
                </form>

                <div class="text-center pt-8 border-t border-[#2a245c]">
                    <p class="text-gray-400 font-medium text-sm">Nouveau joueur ? 
                        <Link :href="route('register')" class="text-[#ffc628] font-black uppercase tracking-widest hover:underline ml-1">Créer un compte</Link>
                    </p>
                </div>
            </div>
        </div>

        <!-- 2FA Verification Modal -->
        <Modal :show="show2faModal" @close="show2faModal = false" maxWidth="md">
            <div class="p-8 bg-[#10101c] border border-[#2a245c] rounded-[2.5rem] shadow-2xl relative overflow-hidden">
                <!-- Decorative element -->
                <div class="absolute -top-10 -right-10 w-32 h-32 bg-[#87d74e]/5 rounded-full blur-3xl pointer-events-none"></div>
                
                <div class="space-y-6 relative z-10">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 bg-[#87d74e]/20 rounded-xl flex items-center justify-center">
                            <span class="text-xl">🛡️</span>
                        </div>
                        <h2 class="text-2xl font-black uppercase italic tracking-tighter text-white">
                            Double <span class="text-[#87d74e]">Vérification</span>
                        </h2>
                    </div>

                    <p class="text-gray-400 text-sm font-medium leading-relaxed">
                        Un code de sécurité a été envoyé à l'adresse 
                        <span class="text-white font-bold">{{ props.email }}</span>. 
                        Veuillez le saisir ci-dessous pour continuer (Valable 1 minute).
                    </p>

                    <form @submit.prevent="submit2fa" class="space-y-6">
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black uppercase tracking-widest text-gray-400">Code de sécurité (6 chiffres)</label>
                            <input 
                                v-model="form2fa.code"
                                type="text" 
                                required
                                maxlength="6"
                                class="w-full bg-[#171235] border border-[#2a245c] rounded-2xl py-5 px-6 focus:ring-2 focus:ring-[#87d74e] focus:border-[#87d74e] transition-all font-black text-white text-center text-2xl tracking-[0.5em] uppercase placeholder-gray-700"
                                placeholder="XXXXXX"
                                autofocus
                            />
                            <div v-if="form2fa.errors.code" class="text-red-500 text-[10px] font-black uppercase mt-2 tracking-widest text-center">{{ form2fa.errors.code }}</div>
                        </div>

                        <button 
                            type="submit" 
                            :disabled="form2fa.processing"
                            class="w-full btn-3d btn-3d-green py-5 rounded-2xl font-black uppercase text-xs tracking-widest shadow-[0_5px_0_#5d9933] flex items-center justify-center gap-2"
                        >
                            🚀 Valider et Démarrer
                        </button>

                        <button 
                            type="button"
                            @click="show2faModal = false"
                            class="w-full text-[10px] font-black uppercase tracking-widest text-gray-500 hover:text-white transition-colors"
                        >
                            Annuler la connexion
                        </button>
                    </form>
                </div>
            </div>
        </Modal>
    </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800;900&display=swap');
.font-sans { font-family: 'Outfit', sans-serif; }
</style>
