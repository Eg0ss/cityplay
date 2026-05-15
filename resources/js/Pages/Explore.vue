<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const places = [
    { id: 1, name: "Ouidah", city: "Porte du Non-Retour", image: "https://images.unsplash.com/photo-1590001158193-7fef71a8f94f?q=80&w=800", level: "Débutant", category: "Histoire" },
    { id: 2, name: "Cotonou", city: "Place de l'Amazone", image: "https://images.unsplash.com/photo-1578301978693-85fa9c0320b9?q=80&w=800", level: "Intermédiaire", category: "Monument" },
    { id: 3, name: "Ganvié", city: "Cité Lacustre", image: "https://images.unsplash.com/photo-1544005313-94ddf0286df2?q=80&w=800", level: "Expert", category: "Nature" },
    { id: 4, name: "Abomey", city: "Palais Royaux", image: "https://images.unsplash.com/photo-1552664730-d307ca884978?q=80&w=800", level: "Intermédiaire", category: "Histoire" },
    { id: 5, name: "Parakou", city: "Place Bio Guéra", image: "https://images.unsplash.com/photo-1527853787696-f7be74f2e3ee?q=80&w=800", level: "Débutant", category: "Monument" },
    { id: 6, name: "Porto-Novo", city: "Grand Mosque", image: "https://images.unsplash.com/photo-1523050335192-ce67a276d112?q=80&w=800", level: "Expert", category: "Religieux" },
];
</script>

<template>
    <Head title="Explorez le Bénin" />
    <AuthenticatedLayout>
        <div class="space-y-12">
            <!-- Header Section -->
            <div class="max-w-3xl">
                <h1 class="text-4xl lg:text-7xl font-black tracking-tighter uppercase italic leading-none dark:text-white text-gray-900 mb-6">
                    Missions <span class="text-[#FF9F1C]">Actives</span>
                </h1>
                <p class="text-lg font-medium dark:text-gray-400 text-gray-600 leading-relaxed">
                    Sélectionnez votre prochaine destination. Chaque lieu cache des énigmes uniques qui mettront à l'épreuve votre sens de l'observation.
                </p>
            </div>

            <!-- Destinations Grid -->
            <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                <div v-for="place in places" :key="place.id" 
                    class="group dark:bg-[#111113] bg-white rounded-[2.5rem] overflow-hidden border dark:border-white/5 border-gray-100 shadow-sm hover:shadow-2xl transition-all duration-500 hover:-translate-y-2">
                    
                    <!-- Image Container -->
                    <div class="relative aspect-[4/3] overflow-hidden">
                        <img :src="place.image" :alt="place.name" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110 grayscale-[50%] group-hover:grayscale-0" />
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>
                        
                        <!-- Difficulty Badge -->
                        <div class="absolute top-6 right-6 dark:bg-black/60 bg-white/90 backdrop-blur-md px-4 py-2 rounded-xl text-[8px] font-black uppercase tracking-widest text-[#FF9F1C] border dark:border-white/10 border-gray-100">
                            {{ place.level }}
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="p-8 space-y-6">
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] font-black uppercase tracking-[0.2em] text-[#FF9F1C]">{{ place.category }}</span>
                            <div class="h-1 w-1 dark:bg-white/20 bg-gray-300 rounded-full"></div>
                            <span class="text-[10px] font-black uppercase tracking-[0.2em] dark:text-gray-500 text-gray-400">{{ place.city }}</span>
                        </div>
                        
                        <h3 class="text-3xl font-black uppercase italic tracking-tighter dark:text-white text-gray-900 group-hover:text-[#FF9F1C] transition-colors">
                            {{ place.name }}
                        </h3>

                        <div class="pt-4">
                            <Link :href="route('places.show', { id: place.id })" 
                                class="flex items-center justify-center w-full dark:bg-white bg-gray-900 dark:text-black text-white py-5 rounded-2xl font-black uppercase text-[10px] tracking-[0.3em] shadow-xl hover:scale-[1.02] active:scale-95 transition-all">
                                Déployer la Quête
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
