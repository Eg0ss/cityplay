<script setup>
import { onMounted, ref } from 'vue';
import { Link } from '@inertiajs/vue3';

const isDark = ref(true);

onMounted(() => {
    const savedTheme = localStorage.getItem('cityplay-theme');
    if (savedTheme) {
        isDark.value = savedTheme === 'dark';
    }
    if (isDark.value) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
});
</script>

<template>
    <div class="min-h-screen font-sans flex flex-col sm:justify-center items-center pt-6 sm:pt-0 transition-colors duration-300 overflow-hidden relative"
         :class="isDark ? 'bg-[#0A0A0B] text-white' : 'bg-gray-50 text-gray-900'">
        
        <!-- Animated Background Elements -->
        <div class="absolute top-0 left-0 w-full h-full overflow-hidden pointer-events-none z-0">
            <div class="absolute -top-[10%] -left-[10%] w-[40%] h-[40%] rounded-full opacity-20 blur-[120px] bg-[#FF9F1C]"></div>
            <div class="absolute -bottom-[10%] -right-[10%] w-[40%] h-[40%] rounded-full opacity-10 blur-[120px] bg-blue-500"></div>
        </div>

        <div class="relative z-10 mb-8">
            <Link href="/" class="flex flex-col items-center gap-4">
                <div class="bg-[#FF9F1C] p-4 rounded-[1.5rem] shadow-2xl shadow-[#FF9F1C]/20 hover:scale-110 transition-transform">
                    <span class="text-white font-black text-3xl">CP</span>
                </div>
                <h1 class="text-2xl font-black tracking-tighter uppercase italic dark:text-white text-gray-900">Cityplay <span class="text-[#FF9F1C]">Terminal</span></h1>
            </Link>
        </div>

        <div
            class="relative z-10 w-full sm:max-w-md mt-6 px-8 py-10 dark:bg-[#111113]/80 bg-white/80 backdrop-blur-xl shadow-2xl overflow-hidden sm:rounded-[2.5rem] border dark:border-white/5 border-gray-100"
        >
            <slot />
        </div>

        <div class="relative z-10 mt-8 text-[10px] font-black uppercase tracking-[0.4em] text-gray-500">
            Adventure Awaits
        </div>
    </div>
</template>

<style>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800;900&display=swap');
.font-sans { font-family: 'Outfit', sans-serif; }
</style>
