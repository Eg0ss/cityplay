<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    title: String,
    subtitle: String,
});
</script>

<template>
    <Head :title="title + ' - Cityplay'" />

    <div class="min-h-screen bg-white font-sans text-[#1A1A1A] flex flex-col">
        <!-- Navbar (GeoGuessr Style) -->
        <header class="fixed top-0 z-50 w-full bg-white/90 backdrop-blur-md border-b border-gray-100">
            <div class="mx-auto flex max-w-screen-2xl items-center justify-between px-6 py-3">
                <div class="flex items-center gap-8">
                    <Link href="/" class="flex items-center gap-3">
                        <div class="h-10 w-10 flex items-center justify-center">
                            <img src="/images/cityplay.png" class="h-full w-full object-contain" alt="Logo" />
                        </div>
                        <span class="text-2xl font-black tracking-tighter uppercase italic">Cityplay</span>
                    </Link>
                    <nav class="hidden items-center gap-6 lg:flex">
                        <Link :href="route('how-to-play')" class="text-xs font-black uppercase tracking-widest hover:text-[#FF9F1C] transition-colors">Championnat</Link>
                        <Link v-if="$page.props.auth.user?.is_admin" :href="route('admin.cities')" class="text-xs font-black uppercase tracking-widest hover:text-[#FF9F1C] transition-colors">Explorer</Link>
                        <Link :href="route('leaderboard')" class="text-xs font-black uppercase tracking-widest hover:text-[#FF9F1C] transition-colors">Classement</Link>
                    </nav>
                </div>

                <div class="flex items-center gap-4">
                    <template v-if="$page.props.auth.user">
                        <Link v-if="$page.props.auth.user.role === 'admin'" :href="route('admin.dashboard')" class="text-xs font-black uppercase tracking-widest text-[#FF9F1C] hover:underline">Admin</Link>
                        <Link :href="route('dashboard')" class="text-xs font-black uppercase tracking-widest hover:text-[#FF9F1C]">Mon Profil</Link>
                    </template>
                    <template v-else>
                        <Link :href="route('login')" class="text-xs font-black uppercase tracking-widest hover:text-[#FF9F1C]">Connexion</Link>
                        <Link :href="route('register')" class="bg-[#4CAF50] text-white px-6 py-3 rounded-xl text-xs font-black uppercase tracking-widest shadow-lg shadow-green-100 hover:scale-105 transition-all">Jouer Maintenant</Link>
                    </template>
                </div>
            </div>
        </header>

        <!-- Page Header -->
        <section class="pt-40 pb-20 bg-gray-50 border-b border-gray-100">
            <div class="mx-auto max-w-7xl px-6">
                <span class="text-[#FF9F1C] font-black uppercase italic tracking-tighter text-sm">Cityplay Adventure</span>
                <h1 class="mt-4 text-6xl font-black uppercase italic tracking-tighter text-[#1A1A1A]">{{ title }}</h1>
                <p v-if="subtitle" class="mt-6 text-2xl font-bold text-gray-500 max-w-3xl leading-tight">{{ subtitle }}</p>
            </div>
        </section>

        <!-- Main Content Slot -->
        <main class="py-20 px-6 flex-grow">
            <div class="mx-auto max-w-7xl">
                <slot />
            </div>
        </main>

        <!-- Footer -->
        <footer class="bg-gray-50 py-12 px-6 border-t border-gray-100">
            <div class="mx-auto max-w-7xl flex flex-col md:flex-row justify-between items-center gap-8">
                <div class="flex items-center gap-2">
                    <div class="h-10 w-10 flex items-center justify-center">
                        <img src="/images/cityplay.png" class="h-full w-full object-contain" alt="Logo" />
                    </div>
                    <span class="text-xl font-bold tracking-tighter uppercase italic">Cityplay</span>
                </div>
                <div class="text-xs font-bold text-gray-400 uppercase tracking-widest space-x-8">
                    <a href="#" class="hover:text-[#FF9F1C]">Confidentialité</a>
                    <a href="#" class="hover:text-[#FF9F1C]">Conditions</a>
                    <a href="#" class="hover:text-[#FF9F1C]">Contact</a>
                </div>
                <p class="text-gray-400 text-xs font-bold uppercase tracking-widest">&copy; 2026 Cityplay Adventure.</p>
            </div>
        </footer>
    </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800;900&display=swap');
.font-sans { font-family: 'Outfit', sans-serif; }
</style>
