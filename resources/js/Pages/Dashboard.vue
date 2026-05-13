<script setup>
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Espace Partenaire" />

    <div class="min-h-screen bg-[#F8F9FA] font-sans text-[#1A1A1A]">
        <!-- Top Navbar -->
        <nav class="bg-white border-b border-gray-100 px-8 py-4 flex justify-between items-center sticky top-0 z-50">
            <Link href="/" class="flex items-center gap-2">
                <img src="/favicon.ico" alt="Logo" class="h-8 w-auto" />
                <span class="text-xl font-bold tracking-tight">Cityplay Partenaire</span>
            </Link>
            
            <div class="flex items-center gap-6">
                <span class="text-sm font-medium text-gray-500">Bienvenue, {{ $page.props.auth.user.name }}</span>
                <Link :href="route('logout')" method="post" as="button" class="text-xs font-bold uppercase tracking-widest text-red-500 hover:text-red-700">Déconnexion</Link>
            </div>
        </nav>

    
        <div class="max-w-7xl mx-auto py-12 px-6">
            <div class="grid gap-12 lg:grid-cols-4">
                <!-- Sidebar -->
                <aside class="lg:col-span-1 space-y-4">
                    <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100">
                        <nav class="space-y-2">
                            <Link :href="route('dashboard')" class="block px-4 py-3 bg-[#FF9F1C]/10 text-[#FF9F1C] rounded-xl font-bold">Tableau de bord</Link>
                            <a href="#" class="block px-4 py-3 text-gray-500 hover:bg-gray-50 rounded-xl font-medium">Mes Statistiques</a>
                            <a href="#" class="block px-4 py-3 text-gray-500 hover:bg-gray-50 rounded-xl font-medium">Rapports d'activité</a>
                            <a href="#" class="block px-4 py-3 text-gray-500 hover:bg-gray-50 rounded-xl font-medium">Paramètres</a>
                        </nav>
                    </div>
                    
                    <div class="bg-[#1A1A1A] p-6 rounded-3xl text-white">
                        <h4 class="font-bold mb-2">Besoin d'aide ?</h4>
                        <p class="text-xs text-gray-400 mb-4">Contactez le support technique pour toute assistance.</p>
                        <Link :href="route('contact')" class="text-xs font-bold text-[#FF9F1C] uppercase tracking-widest">Contacter →</Link>
                    </div>
                </aside>

                <!-- Main Content -->
                <main class="lg:col-span-3 space-y-8">
                    <div class="bg-white p-12 rounded-3xl shadow-sm border border-gray-100">
                        <h1 class="text-3xl font-black mb-4">Espace Partenaire</h1>
                        <p class="text-gray-500 leading-relaxed">
                            Cet espace est réservé aux partenaires officiels de Cityplay Bénin. Suivez les performances de vos lieux et accédez à vos outils de gestion.
                        </p>
                    </div>

                    <div class="grid gap-6 md:grid-cols-3">
                        <div class="bg-white p-8 rounded-3xl border border-gray-100 shadow-sm">
                            <span class="text-3xl">📊</span>
                            <div class="mt-4">
                                <div class="text-xs font-bold text-gray-400 uppercase tracking-widest">Vues totales</div>
                                <div class="text-3xl font-black">1,284</div>
                            </div>
                        </div>
                        <div class="bg-white p-8 rounded-3xl border border-gray-100 shadow-sm">
                            <span class="text-3xl">🧩</span>
                            <div class="mt-4">
                                <div class="text-xs font-bold text-gray-400 uppercase tracking-widest">Enigmes jouées</div>
                                <div class="text-3xl font-black">452</div>
                            </div>
                        </div>
                        <div class="bg-white p-8 rounded-3xl border border-gray-100 shadow-sm">
                            <span class="text-3xl">⭐</span>
                            <div class="mt-4">
                                <div class="text-xs font-bold text-gray-400 uppercase tracking-widest">Note moyenne</div>
                                <div class="text-3xl font-black">4.8/5</div>
                            </div>
                        </div>
                    </div>
                </main>
            </div>
        </div>
    </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800;900&display=swap');
.font-sans { font-family: 'Outfit', sans-serif; }
</style>
