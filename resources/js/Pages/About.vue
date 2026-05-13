<script setup>
import PageTemplate from './PageTemplate.vue';
</script>

<template>
    <PageTemplate title="À Propos de Cityplay" subtitle="La mission de Cityplay est de faire découvrir le riche patrimoine du Bénin à travers le jeu et l'aventure.">
        <div class="prose prose-lg max-w-none text-gray-500 space-y-8">
            <p>
                Cityplay Bénin est une plateforme innovante qui transforme la découverte du pays en une aventure interactive. Nous croyons que l'histoire et la culture de notre nation, du Dahomey à nos jours, méritent d'être explorées de manière ludique et engageante.
            </p>
            <div class="grid gap-8 md:grid-cols-2 mt-12">
                <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100">
                    <h4 class="text-xl font-bold text-[#1A1A1A] mb-4">Notre Vision</h4>
                    <p>Devenir la référence numérique pour la promotion du tourisme local au Bénin en impliquant la jeunesse et les voyageurs.</p>
                </div>
                <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100">
                    <h4 class="text-xl font-bold text-[#1A1A1A] mb-4">L'Équipe</h4>
                    <p>Des passionnés d'histoire, de technologie et de voyages travaillant ensemble pour créer les meilleures énigmes pour vous.</p>
                </div>
            </div>
        </div>
    </PageTemplate>
</template>