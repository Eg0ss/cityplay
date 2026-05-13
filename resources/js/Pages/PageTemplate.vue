<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    title: String,
    subtitle: String,
});
</script>

<template>
    <Head :title="title + ' - Cityplay Bénin'" />

    <div class="min-h-screen bg-[#FDFCFB] font-sans text-[#1A1A1A] flex flex-col">
        <!-- Navbar -->
        <header class="fixed top-0 z-50 w-full bg-white/80 backdrop-blur-md border-b border-gray-100">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
                <Link href="/" class="flex items-center gap-2">
                    <img src="/favicon.ico" alt="Logo Cityplay" class="h-10 w-auto" />
                    <span class="text-2xl font-bold tracking-tight text-[#1A1A1A]">Cityplay</span>
                </Link>

                <nav class="hidden items-center gap-8 md:flex">
                    <Link :href="route('how-to-play')" class="text-sm font-medium hover:text-[#FF9F1C] transition-colors">Comment jouer ?</Link>
                    <Link :href="route('explore')" class="text-sm font-medium hover:text-[#FF9F1C] transition-colors">Explorer les Lieux</Link>
                    <Link :href="route('leaderboard')" class="text-sm font-medium hover:text-[#FF9F1C] transition-colors">Classement (Leaderboard)</Link>
                    <Link :href="route('blog')" class="text-sm font-medium hover:text-[#FF9F1C] transition-colors">Blog des Explorateurs</Link>
                    <Link :href="route('about')" class="text-sm font-medium hover:text-[#FF9F1C] transition-colors">À Propos</Link>
                    <Link :href="route('contact')" class="text-sm font-medium hover:text-[#FF9F1C] transition-colors">Contact</Link>
                </nav>

                <div class="flex items-center gap-4">
                    <template v-if="$page.props.auth.user">
                        <Link v-if="$page.props.auth.user.is_admin" :href="route('admin.dashboard')" class="text-sm font-semibold text-[#FF9F1C] hover:underline">Admin</Link>
                        <Link :href="route('dashboard')" class="text-sm font-semibold text-[#1A1A1A] hover:text-[#FF9F1C]">Tableau de Bord</Link>
                    </template>
                    <template v-else>
                        <Link href="/" class="text-sm font-semibold text-[#1A1A1A] hover:text-[#FF9F1C]">Accueil</Link>
                        <Link :href="route('register')" class="rounded-full bg-[#FF9F1C] px-6 py-2 text-sm font-semibold text-white shadow-lg shadow-orange-200 transition-all hover:bg-[#e68a00] hover:scale-105 active:scale-95">S'inscrire</Link>
                    </template>
                </div>
            </div>
        </header>

        <!-- Hero Page Header -->
        <section class="pt-32 pb-20 bg-gray-50 border-b border-gray-100">
            <div class="mx-auto max-w-7xl px-6">
                <span class="text-[#FF9F1C] font-bold uppercase tracking-[0.3em] text-xs">Cityplay Bénin</span>
                <h1 class="mt-4 text-5xl md:text-6xl font-black text-[#1A1A1A]">{{ title }}</h1>
                <p v-if="subtitle" class="mt-6 text-xl text-gray-500 max-w-2xl">{{ subtitle }}</p>
            </div>
        </section>

        <!-- Main Content Slot -->
        <main class="py-20 px-6 flex-grow">
            <div class="mx-auto max-w-7xl">
                <slot />
            </div>
        </main>

        <!-- Footer -->
        <footer class="bg-[#1A1A1A] py-12 px-6 text-white text-center">
            <p class="text-gray-500 text-sm">&copy; 2026 Cityplay Bénin. Tous droits réservés.</p>
        </footer>
    </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800;900&display=swap');
.font-sans { font-family: 'Outfit', sans-serif; }
</style>
