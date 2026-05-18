<script setup>
import { ref, onMounted } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import ConfirmDialog from 'primevue/confirmdialog';
import { userStatsStore } from '../store.js';

const isMobileMenuOpen = ref(false);
const isDark = ref(true);
const confirm = useConfirm();
const page = usePage();

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

const confirmLogout = () => {
    confirm.require({
        message: 'Êtes-vous sûr de vouloir vous déconnecter ? La partie en cours sera perdue.',
        header: 'Quitter la session',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Rester',
        acceptLabel: 'Quitter',
        rejectClass: 'p-button-secondary p-button-outlined text-gray-300 border-gray-600 hover:bg-gray-800 px-4 py-2 rounded-lg mr-2',
        acceptClass: 'p-button-danger bg-red-600 border-red-600 text-white hover:bg-red-500 px-4 py-2 rounded-lg',
        accept: () => {
            if (document.activeElement) {
                document.activeElement.blur();
            }
            document.body.focus();
            
            router.post(route('logout'));
        }
    });
};

onMounted(() => {
    const savedTheme = localStorage.getItem('cityplay-theme');
    if (savedTheme) {
        isDark.value = savedTheme === 'dark';
    }
    updateTheme();

    const pageUser = page.props.auth?.user;
    if (pageUser) {
        userStatsStore.initialize(pageUser.total_points, pageUser.level_name);
    }
});
</script>

<template>
    <div class="min-h-screen font-sans bg-[#171235] text-white animate-slide-up">
        
        <ConfirmDialog :autoFocus="false">
            <template #container="{ message, acceptCallback, rejectCallback }">
                <div class="bg-[#10101c] border-2 border-[#7751de] p-8 rounded-[2rem] shadow-[0_0_45px_rgba(119,81,222,0.45)] max-w-sm w-full mx-auto relative overflow-hidden animate-slide-up text-center">
                    <div class="absolute -top-10 -left-10 w-24 h-24 bg-[#7751de]/10 rounded-full blur-2xl pointer-events-none"></div>
                    
                    <div class="mb-5 space-y-2">
                        <span class="text-3xl text-glow-yellow block">⚠️</span>
                        <h3 class="text-lg font-black uppercase italic tracking-tighter text-[#ffc628] text-glow-yellow">
                            {{ message.header }}
                        </h3>
                    </div>
                    
                    <p class="text-gray-400 text-sm font-medium leading-relaxed mb-8">
                        {{ message.message }}
                    </p>
                    
                    <div class="flex gap-4 justify-center">
                        <button @click="rejectCallback" class="px-6 py-3.5 rounded-xl font-black uppercase text-[9px] tracking-widest bg-[#1c183a] border border-[#2a245c] text-white hover:text-[#87d74e] transition-colors">
                            {{ message.rejectLabel || 'Annuler' }}
                        </button>
                        <button @click="acceptCallback" class="btn-3d btn-3d-red px-6 py-3.5 rounded-xl font-black uppercase text-[9px] tracking-widest text-white shadow-[0_4px_0_#9e2318]">
                            {{ message.acceptLabel || 'Confirmer' }}
                        </button>
                    </div>
                </div>
            </template>
        </ConfirmDialog>

        <!-- Top Navigation -->
        <nav class="fixed top-0 z-50 w-full border-b bg-[#10101c]/95 border-[#2a245c] backdrop-blur-md">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-20 items-center justify-between">
                    <div class="flex items-center gap-8">
                        <!-- Logo -->
                        <Link href="/dashboard" class="flex items-center gap-2 group">
                            <div class="bg-[#87d74e] p-2.5 rounded-xl shadow-[0_0_15px_rgba(135,215,78,0.4)] transition-all group-hover:scale-110 duration-200">
                                <span class="text-[#10101c] font-black text-lg">CP</span>
                            </div>
                            <span class="text-xl font-black tracking-tighter uppercase italic hidden sm:block text-glow-green text-white">Cityplay</span>
                        </Link>

                        <!-- Player Stats (Desktop) -->
                        <div class="hidden lg:flex items-center gap-4 bg-[#1c183a] border border-[#2a245c] px-4 py-2 rounded-xl">
                            <div class="flex flex-col">
                                <span class="text-[8px] font-black uppercase text-gray-500 tracking-widest">Rang Actuel</span>
                                <span class="text-xs font-black text-[#87d74e] text-glow-green">{{ userStatsStore.levelName }}</span>
                            </div>
                            <div class="h-6 w-[1px] bg-[#2a245c]"></div>
                            <div class="flex flex-col">
                                <span class="text-[8px] font-black uppercase text-gray-500 tracking-widest">Score Global</span>
                                <span class="text-xs font-black text-white">{{ userStatsStore.points }} XP</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <!-- Theme Toggle (Legacy support, hidden for immersion) -->
                        <button @click="toggleTheme" class="p-2 rounded-xl bg-[#1c183a] border border-[#2a245c] text-lg hover:scale-110 transition-all hidden">
                            {{ isDark ? '🌙' : '☀️' }}
                        </button>

                        <!-- Bouton Déconnexion (Bouncy 3D Red) -->
                        <button @click="confirmLogout" class="btn-3d btn-3d-red px-5 py-2.5 text-xs text-white">
                            🚪 Quitter
                        </button>

                        <!-- User Profile Dropdown (Desktop) -->
                        <div class="hidden sm:flex items-center gap-3 border-l border-[#2a245c] pl-6">
                            <div class="text-right">
                                <p class="text-xs font-black uppercase tracking-tight text-white">{{ $page.props.auth.user.name }}</p>
                                <p class="text-[9px] text-[#87d74e] font-bold uppercase tracking-widest italic text-glow-green">Explorateur</p>
                            </div>
                            <div class="h-10 w-10 rounded-full bg-[#1c183a] border-2 border-[#87d74e] shadow-[0_0_15px_rgba(135,215,78,0.35)] flex items-center justify-center font-black text-xs text-[#87d74e] animate-glow-pulse">
                                {{ $page.props.auth.user.name.substring(0, 2).toUpperCase() }}
                            </div>
                        </div>

                        <!-- Hamburger (Mobile) -->
                        <button @click="isMobileMenuOpen = !isMobileMenuOpen" class="sm:hidden text-2xl p-2 rounded-xl bg-[#1c183a] border border-[#2a245c]">
                            {{ isMobileMenuOpen ? '✕' : '☰' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mobile Menu -->
            <transition name="fade">
                <div v-if="isMobileMenuOpen" class="sm:hidden border-t bg-[#10101c] border-[#2a245c] px-6 py-8 space-y-6">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="p-4 rounded-2xl bg-[#1c183a] border border-[#2a245c]">
                            <p class="text-[8px] font-black text-gray-500 uppercase tracking-widest">Points</p>
                            <p class="text-lg font-black text-white">{{ userStatsStore.points }} XP</p>
                        </div>
                        <div class="p-4 rounded-2xl bg-[#1c183a] border border-[#2a245c]">
                            <p class="text-[8px] font-black text-gray-500 uppercase tracking-widest">Niveau</p>
                            <p class="text-lg font-black text-[#87d74e] text-glow-green">{{ userStatsStore.levelName }}</p>
                        </div>
                    </div>
                    <nav class="flex flex-col gap-3">
                        <Link :href="route('dashboard')" class="text-sm font-black uppercase tracking-widest py-3 border-b border-[#2a245c] text-white hover:text-[#87d74e] transition-colors">Tableau de Bord</Link>
                        <Link :href="route('game.progression')" class="text-sm font-black uppercase tracking-widest py-3 border-b border-[#2a245c] text-white hover:text-[#87d74e] transition-colors">Ma Progression</Link>
                        <Link :href="route('profile.edit')" class="text-sm font-black uppercase tracking-widest py-3 border-b border-[#2a245c] text-white hover:text-[#87d74e] transition-colors">Paramètres</Link>
                        <button @click="confirmLogout" class="text-sm font-black uppercase tracking-widest py-3 text-red-500 text-left w-full hover:text-red-400 transition-colors">Déconnexion</button>
                    </nav>
                </div>
            </transition>
        </nav>

        <!-- Main Content Wrapper -->
        <div class="pt-20">
            <!-- Secondary Nav (GeoGuessr Horizontal Tab Console) -->
            <div class="border-b bg-[#10101c]/50 border-[#2a245c] hidden lg:block">
                <div class="mx-auto max-w-7xl px-8 flex gap-8">
                    <Link :href="route('dashboard')" 
                        :class="route().current('dashboard') ? 'border-[#87d74e] text-[#87d74e] text-glow-green' : 'border-transparent text-gray-400 hover:text-white'"
                        class="py-5 border-b-2 text-[10px] font-black uppercase tracking-[0.2em] transition-all duration-200">
                        Vue d'ensemble
                    </Link>
                    <Link :href="route('game.progression')" 
                        :class="route().current('game.progression') ? 'border-[#87d74e] text-[#87d74e] text-glow-green' : 'border-transparent text-gray-400 hover:text-white'"
                        class="py-5 border-b-2 text-[10px] font-black uppercase tracking-[0.2em] transition-all duration-200">
                        Ma Progression
                    </Link>
                    <Link href="#" class="py-5 border-b-2 border-transparent text-gray-400 hover:text-white text-[10px] font-black uppercase tracking-[0.2em] transition-all duration-200">
                        Classement
                    </Link>
                </div>
            </div>

            <!-- Content Area -->
            <main class="mx-auto max-w-7xl p-4 sm:p-6 lg:p-12 relative">
                <!-- Background ambient lights -->
                <div class="absolute top-0 left-1/4 w-96 h-96 bg-[#87d74e]/5 rounded-full blur-[120px] pointer-events-none -z-10"></div>
                <div class="absolute bottom-0 right-1/4 w-96 h-96 bg-purple-600/5 rounded-full blur-[120px] pointer-events-none -z-10"></div>

                <!-- Page Heading (Breeze style slot) -->
                <div v-if="$slots.header" class="mb-12">
                    <slot name="header" />
                </div>
                
                <slot />
            </main>
        </div>

        <!-- Floating Action Button (Mobile) -->
        <button class="fixed bottom-8 right-8 h-16 w-16 bg-[#2fc276] text-white rounded-2xl shadow-[0_0_20px_rgba(47,194,118,0.5)] flex items-center justify-center text-3xl sm:hidden z-40 animate-bounce btn-3d btn-3d-green">
            🚀
        </button>
    </div>
</template>

<style>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800;900&display=swap');

.fade-enter-active, .fade-leave-active {
    transition: opacity 0.3s ease;
}
.fade-enter-from, .fade-leave-to {
    opacity: 0;
}
</style>
