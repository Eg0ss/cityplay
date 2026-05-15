<script setup>
import { Link } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

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
    <div class="min-h-screen font-sans flex overflow-hidden transition-colors duration-300" 
         :class="isDark ? 'bg-[#0A0A0B] text-white' : 'bg-gray-50 text-gray-900'">
        
        <!-- Mobile Header -->
        <header class="lg:hidden fixed top-0 w-full z-30 px-6 py-4 flex justify-between items-center border-b backdrop-blur-md"
                :class="isDark ? 'bg-black/50 border-white/5' : 'bg-white/70 border-gray-200'">
            <div class="flex items-center gap-3">
                <div class="h-8 w-8 bg-[#FF9F1C] rounded-lg flex items-center justify-center font-black text-black text-xs">CP</div>
                <span class="font-black uppercase italic text-sm tracking-tighter">Cityplay Admin</span>
            </div>
            <div class="flex items-center gap-4">
                <button @click="toggleTheme" class="text-xl">
                    {{ isDark ? '🌙' : '☀️' }}
                </button>
                <button @click="isMobileMenuOpen = !isMobileMenuOpen" class="text-2xl">
                    {{ isMobileMenuOpen ? '✕' : '☰' }}
                </button>
            </div>
        </header>

        <!-- Sidebar -->
        <aside :class="[
                    isDark ? 'bg-[#111113] border-white/5' : 'bg-white border-gray-200',
                    isMobileMenuOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
                ]"
               class="w-72 border-r flex flex-col fixed h-full z-40 transition-transform duration-300 lg:z-20">
            
            <div class="p-8 flex flex-col items-center gap-4">
                <div class="h-16 w-16 bg-gradient-to-tr from-[#FF9F1C] to-[#FFBF69] rounded-2xl flex items-center justify-center shadow-lg rotate-3">
                    <span class="text-3xl font-black text-black">CP</span>
                </div>
                <div class="text-center">
                    <span class="text-xl font-black tracking-tighter uppercase italic block">Cityplay</span>
                    <span class="text-[10px] text-[#FF9F1C] font-bold tracking-[0.3em] uppercase opacity-80">Admin Terminal</span>
                </div>
            </div>

            <nav class="flex-1 px-6 py-8 space-y-3">
                <Link :href="route('admin.dashboard')" 
                    @click="isMobileMenuOpen = false"
                    :class="route().current('admin.dashboard') ? 'bg-[#FF9F1C] text-black shadow-lg' : (isDark ? 'text-gray-400 hover:bg-white/5 hover:text-white' : 'text-gray-600 hover:bg-gray-100')" 
                    class="flex items-center gap-4 px-5 py-4 rounded-2xl transition-all duration-300 group">
                    <span class="text-xl">📊</span>
                    <span class="font-black uppercase text-sm tracking-wider">Dashboard</span>
                </Link>

                <Link :href="route('admin.cities')" 
                    @click="isMobileMenuOpen = false"
                    :class="route().current('admin.cities*') || route().current('admin.cities.places*') ? 'bg-[#FF9F1C] text-black shadow-lg' : (isDark ? 'text-gray-400 hover:bg-white/5 hover:text-white' : 'text-gray-600 hover:bg-gray-100')" 
                    class="flex items-center gap-4 px-5 py-4 rounded-2xl transition-all duration-300 group">
                    <span class="text-xl">🏙️</span>
                    <span class="font-black uppercase text-sm tracking-wider">Lieux & Cités</span>
                </Link>

                <div class="pt-8">
                    <p class="px-5 text-[10px] font-bold text-gray-500 uppercase tracking-[0.2em] mb-4">Système</p>
                    <button @click="toggleTheme" 
                        :class="isDark ? 'text-gray-400 hover:bg-white/5' : 'text-gray-600 hover:bg-gray-100'"
                        class="w-full flex items-center gap-4 px-5 py-4 rounded-2xl transition-all group mb-2">
                        <span class="text-xl">{{ isDark ? '🌙' : '☀️' }}</span>
                        <span class="font-bold uppercase text-xs tracking-wider">{{ isDark ? 'Mode Sombre' : 'Mode Clair' }}</span>
                    </button>
                    <Link href="/" class="flex items-center gap-4 px-5 py-4 rounded-2xl transition-all group"
                        :class="isDark ? 'text-gray-400 hover:bg-white/5' : 'text-gray-600 hover:bg-gray-100'">
                        <span class="text-xl">🌍</span>
                        <span class="font-bold uppercase text-xs tracking-wider">Site Public</span>
                    </Link>
                </div>
            </nav>

            <div class="p-8 border-t bg-opacity-50" :class="isDark ? 'border-white/5 bg-[#0D0D0F]' : 'border-gray-100 bg-gray-50'">
                <div class="flex items-center gap-3 mb-6">
                    <div class="h-10 w-10 rounded-full bg-gray-200 border flex items-center justify-center font-bold text-xs text-[#FF9F1C]">
                        AD
                    </div>
                    <div class="overflow-hidden">
                        <p class="text-xs font-black uppercase truncate">{{ $page.props.auth.user.name }}</p>
                        <p class="text-[10px] text-gray-500 font-bold uppercase tracking-tighter">Administrateur</p>
                    </div>
                </div>
                <Link :href="route('logout')" method="post" as="button" class="w-full py-3 rounded-xl border text-[10px] font-black uppercase tracking-widest transition-all"
                    :class="isDark ? 'border-white/5 text-gray-500 hover:text-red-500' : 'border-gray-200 text-gray-400 hover:text-red-600'">
                    Quitter
                </Link>
            </div>
        </aside>

        <!-- Overlay for mobile -->
        <div v-if="isMobileMenuOpen" 
             @click="isMobileMenuOpen = false"
             class="fixed inset-0 bg-black/60 backdrop-blur-sm z-30 lg:hidden"></div>

        <!-- Main Content -->
        <main class="lg:ml-72 flex-1 relative h-screen overflow-y-auto pt-20 lg:pt-0">

            <!-- Decorative Backgrounds (Visible only in Dark Mode) -->
            <div v-if="isDark" class="fixed top-0 right-0 w-[500px] h-[500px] bg-[#FF9F1C]/5 blur-[120px] -z-10 rounded-full"></div>
            
            <div class="p-6 lg:p-12 max-w-7xl mx-auto">
                <slot />
            </div>
        </main>
    </div>
</template>

<style>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800;900&display=swap');

html.dark {
    background-color: #0A0A0B;
}

::-webkit-scrollbar {
    width: 6px;
}

::-webkit-scrollbar-track {
    background: transparent;
}

::-webkit-scrollbar-thumb {
    background: #FF9F1C;
    border-radius: 10px;
}
</style>
