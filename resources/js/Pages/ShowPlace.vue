<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

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
    <Head title="Mission en cours" />
    <AuthenticatedLayout>
        <div class="space-y-8 lg:space-y-12">
            <!-- Header with Back Button -->
            <div class="flex items-center gap-6">
                <Link :href="route('places.index')" class="h-12 w-12 dark:bg-white/5 bg-gray-100 rounded-2xl flex items-center justify-center hover:bg-[#FF9F1C] hover:text-black transition-all group">
                    <span class="group-hover:-translate-x-1 transition-transform text-xl">←</span>
                </Link>
                <div>
                    <h1 class="text-3xl lg:text-5xl font-black tracking-tighter uppercase italic dark:text-white text-gray-900 leading-none">
                        Mission <span class="text-[#FF9F1C]">Tactique</span>
                    </h1>
                    <p class="text-[10px] font-black uppercase tracking-[0.3em] text-gray-500 mt-2">Secteur: Historique • ID-402</p>
                </div>
            </div>

            <div class="grid gap-8 lg:gap-12 lg:grid-cols-2 items-start">
                <!-- Enigma Terminal -->
                <div class="space-y-8 order-2 lg:order-1">
                    <div class="dark:bg-[#111113] bg-white p-8 lg:p-10 rounded-[2.5rem] shadow-2xl border dark:border-white/5 border-gray-100 relative overflow-hidden">
                        <div class="absolute top-0 right-0 p-6 opacity-10">
                            <span class="text-6xl lg:text-8xl">❓</span>
                        </div>
                        
                        <h3 class="text-xs font-black uppercase tracking-[0.4em] text-[#FF9F1C] mb-8 italic">Transmission Entrante</h3>
                        <p class="text-xl lg:text-2xl dark:text-gray-300 text-gray-700 leading-relaxed italic font-medium">
                            "Je suis l'ancien nom de ce royaume puissant, dont les amazones étaient la terreur des ennemis. Mon dernier roi fut Béhanzin. Qui suis-je ?"
                        </p>
                    </div>

                    <div v-if="!enigmaSolved" class="space-y-6">
                        <div class="relative">
                            <input 
                                v-model="answer"
                                type="text" 
                                class="w-full dark:bg-white/5 bg-gray-50 border-2 dark:border-white/5 border-gray-100 rounded-[2rem] py-6 px-8 text-xl lg:text-2xl focus:ring-2 focus:ring-[#FF9F1C] focus:border-transparent transition-all font-black uppercase tracking-widest dark:text-white text-gray-900" 
                                placeholder="Entrez le code..." 
                                @keyup.enter="checkAnswer"
                            />
                            <div class="absolute right-6 top-1/2 -translate-y-1/2 pointer-events-none opacity-20">
                                <span class="text-2xl">⌨️</span>
                            </div>
                        </div>
                        
                        <transition name="fade">
                            <p v-if="error" class="text-red-500 font-black uppercase text-[10px] tracking-[0.2em] px-6">
                                ❌ Erreur de décryptage. Tentative échouée.
                            </p>
                        </transition>

                        <button 
                            @click="checkAnswer"
                            class="w-full rounded-[2rem] bg-[#FF9F1C] py-6 text-xs font-black uppercase tracking-[0.4em] text-black shadow-xl shadow-[#FF9F1C]/20 transition-all hover:scale-[1.02] active:scale-95"
                        >
                            Soumettre la Réponse
                        </button>
                    </div>

                    <transition name="fade">
                        <div v-else class="dark:bg-[#4CAF50]/10 bg-[#4CAF50]/5 p-8 lg:p-10 rounded-[2.5rem] border-2 border-dashed border-[#4CAF50]/30 text-[#4CAF50] space-y-4">
                            <div class="flex items-center gap-4">
                                <span class="text-4xl">🏆</span>
                                <h4 class="text-2xl lg:text-3xl font-black uppercase italic tracking-tighter">Succès Déverrouillé !</h4>
                            </div>
                            <p class="text-lg font-bold">L'énigme a été résolue avec brio. +150 points XP ajoutés à votre profil.</p>
                        </div>
                    </transition>
                </div>

                <!-- Visual Evidence -->
                <div class="dark:bg-[#111113] bg-white rounded-[2.5rem] shadow-2xl overflow-hidden border dark:border-white/5 border-gray-100 order-1 lg:order-2">
                    <div class="relative">
                        <img src="https://images.unsplash.com/photo-1552664730-d307ca884978?q=80&w=1200" alt="Monument" class="w-full aspect-[4/3] lg:aspect-square object-cover" />
                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent"></div>
                        <div class="absolute bottom-6 left-6">
                            <span class="text-[10px] font-black uppercase tracking-[0.4em] text-white/60">Analyse Visuelle</span>
                            <h3 class="text-xl font-black text-white uppercase italic tracking-tighter">Royaume du Dahomey</h3>
                        </div>
                    </div>
                    
                    <div class="p-8 lg:p-10 space-y-6">
                        <div v-if="enigmaSolved" class="space-y-4 animate-fade-in">
                            <h3 class="text-xs font-black uppercase tracking-[0.4em] text-[#FF9F1C] italic">Archives Historiques</h3>
                            <p class="dark:text-gray-400 text-gray-600 font-medium leading-relaxed italic lg:text-lg">
                                Le royaume du Dahomey était un royaume africain situé dans le sud-ouest de l'actuel Bénin entre le XVIIe siècle et la fin du XIXe siècle. Il est célèbre pour ses guerrières d'élite, les Agodjié, véritables piliers de la puissance royale.
                            </p>
                        </div>
                        <div v-else class="text-center py-12 lg:py-20 flex flex-col items-center gap-4 opacity-40">
                            <span class="text-4xl">🔒</span>
                            <p class="text-[10px] font-black uppercase tracking-[0.3em] dark:text-white text-gray-900">Données Cryptées. Résolvez l'énigme pour y accéder.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active {
    transition: opacity 0.5s ease;
}
.fade-enter-from, .fade-leave-to {
    opacity: 0;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in {
    animation: fadeIn 0.8s ease-out forwards;
}
</style>

<style>
@keyframes bounce-short {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}
.animate-bounce-short {
    animation: bounce-short 0.5s ease-in-out;
}
</style>
