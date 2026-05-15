<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen bg-[#0A0A0B] text-white font-sans flex overflow-hidden">
        <!-- Sidebar -->
        <aside class="w-72 bg-[#111113] border-r border-white/5 flex flex-col fixed h-full z-20">
            <div class="p-8 flex flex-col items-center gap-4">
                <div class="h-16 w-16 bg-gradient-to-tr from-[#FF9F1C] to-[#FFBF69] rounded-2xl flex items-center justify-center shadow-[0_0_20px_rgba(255,159,28,0.3)] rotate-3">
                    <span class="text-3xl font-black text-black">CP</span>
                </div>
                <div class="text-center">
                    <span class="text-xl font-black tracking-tighter uppercase italic block">Cityplay</span>
                    <span class="text-[10px] text-[#FF9F1C] font-bold tracking-[0.3em] uppercase opacity-80">Admin Terminal</span>
                </div>
            </div>

            <nav class="flex-1 px-6 py-8 space-y-3">
                <Link :href="route('admin.dashboard')" 
                    :class="route().current('admin.dashboard') ? 'bg-[#FF9F1C] text-black shadow-[0_0_15px_rgba(255,159,28,0.4)]' : 'text-gray-400 hover:bg-white/5 hover:text-white'" 
                    class="flex items-center gap-4 px-5 py-4 rounded-2xl transition-all duration-300 group">
                    <span class="text-xl group-hover:scale-110 transition-transform">📊</span>
                    <span class="font-black uppercase text-sm tracking-wider">Tableau de Bord</span>
                </Link>

                <Link :href="route('admin.places')" 
                    :class="route().current('admin.places*') ? 'bg-[#FF9F1C] text-black shadow-[0_0_15px_rgba(255,159,28,0.4)]' : 'text-gray-400 hover:bg-white/5 hover:text-white'" 
                    class="flex items-center gap-4 px-5 py-4 rounded-2xl transition-all duration-300 group">
                    <span class="text-xl group-hover:scale-110 transition-transform">📍</span>
                    <span class="font-black uppercase text-sm tracking-wider">Exploration (Lieux)</span>
                </Link>

                <div class="pt-8">
                    <p class="px-5 text-[10px] font-bold text-gray-600 uppercase tracking-[0.2em] mb-4">Système</p>
                    <Link href="/" class="flex items-center gap-4 px-5 py-4 rounded-2xl text-gray-400 hover:bg-white/5 hover:text-white transition-all group">
                        <span class="text-xl group-hover:scale-110 transition-transform">🌍</span>
                        <span class="font-bold uppercase text-xs tracking-wider">Aller au Site</span>
                    </Link>
                </div>
            </nav>

            <div class="p-8 border-t border-white/5 bg-[#0D0D0F]">
                <div class="flex items-center gap-3 mb-6">
                    <div class="h-10 w-10 rounded-full bg-gray-800 border border-white/10 flex items-center justify-center font-bold text-xs text-[#FF9F1C]">
                        AD
                    </div>
                    <div>
                        <p class="text-xs font-black uppercase truncate max-w-[120px]">{{ $page.props.auth.user.name }}</p>
                        <p class="text-[10px] text-gray-500 font-bold uppercase tracking-tighter">Administrateur</p>
                    </div>
                </div>
                <Link :href="route('logout')" method="post" as="button" class="w-full py-3 rounded-xl border border-white/5 text-[10px] font-black uppercase tracking-widest text-gray-500 hover:text-red-500 hover:border-red-500/30 transition-all">
                    Quitter la session
                </Link>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="ml-72 flex-1 relative h-screen overflow-y-auto">
            <!-- Background Decoration -->
            <div class="fixed top-0 right-0 w-[500px] h-[500px] bg-[#FF9F1C]/5 blur-[120px] -z-10 rounded-full"></div>
            <div class="fixed bottom-0 left-72 w-[300px] h-[300px] bg-blue-500/5 blur-[100px] -z-10 rounded-full"></div>
            
            <div class="p-12">
                <slot />
            </div>
        </main>
    </div>
</template>

<style>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800;900&display=swap');

body {
    background-color: #0A0A0B;
}

::-webkit-scrollbar {
    width: 6px;
}

::-webkit-scrollbar-track {
    background: transparent;
}

::-webkit-scrollbar-thumb {
    background: #1A1A1C;
    border-radius: 10px;
}

::-webkit-scrollbar-thumb:hover {
    background: #2A2A2C;
}
</style>
