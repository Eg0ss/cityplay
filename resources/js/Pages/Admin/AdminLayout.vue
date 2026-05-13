<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen bg-gray-50 font-sans flex">
        <!-- Sidebar -->
        <aside class="w-64 bg-[#1A1A1A] text-white flex flex-col fixed h-full">
            <div class="p-8 flex items-center gap-2">
                <img src="/favicon.ico" alt="Logo" class="h-8 w-auto brightness-0 invert" />
                <span class="text-xl font-bold tracking-tight">Cityplay Admin</span>
            </div>

            <nav class="flex-1 px-4 py-8 space-y-2">
                <Link :href="route('admin.dashboard')" :class="{'bg-[#FF9F1C]': route().current('admin.dashboard')}" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-white/10 transition-all">
                    <span>📊</span>
                    <span class="font-bold">Dashboard</span>
                </Link>
                <Link :href="route('admin.places')" :class="{'bg-[#FF9F1C]': route().current('admin.places*')}" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-white/10 transition-all">
                    <span>📍</span>
                    <span class="font-bold">Gestion des Lieux</span>
                </Link>
                <Link href="/" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-white/10 transition-all text-gray-400 mt-auto">
                    <span>🏠</span>
                    <span>Voir le site</span>
                </Link>
            </nav>

        
            <div class="p-8 border-t border-white/10">
                <Link :href="route('logout')" method="post" as="button" class="w-full text-left text-sm text-gray-400 hover:text-white">Déconnexion</Link>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="ml-64 flex-1 p-12 overflow-y-auto">
            <slot />
        </main>
    </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800;900&display=swap');
.font-sans { font-family: 'Outfit', sans-serif; }
</style>
