<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Inscription - Cityplay Bénin" />

    <div class="min-h-screen bg-[#FDFCFB] font-sans text-[#1A1A1A] flex flex-col md:flex-row">
        <!-- Left Side: Branding/Image -->
        <div class="hidden md:flex md:w-1/2 bg-[#1A1A1A] relative overflow-hidden p-20 flex-col justify-between">
            <div class="absolute inset-0 z-0 opacity-40">
                <img src="/images/water.png" alt="Exploration" class="h-full w-full object-cover" />
                <div class="absolute inset-0 bg-gradient-to-br from-[#FF9F1C]/40 to-black/80"></div>
            </div>
            
            <Link href="/" class="relative z-10 flex items-center gap-2">
                <img src="/favicon.ico" alt="Logo" class="h-10 w-auto brightness-0 invert" />
                <span class="text-3xl font-bold tracking-tight text-white">Cityplay</span>
            </Link>

            <div class="relative z-10">
                <h1 class="text-6xl font-black text-white leading-tight">Devenez un <br />Explorateur.</h1>
                <p class="mt-6 text-xl text-gray-300 max-w-md">Créez votre compte et commencez à résoudre des énigmes pour découvrir les trésors du Bénin.</p>
            </div>

            <div class="relative z-10 text-gray-500 text-sm italic">
                "Chaque voyage commence par un premier pas."
            </div>
        </div>

        <!-- Right Side: Register Form -->
        <div class="flex-1 flex items-center justify-center p-8 md:p-20 overflow-y-auto relative">
            <!-- Back to Home Button -->
            <Link href="/" class="absolute top-8 right-8 flex items-center gap-2 text-sm font-bold text-gray-400 hover:text-[#FF9F1C] transition-colors">
                <span>←</span>
                <span>Retour à l'accueil</span>
            </Link>

            <div class="w-full max-w-md space-y-12 my-12">
                <div class="md:hidden flex justify-center mb-12">
                    <Link href="/" class="flex items-center gap-2">
                        <img src="/favicon.ico" alt="Logo" class="h-10 w-auto" />
                        <span class="text-3xl font-bold tracking-tight">Cityplay</span>
                    </Link>
                </div>

                <div class="space-y-4">
                    <h2 class="text-4xl font-black">Inscription</h2>
                    <p class="text-gray-500">Rejoignez la communauté des aventuriers.</p>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="space-y-2">
                        <label class="block text-xs font-bold uppercase tracking-widest text-gray-400">Nom Complet</label>
                        <input 
                            v-model="form.name"
                            type="text" 
                            required
                            autofocus
                            class="w-full border-gray-100 bg-gray-50 rounded-2xl py-4 px-6 focus:ring-2 focus:ring-[#FF9F1C] border-2 transition-all"
                            placeholder="Jean Dupont"
                        />
                        <div v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</div>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-xs font-bold uppercase tracking-widest text-gray-400">Adresse e-mail</label>
                        <input 
                            v-model="form.email"
                            type="email" 
                            required
                            class="w-full border-gray-100 bg-gray-50 rounded-2xl py-4 px-6 focus:ring-2 focus:ring-[#FF9F1C] border-2 transition-all"
                            placeholder="votre@email.com"
                        />
                        <div v-if="form.errors.email" class="text-red-500 text-xs mt-1">{{ form.errors.email }}</div>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-xs font-bold uppercase tracking-widest text-gray-400">Mot de passe</label>
                        <input 
                            v-model="form.password"
                            type="password" 
                            required
                            class="w-full border-gray-100 bg-gray-50 rounded-2xl py-4 px-6 focus:ring-2 focus:ring-[#FF9F1C] border-2 transition-all"
                            placeholder="••••••••"
                        />
                        <div v-if="form.errors.password" class="text-red-500 text-xs mt-1">{{ form.errors.password }}</div>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-xs font-bold uppercase tracking-widest text-gray-400">Confirmer le mot de passe</label>
                        <input 
                            v-model="form.password_confirmation"
                            type="password" 
                            required
                            class="w-full border-gray-100 bg-gray-50 rounded-2xl py-4 px-6 focus:ring-2 focus:ring-[#FF9F1C] border-2 transition-all"
                            placeholder="••••••••"
                        />
                        <div v-if="form.errors.password_confirmation" class="text-red-500 text-xs mt-1">{{ form.errors.password_confirmation }}</div>
                    </div>

                    <button 
                        type="submit" 
                        :disabled="form.processing"
                        class="w-full rounded-2xl bg-[#1A1A1A] py-5 text-lg font-bold text-white shadow-xl transition-all hover:bg-black hover:scale-[1.02] active:scale-95 disabled:opacity-50"
                    >
                        S'inscrire
                    </button>
                </form>

                <div class="text-center pt-8 border-t border-gray-100">
                    <p class="text-gray-500">Déjà inscrit ? 
                        <Link :href="route('login')" class="text-[#FF9F1C] font-bold hover:underline">Se connecter</Link>
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800;900&display=swap');
.font-sans { font-family: 'Outfit', sans-serif; }
</style>
