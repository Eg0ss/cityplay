<script setup>
import { Link } from '@inertiajs/vue3';
import PageTemplate from './PageTemplate.vue';

const places = [
    { id: 1, name: "La Porte du Non-Retour", city: "Ouidah", image: "/images/image_1.png", type: "Histoire" },
    { id: 2, name: "Les chutes d'eau", city: "Atacora", image: "/images/image_2.png", type: "Nature" },
    { id: 3, name: "Statue d'Agodjié", city: "Cotonou", image: "/images/image_3.png", type: "Culture" },
    { id: 4, name: "Statue de Bio Guéra", city: "Parakou", image: "/images/image_4.png", type: "Histoire" },
    { id: 5, name: "Ganvié", city: "Sô-Ava", image: "/images/hero.png", type: "Culture" },
    { id: 6, name: "Palais d'Abomey", city: "Abomey", image: "/images/hiking.png", type: "Histoire" },
];
</script>

<template>
    <PageTemplate title="Explorer les Lieux" subtitle="Choisissez un lieu emblématique du Bénin et commencez à résoudre ses mystères.">
        <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
            <div v-for="place in places" :key="place.id" class="group relative overflow-hidden rounded-3xl bg-white shadow-lg transition-all hover:shadow-2xl">
                <div class="aspect-video overflow-hidden">
                    <img :src="place.image" :alt="place.name" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110" />
                </div>
                <div class="p-6">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold uppercase tracking-widest text-[#FF9F1C]">{{ place.type }}</span>
                        <span class="text-xs font-medium text-gray-400">{{ place.city }}</span>
                    </div>
                    <h3 class="text-xl font-bold mb-4">{{ place.name }}</h3>
                    <Link :href="route('places.show', { id: place.id })" class="inline-block w-full rounded-xl bg-gray-50 py-3 text-center text-sm font-bold transition-all hover:bg-[#FF9F1C] hover:text-white">
                        Jouer l'énigme
                    </Link>
                </div>
            </div>
        </div>
    </PageTemplate>
</template>
