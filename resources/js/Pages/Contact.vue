<script setup>
import PageTemplate from './PageTemplate.vue';
</script>

<template>
    <PageTemplate title="Contactez-nous" subtitle="Une question ? Une idée d'énigme ? N'hésitez pas à nous écrire.">
        <div class="max-w-2xl mx-auto bg-white p-12 rounded-3xl shadow-xl border border-gray-100">
            <form class="space-y-6">
                <div>
                    <label class="block text-sm font-bold uppercase tracking-widest text-gray-400 mb-2">Nom Complet</label>
                    <input type="text" class="w-full border-gray-100 bg-gray-50 rounded-xl focus:ring-2 focus:ring-[#FF9F1C] focus:border-transparent py-4" placeholder="Votre nom" />
                </div>
                <div>
                    <label class="block text-sm font-bold uppercase tracking-widest text-gray-400 mb-2">Email</label>
                    <input type="email" class="w-full border-gray-100 bg-gray-50 rounded-xl focus:ring-2 focus:ring-[#FF9F1C] focus:border-transparent py-4" placeholder="votre@email.com" />
                </div>
                <div>
                    <label class="block text-sm font-bold uppercase tracking-widest text-gray-400 mb-2">Message</label>
                    <textarea class="w-full border-gray-100 bg-gray-50 rounded-xl focus:ring-2 focus:ring-[#FF9F1C] focus:border-transparent py-4 h-40" placeholder="Comment pouvons-nous vous aider ?"></textarea>
                </div>
                <button type="submit" class="w-full rounded-xl bg-[#FF9F1C] py-4 text-lg font-bold text-white shadow-xl transition-all hover:bg-[#e68a00] hover:scale-[1.02]">
                    Envoyer le message
                </button>
            </form>
        </div>
    </PageTemplate>
</template>