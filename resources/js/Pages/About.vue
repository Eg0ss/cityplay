<script setup>
import PageTemplate from './PageTemplate.vue';
</script>

<template>
    <PageTemplate title="À PROPOS DE NOUS" subtitle="Découvrez la mission de Cityplay et notre passion pour le patrimoine culturel du Bénin.">
        <div class="grid gap-20 lg:grid-cols-2 items-center">
            <div class="space-y-8">
                <h2 class="text-5xl font-black uppercase italic tracking-tighter leading-none">Notre Mission</h2>
                <p class="text-xl text-gray-500 font-medium leading-relaxed">
                    Cityplay est né de la volonté de valoriser le patrimoine culturel et historique du Bénin à travers le jeu. Nous croyons que l'apprentissage et l'exploration doivent être des expériences immersives et ludiques.
                </p>
                <div class="space-y-4">
                    <div v-for="i in 3" :key="i" class="flex items-start gap-4 p-6 bg-gray-50 rounded-3xl border border-gray-100">
                        <div class="text-[#FF9F1C] text-2xl">✓</div>
                        <div>
                            <h4 class="font-bold text-lg">Engagement Historique</h4>
                            <p class="text-gray-500 text-sm">Chaque énigme est vérifiée par des experts locaux.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="relative">
                <img src="/images/hero_light.png" class="rounded-[4rem] shadow-2xl" alt="About Image" />
                <div class="absolute -bottom-10 -right-10 bg-[#FF9F1C] p-12 rounded-[3rem] shadow-xl text-white hidden md:block">
                    <div class="text-5xl font-black italic tracking-tighter">100%</div>
                    <div class="text-xs font-bold uppercase tracking-widest mt-2">Béninois</div>
                </div>
            </div>
        </div>
    </PageTemplate>
</template>