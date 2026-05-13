<script setup>
import PageTemplate from './PageTemplate.vue';
</script>

<template>
    <PageTemplate title="Comment jouer ?" subtitle="Apprenez les règles du jeu et commencez votre exploration du Bénin dès aujourd'hui.">
        <div class="grid gap-12 md:grid-cols-3">
            <div class="space-y-6">
                <div class="h-16 w-16 bg-[#FF9F1C] text-white rounded-2xl flex items-center justify-center text-3xl font-black shadow-lg shadow-orange-100">1</div>
                <h3 class="text-2xl font-bold">Choisissez un Lieu</h3>
                <p class="text-gray-500">Parcourez la carte ou la liste des lieux emblématiques du Bénin. Sélectionnez l'endroit que vous souhaitez découvrir.</p>
            </div>
            <div class="space-y-6">
                <div class="h-16 w-16 bg-[#FF9F1C] text-white rounded-2xl flex items-center justify-center text-3xl font-black shadow-lg shadow-orange-100">2</div>
                <h3 class="text-2xl font-bold">Résolvez l'Énigme</h3>
                <p class="text-gray-500">Chaque lieu est protégé par une énigme créée par nos administrateurs. Utilisez votre logique et vos connaissances pour trouver la réponse.</p>
            </div>
            <div class="space-y-6">
                <div class="h-16 w-16 bg-[#FF9F1C] text-white rounded-2xl flex items-center justify-center text-3xl font-black shadow-lg shadow-orange-100">3</div>
                <h3 class="text-2xl font-bold">Gagnez des Points</h3>
                <p class="text-gray-500">Une fois l'énigme résolue, vous débloquez l'histoire secrète du lieu et gagnez des points pour grimper dans le classement national.</p>
            </div>
        </div>

        <div class="mt-20 bg-[#1A1A1A] p-12 rounded-3xl text-white">
            <h3 class="text-3xl font-bold mb-6 text-center">Les types d'énigmes</h3>
            <div class="grid gap-8 md:grid-cols-2">
                <div class="border border-white/10 p-8 rounded-2xl hover:bg-white/5 transition-all">
                    <h4 class="text-xl font-bold text-[#FF9F1C] mb-4">Énigmes Historiques</h4>
                    <p class="text-gray-400">Basées sur les faits réels, les rois du Dahomey et les moments clés de notre histoire.</p>
                </div>
                <div class="border border-white/10 p-8 rounded-2xl hover:bg-white/5 transition-all">
                    <h4 class="text-xl font-bold text-[#FF9F1C] mb-4">Énigmes Géographiques</h4>
                    <p class="text-gray-400">Testez votre sens de l'orientation et vos connaissances sur les paysages béninois.</p>
                </div>
            </div>
        </div>
    </PageTemplate>
</template>
