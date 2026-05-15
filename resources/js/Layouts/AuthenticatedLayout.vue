<script setup>
import { ref, onMounted } from 'vue';
import { Link } from '@inertiajs/vue3';

const isMobileMenuOpen = ref(false);
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
</script>

<template>
    <div class="min-h-screen font-sans transition-colors duration-300"
         :class="isDark ? 'bg-[#0A0A0B] text-white' : 'bg-gray-50 text-gray-900'">
        
        <!-- Top Navigation -->
        <nav class="fixed top-0 z-50 w-full border-b backdrop-blur-md transition-colors"
             :class="isDark ? 'bg-black/80 border-white/5' : 'bg-white/80 border-gray-200'">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-20 items-center justify-between">
                    <div class="flex items-center gap-8">
                        <!-- Logo -->
                        <Link href="/dashboard" class="flex items-center gap-2">
                            <div class="bg-[#FF9F1C] p-2 rounded-lg shadow-lg">
                                <span class="text-white font-black text-lg">CP</span>
                            </div>
                            <span class="text-lg font-black tracking-tighter uppercase italic hidden sm:block">Cityplay</span>
                        </Link>

                        <!-- Player Stats (Desktop) -->
                        <div class="hidden lg:flex items-center gap-6">
                            <div class="flex flex-col">
                                <span class="text-[8px] font-black uppercase text-gray-500 tracking-widest">Niveau</span>
                                <span class="text-xs font-black dark:text-[#FF9F1C] text-gray-900">BRONZE II</span>
                            </div>
                            <div class="h-8 w-[1px] dark:bg-white/5 bg-gray-200"></div>
                            <div class="flex flex-col">
                                <span class="text-[8px] font-black uppercase text-gray-500 tracking-widest">XP Actuel</span>
                                <span class="text-xs font-black dark:text-white text-gray-900">1,240 XP</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <!-- Theme Toggle -->
                        <button @click="toggleTheme" class="p-2 rounded-xl dark:bg-white/5 bg-gray-100 text-xl hover:scale-110 transition-all">
                            {{ isDark ? '🌙' : '☀️' }}
                        </button>

                        <!-- User Profile Dropdown (Desktop) -->
                        <div class="hidden sm:flex items-center gap-4 border-l dark:border-white/5 border-gray-200 pl-6">
                            <div class="text-right">
                                <p class="text-xs font-black uppercase tracking-tight">{{ $page.props.auth.user.name }}</p>
                                <p class="text-[9px] text-[#FF9F1C] font-bold uppercase tracking-widest italic">Explorateur</p>
                            </div>
                            <div class="h-10 w-10 rounded-full dark:bg-white/5 bg-gray-200 border dark:border-white/10 border-gray-300 flex items-center justify-center font-black text-xs text-[#FF9F1C]">
                                {{ $page.props.auth.user.name.substring(0, 2).toUpperCase() }}
                            </div>
                        </div>

                        <!-- Hamburger (Mobile) -->
                        <button @click="isMobileMenuOpen = !isMobileMenuOpen" class="sm:hidden text-2xl">
                            {{ isMobileMenuOpen ? '✕' : '☰' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mobile Menu -->
            <transition name="fade">
                <div v-if="isMobileMenuOpen" class="sm:hidden border-t dark:bg-[#0D0D0F] bg-white dark:border-white/5 border-gray-200 px-6 py-8 space-y-6">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="p-4 rounded-2xl dark:bg-white/5 bg-gray-50 border dark:border-white/5 border-gray-100">
                            <p class="text-[8px] font-black text-gray-500 uppercase tracking-widest">Points</p>
                            <p class="text-lg font-black dark:text-[#FF9F1C] text-gray-900">4,500</p>
                        </div>
                        <div class="p-4 rounded-2xl dark:bg-white/5 bg-gray-50 border dark:border-white/5 border-gray-100">
                            <p class="text-[8px] font-black text-gray-500 uppercase tracking-widest">Niveau</p>
                            <p class="text-lg font-black dark:text-blue-500 text-gray-900">12</p>
                        </div>
                    </div>
                    <nav class="flex flex-col gap-4">
                        <Link :href="route('dashboard')" class="text-sm font-black uppercase tracking-widest py-3 border-b dark:border-white/5 border-gray-100">Tableau de Bord</Link>
                        <Link href="#" class="text-sm font-black uppercase tracking-widest py-3 border-b dark:border-white/5 border-gray-100">Ma Progression</Link>
                        <Link :href="route('profile.edit')" class="text-sm font-black uppercase tracking-widest py-3 border-b dark:border-white/5 border-gray-100">Paramètres</Link>
                        <Link :href="route('logout')" method="post" as="button" class="text-sm font-black uppercase tracking-widest py-3 text-red-500 text-left">Déconnexion</Link>
                    </nav>
                </div>
            </transition>
        </nav>

        <!-- Main Content Wrapper -->
        <div class="pt-20">
            <!-- Secondary Nav (Optional for Sub-pages) -->
            <div class="border-b dark:bg-white/2 dark:border-white/5 border-gray-200 hidden lg:block">
                <div class="mx-auto max-w-7xl px-8 flex gap-8">
                    <Link :href="route('dashboard')" 
                        :class="route().current('dashboard') ? 'border-[#FF9F1C] text-[#FF9F1C]' : 'border-transparent text-gray-500 hover:text-gray-900 dark:hover:text-white'"
                        class="py-4 border-b-2 text-[10px] font-black uppercase tracking-[0.2em] transition-all">
                        Vue d'ensemble
                    </Link>
                    <Link href="#" class="py-4 border-b-2 border-transparent text-gray-500 hover:text-gray-900 dark:hover:text-white text-[10px] font-black uppercase tracking-[0.2em] transition-all">
                        Quêtes Dispo
                    </Link>
                    <Link href="#" class="py-4 border-b-2 border-transparent text-gray-500 hover:text-gray-900 dark:hover:text-white text-[10px] font-black uppercase tracking-[0.2em] transition-all">
                        Classement
                    </Link>
                </div>
            </div>

            <!-- Content Area -->
            <main class="mx-auto max-w-7xl p-6 lg:p-12">
                <!-- Page Heading (Breeze style slot) -->
                <div v-if="$slots.header" class="mb-12">
                    <slot name="header" />
                </div>
                
                <slot />
            </main>
        </div>

        <!-- Floating Action Button (Mobile) -->
        <button class="fixed bottom-8 right-8 h-16 w-16 bg-[#FF9F1C] text-black rounded-2xl shadow-2xl flex items-center justify-center text-3xl sm:hidden z-40 animate-bounce">
            🚀
        </button>
    </div>
</template>

<style>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800;900&display=swap');

html.dark {
    background-color: #0A0A0B;
}

.fade-enter-active, .fade-leave-active {
    transition: opacity 0.3s ease;
}
.fade-enter-from, .fade-leave-to {
    opacity: 0;
}
</style>
