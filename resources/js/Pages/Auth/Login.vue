<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: { type: Boolean },
    status: { type: String },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Connexion - Cityplay Bénin" />

    <div class="min-h-screen bg-[#FDFCFB] font-sans text-[#1A1A1A] flex flex-col md:flex-row">
        <!-- Left Side: Branding/Image -->
        <div class="hidden md:flex md:w-1/2 bg-[#1A1A1A] relative overflow-hidden p-20 flex-col justify-between">
            <div class="absolute inset-0 z-0 opacity-40">
                <img src="/images/hero.png" alt="Aventure" class="h-full w-full object-cover" />
                <div class="absolute inset-0 bg-gradient-to-br from-[#FF9F1C]/40 to-black/80"></div>
            </div>
            
            <Link href="/" class="relative z-10 flex items-center gap-2">
                <img src="/favicon.ico" alt="Logo" class="h-10 w-auto brightness-0 invert" />
                <span class="text-3xl font-bold tracking-tight text-white">Cityplay</span>
            </Link>

            <div class="relative z-10">
                <h1 class="text-6xl font-black text-white leading-tight">Bon retour <br />parmi nous !</h1>
                <p class="mt-6 text-xl text-gray-300 max-w-md">Continuez votre quête et débloquez les secrets du patrimoine béninois.</p>
            </div>

            <div class="relative z-10 text-gray-500 text-sm italic">
                "L'histoire est un trésor que l'on découvre pas à pas."
            </div>
        </div>

        <!-- Right Side: Login Form -->
        <div class="flex-1 flex items-center justify-center p-8 md:p-20 relative">
            <!-- Back to Home Button -->
            <Link href="/" class="absolute top-8 right-8 flex items-center gap-2 text-sm font-bold text-gray-400 hover:text-[#FF9F1C] transition-colors">
                <span>←</span>
                <span>Retour à l'accueil</span>
            </Link>

            <div class="w-full max-w-md space-y-12">
                <div class="md:hidden flex justify-center mb-12">
                    <Link href="/" class="flex items-center gap-2">
                        <img src="/favicon.ico" alt="Logo" class="h-10 w-auto" />
                        <span class="text-3xl font-bold tracking-tight">Cityplay</span>
                    </Link>
                </div>

                <div class="space-y-4">
                    <h2 class="text-4xl font-black">Connexion</h2>
                    <p class="text-gray-500">Ravis de vous revoir sur la plateforme.</p>
                </div>

                <div v-if="status" class="bg-green-50 p-4 rounded-xl text-green-600 text-sm font-medium">
                    {{ status }}
                </div>

                <form @submit.prevent="submit" class="space-y-6">
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
                        <div class="flex justify-between">
                            <label class="block text-xs font-bold uppercase tracking-widest text-gray-400">Mot de passe</label>
                            <Link v-if="canResetPassword" :href="route('password.request')" class="text-xs font-bold text-[#FF9F1C] hover:underline">Oublié ?</Link>
                        </div>
                        <input 
                            v-model="form.password"
                            type="password" 
                            required
                            class="w-full border-gray-100 bg-gray-50 rounded-2xl py-4 px-6 focus:ring-2 focus:ring-[#FF9F1C] border-2 transition-all"
                            placeholder="••••••••"
                        />
                        <div v-if="form.errors.password" class="text-red-500 text-xs mt-1">{{ form.errors.password }}</div>
                    </div>

                    <div class="flex items-center">
                        <input v-model="form.remember" type="checkbox" class="rounded border-gray-300 text-[#FF9F1C] focus:ring-[#FF9F1C]" />
                        <span class="ms-2 text-sm text-gray-500 font-medium">Se souvenir de moi</span>
                    </div>

                    <button 
                        type="submit" 
                        :disabled="form.processing"
                        class="w-full rounded-2xl bg-[#1A1A1A] py-5 text-lg font-bold text-white shadow-xl transition-all hover:bg-black hover:scale-[1.02] active:scale-95 disabled:opacity-50"
                    >
                        Se connecter
                    </button>
                </form>

                <div class="text-center pt-8 border-t border-gray-100">
                    <p class="text-gray-500">Pas encore de compte ? 
                        <Link :href="route('register')" class="text-[#FF9F1C] font-bold hover:underline">Créer un compte</Link>
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
