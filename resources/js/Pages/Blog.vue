<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    title: String,
    content: String
});
</script>

<template>
    <Head :title="title" />

    <div class="min-h-screen bg-[#FDFCFB] font-sans text-[#1A1A1A]">
        <header class="bg-white border-b border-gray-100">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
                <Link href="/" class="flex items-center gap-2">
                    <img src="/favicon.ico" alt="Logo Cityplay" class="h-10 w-auto" />
                    <span class="text-2xl font-bold tracking-tight text-[#1A1A1A]">Cityplay</span>
                </Link>
                <Link href="/" class="text-sm font-semibold text-[#FF9F1C]">Retour à l'accueil</Link>
            </div>
        </header>

        <main class="py-20 px-6">
            <div class="mx-auto max-w-3xl text-center">
                <h1 class="text-5xl font-black mb-8">{{ title }}</h1>
                <p class="text-xl text-gray-500 leading-relaxed">
                    {{ content || "Cette page est en cours de construction. Revenez bientôt pour découvrir tout le contenu lié au Bénin !" }}
                </p>
                <div class="mt-12">
                    <Link href="/" class="inline-block rounded-full bg-[#FF9F1C] px-8 py-4 text-lg font-bold text-white shadow-xl transition-all hover:scale-105 hover:bg-[#e68a00]">
                        Retour à l'accueil
                    </Link>
                </div>
            </div>
        </main>
    </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800;900&display=swap');
.font-sans { font-family: 'Outfit', sans-serif; }
</style>
