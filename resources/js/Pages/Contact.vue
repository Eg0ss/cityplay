<script setup>
import PageTemplate from './PageTemplate.vue';
import { useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    message: '',
});

const submit = () => {
    // Logic for form submission
};
</script>

<template>
    <PageTemplate title="CONTACTEZ-NOUS" subtitle="Vous avez une question ou une suggestion d'énigme ? Notre équipe d'aventuriers est à votre écoute.">
        <div class="mx-auto max-w-4xl bg-white p-16 rounded-[4rem] border border-gray-100 shadow-sm">
            <form @submit.prevent="submit" class="space-y-12">
                <div class="grid gap-8 md:grid-cols-2">
                    <div class="space-y-4">
                        <label class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400">Votre Nom</label>
                        <input v-model="form.name" type="text" class="w-full border-gray-100 bg-gray-50 rounded-2xl py-5 px-8 font-bold focus:ring-2 focus:ring-[#FF9F1C] border-2 transition-all" placeholder="Ex: Koffi Benin" />
                    </div>
                    <div class="space-y-4">
                        <label class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400">Adresse E-mail</label>
                        <input v-model="form.email" type="email" class="w-full border-gray-100 bg-gray-50 rounded-2xl py-5 px-8 font-bold focus:ring-2 focus:ring-[#FF9F1C] border-2 transition-all" placeholder="koffi@exemple.com" />
                    </div>
                </div>
                <div class="space-y-4">
                    <label class="text-[10px] font-black uppercase tracking-[0.2em] text-gray-400">Message</label>
                    <textarea v-model="form.message" class="w-full border-gray-100 bg-gray-50 rounded-2xl py-5 px-8 font-bold focus:ring-2 focus:ring-[#FF9F1C] border-2 transition-all h-48" placeholder="Votre message ici..."></textarea>
                </div>
                <div class="pt-6">
                    <button type="submit" class="w-full bg-[#1A1A1A] text-white py-6 rounded-2xl font-black uppercase tracking-[0.2em] text-sm shadow-xl hover:bg-black transition-all hover:scale-[1.02]">
                        Envoyer le message
                    </button>
                </div>
            </form>
        </div>
    </PageTemplate>
</template>