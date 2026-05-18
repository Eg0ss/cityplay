<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
</script>

<template>
    <AuthenticatedLayout title="Gaming Dashboard">
        <div class="min-h-screen bg-gray-900 text-white font-sans py-12 px-4 sm:px-6 lg:px-8 transition-colors duration-500">
            <div class="max-w-7xl mx-auto">
                <div class="text-center mb-16">
                    <h1 class="text-5xl font-extrabold tracking-tight text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-purple-500 mb-4 animate-pulse">
                        Bienvenue dans CityPlay
                    </h1>
                    <p class="text-xl text-gray-400">Votre aventure commence ici. Prêt à découvrir le monde ?</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Nouvelle Partie -->
                    <div class="bg-gray-800 rounded-2xl shadow-2xl p-8 border border-gray-700 hover:border-blue-500 transition-all duration-300 transform hover:-translate-y-2 relative overflow-hidden group">
                        <div class="absolute inset-0 bg-gradient-to-br from-blue-600/20 to-purple-600/20 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                        <div class="relative z-10 text-center">
                            <div class="w-20 h-20 mx-auto bg-blue-500 rounded-full flex items-center justify-center mb-6 shadow-[0_0_30px_rgba(59,130,246,0.5)]">
                                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <h2 class="text-3xl font-bold mb-4">Nouvelle Partie</h2>
                            <p class="text-gray-400 mb-8">Lancez-vous dans une nouvelle quête. Solo ou en mode multijoueur, le choix vous appartient.</p>
                            <Link :href="route('game.setup')" class="inline-block px-8 py-4 bg-gradient-to-r from-blue-500 to-purple-600 text-white font-bold rounded-full shadow-lg hover:shadow-[0_0_20px_rgba(139,92,246,0.6)] transform hover:scale-105 transition-all duration-300 w-full text-lg">
                                DÉMARRER
                            </Link>
                        </div>
                    </div>

                    <!-- Statistiques -->
                    <div class="bg-gray-800 rounded-2xl shadow-2xl p-8 border border-gray-700">
                        <h2 class="text-2xl font-bold mb-6 flex items-center gap-3">
                            <svg class="w-6 h-6 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                            Vos Statistiques
                        </h2>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-gray-900/50 p-6 rounded-xl border border-gray-700 text-center">
                                <span class="block text-4xl font-black text-blue-400 mb-2">0</span>
                                <span class="text-sm text-gray-400 uppercase tracking-wider">Parties Jouées</span>
                            </div>
                            <div class="bg-gray-900/50 p-6 rounded-xl border border-gray-700 text-center">
                                <span class="block text-4xl font-black text-purple-400 mb-2">0</span>
                                <span class="text-sm text-gray-400 uppercase tracking-wider">Énigmes Résolues</span>
                            </div>
                            <div class="col-span-2 bg-gray-900/50 p-6 rounded-xl border border-gray-700 text-center mt-2">
                                <span class="block text-5xl font-black text-yellow-400 mb-2 drop-shadow-[0_0_10px_rgba(250,204,21,0.8)]">0</span>
                                <span class="text-sm text-gray-400 uppercase tracking-wider">Points Totaux</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Animations et styles spécifiques au mode gaming */
::selection {
    background: rgba(139, 92, 246, 0.5);
    color: white;
}
</style>
