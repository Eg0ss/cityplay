<script setup>
import { onMounted, ref, markRaw } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { 
    MapPin, 
    Puzzle, 
    Trophy, 
    Moon, 
    Sun,
    ChevronLeft,
    Rocket,
    ShieldCheck,
    Terminal,
    Database,
    Zap,
    LogIn,
    Compass,
    Target,
    UserPlus,
    Search,
    Play,
    PenTool,
    Image as ImageIcon,
    Layers,
    LayoutDashboard
} from 'lucide-vue-next';

const page = usePage();
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

const adminSteps = [
    { title: "Accès au Terminal", desc: "Connexion sécurisée au tableau de bord pour superviser les statistiques et les utilisateurs.", icon: markRaw(LayoutDashboard) },
    { title: "Gestion des Cités", desc: "Configuration des villes et départements pour organiser l'univers de jeu par régions.", icon: markRaw(Database) },
    { title: "Déploiement des Lieux", desc: "Placement précis des points d'intérêt sur la carte interactive via les coordonnées GPS.", icon: markRaw(MapPin) },
    { title: "Forge d'Énigmes", desc: "Conception de défis uniques avec validation des réponses et niveaux de difficulté.", icon: markRaw(PenTool) },
    { title: "Système d'Indices", desc: "Intégration d'aides visuelles et textuelles multi-images pour guider les joueurs.", icon: markRaw(Search) },
];

const playerSteps = [
    { title: "Inscription & Profil", desc: "Création de compte et personnalisation pour suivre l'évolution de l'XP et des badges.", icon: markRaw(UserPlus) },
    { title: "Exploration & Choix", desc: "Découverte des villes disponibles sur la carte immersive du Bénin pour choisir une mission.", icon: markRaw(Compass) },
    { title: "Modes de Jeu", desc: "Sélection entre Solo, Coopération (Participants) ou Compétition (Challengers).", icon: markRaw(Layers) },
    { title: "Lobby & Phase de Jeu", desc: "Salle d'attente en temps réel et résolution d'énigmes chronométrées sur les sites historiques.", icon: markRaw(Play) },
    { title: "Triomphe & Classement", desc: "Validation des succès, gain de points XP et ascension dans le classement national.", icon: markRaw(Trophy) },
];
</script>

<template>
    <Head title="Comment Jouer - Cityplay" />
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
            <button @click="toggleTheme" class="h-12 w-12 rounded-2xl dark:bg-white/5 bg-white shadow-xl flex items-center justify-center hover:scale-110 transition-all border dark:border-white/5 border-gray-100">
                <Moon v-if="isDark" :size="20" />
                <Sun v-else :size="20" />
            </button>
        </nav>

        <div class="max-w-7xl mx-auto px-6 space-y-32">
            <!-- Hero -->
            <div class="text-center space-y-6">
                <h1 class="text-5xl lg:text-8xl font-black tracking-tighter uppercase italic leading-none">
                    PROTOCOLE DE <br /><span class="text-[#FF9F1C]">L'AVENTURE</span>
                </h1>
                <p class="text-xl font-medium dark:text-gray-400 text-gray-600 max-w-2xl mx-auto">
                    Découvrez comment fonctionne la matrice Cityplay, de la création des défis à la gloire du classement.
                </p>
            </div>

            <!-- Part 1: Admin Workflow -->
            <div v-if="page.props.auth.user?.is_admin" class="space-y-16">
                <div class="flex items-center gap-6">
                    <div class="h-px flex-1 dark:bg-white/10 bg-gray-200"></div>
                    <h2 class="text-2xl font-black uppercase tracking-[0.3em] text-[#FF9F1C] italic">01. TERMINAL ADMINISTRATEUR</h2>
                    <div class="h-px flex-1 dark:bg-white/10 bg-gray-200"></div>
                </div>

                <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-5">
                    <div v-for="(step, i) in adminSteps" :key="i" 
                        class="dark:bg-[#111113] bg-white p-8 rounded-[2.5rem] border dark:border-white/5 border-gray-100 shadow-sm hover:shadow-2xl transition-all group relative overflow-hidden">
                        <div class="absolute -top-4 -right-4 text-6xl opacity-5 font-black italic">0{{ i + 1 }}</div>
                        
                        <div class="h-14 w-14 dark:bg-white/5 bg-gray-50 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-500 border dark:border-white/10 border-gray-200">
                            <component :is="step.icon" :size="28" class="text-[#FF9F1C]" />
                        </div>
                        <h3 class="text-lg font-black uppercase italic tracking-tighter mb-3 dark:text-white text-gray-900">{{ step.title }}</h3>
                        <p class="text-[11px] dark:text-gray-500 text-gray-500 font-medium leading-relaxed">{{ step.desc }}</p>
                    </div>
                </div>
            </div>

            <!-- Part 2: Player Workflow -->
            <div class="space-y-16">
                <div class="flex items-center gap-6">
                    <div class="h-px flex-1 dark:bg-white/10 bg-gray-200"></div>
                    <h2 class="text-2xl font-black uppercase tracking-[0.3em] text-[#87d74e] italic">02. HUB DES JOUEURS</h2>
                    <div class="h-px flex-1 dark:bg-white/10 bg-gray-200"></div>
                </div>

                <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-5">
                    <div v-for="(step, i) in playerSteps" :key="i" 
                        class="dark:bg-[#111113] bg-white p-8 rounded-[2.5rem] border dark:border-white/5 border-gray-100 shadow-sm hover:shadow-2xl transition-all group relative overflow-hidden">
                        <div class="absolute -top-4 -right-4 text-6xl opacity-5 font-black italic">0{{ i + 1 }}</div>
                        
                        <div class="h-14 w-14 dark:bg-white/5 bg-gray-50 rounded-2xl flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-500 border dark:border-white/10 border-gray-200">
                            <component :is="step.icon" :size="28" class="text-[#87d74e]" />
                        </div>
                        <h3 class="text-lg font-black uppercase italic tracking-tighter mb-3 dark:text-white text-gray-900">{{ step.title }}</h3>
                        <p class="text-[11px] dark:text-gray-500 text-gray-500 font-medium leading-relaxed">{{ step.desc }}</p>
                    </div>
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
