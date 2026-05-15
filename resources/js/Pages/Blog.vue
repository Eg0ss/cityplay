<script setup>
import { onMounted, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    title: { type: String, default: "JOURNAL D'AVENTURE" },
    content: { type: String }
});

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

const posts = [
    { title: "Les secrets de Ouidah", category: "Histoire", date: "15 Mai 2026", image: "https://images.unsplash.com/photo-1590001158193-7fef71a8f94f?q=80&w=600" },
    { title: "L'art des Amazones", category: "Culture", date: "12 Mai 2026", image: "https://images.unsplash.com/photo-1578301978693-85fa9c0320b9?q=80&w=600" },
    { title: "Randonnée à Natitingou", category: "Nature", date: "10 Mai 2026", image: "https://images.unsplash.com/photo-1527853787696-f7be74f2e3ee?q=80&w=600" },
];
</script>

<template>
    <Head :title="title" />
    <div class="min-h-screen font-sans transition-colors duration-300 pb-20"
         :class="isDark ? 'bg-[#0A0A0B] text-white' : 'bg-gray-50 text-gray-900'">
        
        <!-- Navigation -->
        <nav class="max-w-7xl mx-auto px-6 py-10 flex justify-between items-center">
            <Link href="/" class="flex items-center gap-3">
                <div class="bg-[#FF9F1C] p-2 rounded-lg">
                    <span class="text-white font-black text-xl">CP</span>
                </div>
                <span class="text-2xl font-black tracking-tighter uppercase italic">Cityplay</span>
            </Link>
            <button @click="toggleTheme" class="h-12 w-12 rounded-2xl dark:bg-white/5 bg-white shadow-xl flex items-center justify-center text-xl hover:scale-110 transition-all border dark:border-white/5 border-gray-100">
                {{ isDark ? '🌙' : '☀️' }}
            </button>
        </nav>

        <main class="max-w-7xl mx-auto px-6 space-y-24">
            <!-- Header -->
            <div class="max-w-3xl space-y-6">
                <span class="text-[10px] font-black uppercase tracking-[0.4em] text-[#FF9F1C]">Le Journal de l'Explorateur</span>
                <h1 class="text-5xl lg:text-8xl font-black tracking-tighter uppercase italic leading-none">{{ title }}</h1>
                <p class="text-xl font-medium dark:text-gray-500 text-gray-500 leading-relaxed">
                    Découvrez des récits captivants, des guides pratiques et les dernières actualités sur le patrimoine du Bénin.
                </p>
            </div>

            <!-- Content Area -->
            <div v-if="!content" class="grid gap-10 md:grid-cols-3">
                <div v-for="(post, i) in posts" :key="i" 
                    class="group dark:bg-[#111113] bg-white rounded-[2.5rem] overflow-hidden border dark:border-white/5 border-gray-100 shadow-sm hover:shadow-2xl transition-all duration-500">
                    <div class="aspect-video overflow-hidden">
                        <img :src="post.image" :alt="post.title" class="w-full h-full object-cover grayscale-[40%] group-hover:grayscale-0 group-hover:scale-110 transition-all duration-700" />
                    </div>
                    <div class="p-8 space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-[10px] font-black uppercase tracking-widest text-[#FF9F1C]">{{ post.category }}</span>
                            <span class="text-[10px] font-bold text-gray-500 uppercase">{{ post.date }}</span>
                        </div>
                        <h3 class="text-2xl font-black uppercase italic tracking-tighter dark:text-white text-gray-900 group-hover:text-[#FF9F1C] transition-colors">{{ post.title }}</h3>
                        <Link href="#" class="inline-block text-[10px] font-black uppercase tracking-widest border-b-2 border-[#FF9F1C] pb-1 hover:translate-x-2 transition-transform">Lire l'article</Link>
                    </div>
                </div>
            </div>

            <div v-else class="max-w-3xl prose prose-xl dark:prose-invert mx-auto">
                {{ content }}
            </div>

            <!-- Empty State / Placeholder -->
            <div v-if="!content && posts.length === 0" class="text-center py-24 dark:bg-white/5 bg-white rounded-[4rem] border-2 border-dashed dark:border-white/5 border-gray-100">
                <p class="text-gray-500 font-black uppercase tracking-widest">En cours de synchronisation...</p>
            </div>

            <!-- Footer Link -->
            <div class="text-center pt-20 border-t dark:border-white/5 border-gray-100">
                <Link href="/" class="text-xs font-black uppercase tracking-[0.4em] dark:text-white text-gray-900 hover:text-[#FF9F1C] transition-colors">
                    ← Retour au Hub Principal
                </Link>
            </div>
        </main>
    </div>
</template>

<style>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800;900&display=swap');
.font-sans { font-family: 'Outfit', sans-serif; }
</style>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800;900&display=swap');
.font-sans { font-family: 'Outfit', sans-serif; }
</style>
