<script setup>
import PageTemplate from './PageTemplate.vue';
import { Link } from '@inertiajs/vue3';

const steps = [
    { title: "Choisissez un lieu", desc: "Explorez la carte et sélectionnez un monument ou un endroit emblématique du Bénin.", icon: "📍" },
    { title: "Résolvez l'énigme", desc: "Utilisez les indices textuels et visuels pour deviner l'histoire ou le nom caché.", icon: "🧩" },
    { title: "Gagnez des points", desc: "Accumulez des points pour chaque bonne réponse et grimpez dans le classement mondial.", icon: "🏆" },
];
</script>

<template>
    <PageTemplate title="COMMENT JOUER ?" subtitle="Suivez ces trois étapes simples pour devenir un expert de l'exploration béninoise.">
        <div class="grid gap-12 md:grid-cols-3">
            <div v-for="(step, i) in steps" :key="i" class="bg-white p-12 rounded-[3rem] border border-gray-100 shadow-sm hover:shadow-xl transition-all group">
                <div class="h-20 w-20 bg-gray-50 rounded-[2rem] flex items-center justify-center text-4xl mb-8 group-hover:scale-110 transition-transform duration-500">
                    {{ step.icon }}
                </div>
                <h3 class="text-3xl font-black uppercase italic tracking-tighter mb-4">{{ step.title }}</h3>
                <p class="text-lg text-gray-500 font-medium leading-relaxed">{{ step.desc }}</p>
            </div>
        </div>

        <div class="mt-24 bg-[#1A1A1A] p-16 rounded-[4rem] text-center space-y-8">
            <h2 class="text-4xl md:text-5xl font-black text-white uppercase italic tracking-tighter">Prêt pour l'aventure ?</h2>
            <p class="text-xl text-gray-400 max-w-2xl mx-auto font-medium">Rejoignez des milliers de joueurs et découvrez le Bénin comme vous ne l'avez jamais vu.</p>
            <div class="pt-4">
                <Link :href="route('register')" class="inline-block bg-[#FF9F1C] text-white px-12 py-5 rounded-2xl font-black uppercase tracking-widest text-sm shadow-xl shadow-orange-500/20 hover:scale-105 transition-all">
                    Commencer Maintenant
                </Link>
            </div>
        </div>
    </PageTemplate>
</template>
