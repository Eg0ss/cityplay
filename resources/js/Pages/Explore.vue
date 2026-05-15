<script setup>
import PageTemplate from './PageTemplate.vue';
import { Link } from '@inertiajs/vue3';

const places = [
    { id: 1, name: "Ouidah", city: "Porte du Non-Retour", image: "/images/image_1.png", level: "Débutant", category: "Histoire" },
    { id: 2, name: "Cotonou", city: "Place de l'Amazone", image: "/images/image_3.png", level: "Intermédiaire", category: "Monument" },
    { id: 3, name: "Ganvié", city: "Cité Lacustre", image: "/images/water.png", level: "Expert", category: "Nature" },
    { id: 4, name: "Abomey", city: "Palais Royaux", image: "/images/image_4.png", level: "Intermédiaire", category: "Histoire" },
    { id: 5, name: "Parakou", city: "Place Bio Guéra", image: "/images/hiking.png", level: "Débutant", category: "Monument" },
    { id: 6, name: "Porto-Novo", city: "Grand Mosque", image: "/images/image_1.png", level: "Expert", category: "Religieux" },
];
</script>

<template>
    <PageTemplate title="EXPLORER LE BÉNIN" subtitle="Choisissez une destination et commencez à résoudre des énigmes pour découvrir l'histoire secrète de chaque lieu.">
        <div class="grid gap-12 sm:grid-cols-2 lg:grid-cols-3">
            <div v-for="place in places" :key="place.id" class="group bg-white rounded-[2.5rem] overflow-hidden border border-gray-100 shadow-sm hover:shadow-2xl transition-all duration-500 hover:-translate-y-2">
                <div class="relative aspect-[4/3] overflow-hidden">
                    <img :src="place.image" :alt="place.name" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110" />
                    <div class="absolute top-6 right-6 bg-white/90 backdrop-blur-md px-4 py-2 rounded-full text-[10px] font-black uppercase tracking-widest text-[#FF9F1C]">
                        {{ place.level }}
                    </div>
                </div>
                <div class="p-8 space-y-4">
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] font-black uppercase tracking-widest text-gray-400">{{ place.category }}</span>
                        <div class="h-1 w-1 bg-gray-200 rounded-full"></div>
                        <span class="text-[10px] font-black uppercase tracking-widest text-gray-400">{{ place.city }}</span>
                    </div>
                    <h3 class="text-3xl font-black uppercase italic tracking-tighter">{{ place.name }}</h3>
                    <div class="pt-4">
                        <Link :href="route('places.show', { id: place.id })" class="inline-block w-full text-center bg-[#1A1A1A] text-white py-4 rounded-2xl font-bold uppercase text-xs tracking-widest hover:bg-black transition-all">
                            Jouer l'énigme
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </PageTemplate>
</template>
