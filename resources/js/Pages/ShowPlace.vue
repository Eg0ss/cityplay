<script setup>
import { ref } from 'vue';
import PageTemplate from './PageTemplate.vue';

const props = defineProps({
    id: String
});

const enigmaSolved = ref(false);
const answer = ref('');
const error = ref(false);

const checkAnswer = () => {
    // For demo purposes, the answer is "Dahomey" or similar
    if (answer.value.toLowerCase().includes('dahomey') || answer.value.toLowerCase().includes('béhanzin')) {
        enigmaSolved.value = true;
        error.value = false;
    } else {
        error.value = true;
    }
};
</script>

<template>
    <PageTemplate title="Énigme du Lieu" subtitle="Résolvez le mystère pour découvrir l'histoire secrète de ce monument.">
        <div class="grid gap-12 lg:grid-cols-2 items-start">
            <div class="space-y-8">
                <div class="bg-white p-8 rounded-3xl shadow-xl border border-gray-100">
                    <h3 class="text-2xl font-bold mb-6 text-[#FF9F1C]">L'Énigme</h3>
                    <p class="text-xl text-gray-700 leading-relaxed italic">
                        "Je suis l'ancien nom de ce royaume puissant, dont les amazones étaient la terreur des ennemis. Mon dernier roi fut Béhanzin. Qui suis-je ?"
                    </p>
                </div>

                <div v-if="!enigmaSolved" class="space-y-4">
                    <input 
                        v-model="answer"
                        type="text" 
                        class="w-full border-gray-100 bg-gray-50 rounded-2xl py-6 px-8 text-xl focus:ring-2 focus:ring-[#FF9F1C] border-2" 
                        placeholder="Votre réponse ici..." 
                        @keyup.enter="checkAnswer"
                    />
                    <p v-if="error" class="text-red-500 font-bold px-4">Ce n'est pas la bonne réponse. Réessayez !</p>
                    <button 
                        @click="checkAnswer"
                        class="w-full rounded-2xl bg-[#1A1A1A] py-6 text-xl font-bold text-white shadow-xl transition-all hover:bg-black"
                    >
                        Vérifier la réponse
                    </button>
                </div>

                <div v-else class="bg-green-50 p-8 rounded-3xl border border-green-200 text-green-800 space-y-4 animate-bounce-short">
                    <h4 class="text-2xl font-bold">🎉 Félicitations !</h4>
                    <p class="text-lg">Vous avez résolu l'énigme. Vous avez gagné 50 points !</p>
                </div>
            </div>

            <div class="bg-white rounded-3xl shadow-xl overflow-hidden border border-gray-100">
                <img src="/images/image_3.png" alt="Monument" class="w-full aspect-square object-cover" />
                <div v-if="enigmaSolved" class="p-8 space-y-4">
                    <h3 class="text-2xl font-bold">L'Histoire Secrète</h3>
                    <p class="text-gray-500">
                        Le royaume du Dahomey était un royaume africain situé dans le sud-ouest de l'actuel Bénin entre le XVIIe siècle et la fin du XIXe siècle. Il est célèbre pour ses guerrières, les Agodjié (Amazones).
                    </p>
                </div>
                <div v-else class="p-8 text-center text-gray-400">
                    <p>Résolvez l'énigme pour débloquer les détails historiques.</p>
                </div>
            </div>
        </div>
    </PageTemplate>
</template>

<style>
@keyframes bounce-short {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}
.animate-bounce-short {
    animation: bounce-short 0.5s ease-in-out;
}
</style>
