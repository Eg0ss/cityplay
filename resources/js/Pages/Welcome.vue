<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});
</script>

<template>
    <Head title="CityPlay - gaming" />

    <div class="min-h-screen bg-[#FDFCFB] font-sans text-[#1A1A1A] selection:bg-[#FF9F1C] selection:text-white">
        <!-- Header -->
        <header class="fixed top-0 z-50 w-full bg-white/80 backdrop-blur-md border-b border-gray-100">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
                <div class="flex items-center gap-2">
                    <img src="/favicon.ico" alt="Logo Cityplay" class="h-10 w-auto" />
                    <span class="text-2xl font-bold tracking-tight text-[#1A1A1A]">Cityplay</span>
                </div>

                <nav class="hidden items-center gap-8 md:flex">
                    <Link :href="route('how-to-play')" class="text-sm font-medium hover:text-[#FF9F1C] transition-colors">Comment jouer ?</Link>
                    <Link :href="route('explore')" class="text-sm font-medium hover:text-[#FF9F1C] transition-colors">Explorer les Lieux</Link>
                    <Link :href="route('leaderboard')" class="text-sm font-medium hover:text-[#FF9F1C] transition-colors">Classement (Leaderboard)</Link>
                    <Link :href="route('blog')" class="text-sm font-medium hover:text-[#FF9F1C] transition-colors">Blog des Explorateurs</Link>
                    <Link :href="route('about')" class="text-sm font-medium hover:text-[#FF9F1C] transition-colors">À Propos</Link>
                    <Link :href="route('contact')" class="text-sm font-medium hover:text-[#FF9F1C] transition-colors">Contact</Link>
                </nav>

                <div class="flex items-center gap-4">
                    <template v-if="canLogin">
                        <template v-if="$page.props.auth.user">
                            <Link v-if="$page.props.auth.user.is_admin" :href="route('admin.dashboard')" class="text-sm font-semibold text-[#FF9F1C] hover:underline">Admin</Link>
                            <Link :href="route('dashboard')" class="text-sm font-semibold text-[#1A1A1A] hover:text-[#FF9F1C]">Tableau de Bord</Link>
                        </template>
                        <template v-else>
                            <Link :href="route('login')" class="text-sm font-semibold text-[#1A1A1A] hover:text-[#FF9F1C]">Connexion</Link>
                            <Link v-if="canRegister" :href="route('register')" class="rounded-full bg-[#FF9F1C] px-6 py-2 text-sm font-semibold text-white shadow-lg shadow-orange-200 transition-all hover:bg-[#e68a00] hover:scale-105 active:scale-95">S'inscrire</Link>
                        </template>
                    </template>
                </div>
            </div>
        </header>

        <!-- Hero Section -->
        <section class="relative pt-24 pb-32 overflow-hidden">
            <div class="absolute inset-0 z-0">
                <img src="/images/hero.png" alt="Fond d'accueil" class="h-full w-full object-cover opacity-90 brightness-75" />
                <div class="absolute inset-0 bg-gradient-to-r from-black/40 to-transparent"></div>
            </div>

            <div class="relative z-10 mx-auto max-w-7xl px-6 pt-20">
                <div class="max-w-3xl">
                    <div class="inline-block bg-white px-8 py-4 mb-8">
                        <h1 class="text-7xl md:text-9xl font-black uppercase tracking-tighter text-[#1A1A1A]">
                            AVENTURE
                        </h1>
                    </div>
                    <div class="space-y-4">
                        <h2 class="text-3xl md:text-5xl font-bold text-white leading-tight">
                            Découvrez le Bénin dans son entièreté, <br />
                            Voyages d' <span class="text-[#FF9F1C]">Aventure</span>
                        </h2>
                        <p class="max-w-xl text-lg text-gray-200">
                            Vivez les réalités béninoises comme jamais auparavant. Du sommet de l'Atacora aux reflets de Ganvié, nous préparons l'aventure suprême pour les cœurs de guerriers.
                        </p>
                        <div class="flex flex-wrap gap-4 pt-6">
                            <Link :href="route('explore')" class="rounded-full bg-[#FF9F1C] px-8 py-4 text-lg font-bold text-white shadow-xl transition-all hover:scale-105 hover:bg-[#e68a00]">
                                Commencer votre découverte
                            </Link>
                            <button class="rounded-full bg-white/20 px-8 py-4 text-lg font-bold text-white backdrop-blur-md transition-all hover:bg-white/30">
                                Voir la vidéo
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hero Stats Box -->
            <div class="absolute bottom-0 right-0 z-10 hidden lg:block p-12">
                <div class="grid grid-cols-2 gap-6 bg-white/90 backdrop-blur-lg p-8 rounded-2xl shadow-2xl">
                    <div class="text-center p-4 border-r border-gray-100">
                        <div class="text-3xl font-bold text-[#FF9F1C]">3554+</div>
                        <div class="text-sm font-medium text-gray-500 uppercase tracking-widest">Voyages</div>
                    </div>
                    <div class="text-center p-4">
                        <div class="text-3xl font-bold text-[#FF9F1C]">280</div>
                        <div class="text-sm font-medium text-gray-500 uppercase tracking-widest">Guides</div>
                    </div>
                    <div class="text-center p-4 border-r border-gray-100 border-t">
                        <div class="text-3xl font-bold text-[#FF9F1C]">120</div>
                        <div class="text-sm font-medium text-gray-500 uppercase tracking-widest">Récompenses</div>
                    </div>
                    <div class="text-center p-4 border-t">
                        <div class="text-3xl font-bold text-[#FF9F1C]">390</div>
                        <div class="text-sm font-medium text-gray-500 uppercase tracking-widest">Avis</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Experience Section -->
        <section class="py-32 px-6">
            <div class="mx-auto max-w-7xl">
                <div class="text-center mb-20">
                    <span class="text-[#FF9F1C] font-bold uppercase tracking-[0.3em] text-sm">Notre Expérience</span>
                    <h2 class="mt-4 text-4xl md:text-5xl font-black text-[#1A1A1A] leading-tight">
                        12 ans d'expérience dans les <br />
                        circuits du Bénin
                    </h2>
                </div>

                <div class="grid gap-8 md:grid-cols-4">
                    <!-- Hiking Card -->
                    <div class="group relative overflow-hidden rounded-3xl bg-white shadow-xl transition-all hover:-translate-y-2">
                        <div class="aspect-[4/5] overflow-hidden">
                            <img src="/images/image_1.png" alt="La Porte du Non-Retour" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110" />
                        </div>
                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent"></div>
                        <div class="absolute bottom-0 p-6">
                            <h3 class="text-xl font-bold text-white">La Porte du Non-Retour</h3>
                            <p class="mt-2 text-sm text-gray-300">La Porte du Non-Retour à Ouidah, monument historique commémorant la déportation des esclaves.</p>
                            <Link :href="route('places.show', { id: 1 })" class="mt-4 text-[#FF9F1C] font-bold text-sm uppercase tracking-widest hover:underline inline-block">Explorer →</Link>
                        </div>
                    </div>

                    <!-- Water Card -->
                    <div class="group relative overflow-hidden rounded-3xl bg-white shadow-xl transition-all hover:-translate-y-2">
                        <div class="aspect-[4/5] overflow-hidden">
                            <img src="/images/image_2.png" alt="Les chutes d'eau" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110" />
                        </div>
                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent"></div>
                        <div class="absolute bottom-0 p-6">
                            <h3 class="text-xl font-bold text-white">Les chutes d'eau</h3>
                            <p class="mt-2 text-sm text-gray-300">Une chute d'eau naturelle nichée dans une végétation tropicale dense au Bénin.</p>
                            <Link :href="route('places.show', { id: 1 })" class="mt-4 text-[#FF9F1C] font-bold text-sm uppercase tracking-widest hover:underline inline-block">Explorer →</Link>
                        </div>
                    </div>

                    <!-- Climbing Card -->
                    <div class="group relative overflow-hidden rounded-3xl bg-white shadow-xl transition-all hover:-translate-y-2">
                        <div class="aspect-[4/5] overflow-hidden">
                            <img src="/images/image_3.png" alt="Statue d'Agodjié" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110 brightness-50" />
                        </div>
                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent"></div>
                        <div class="absolute bottom-0 p-6">
                            <h3 class="text-xl font-bold text-white">Statue d'Agodjié</h3>
                            <p class="mt-2 text-sm text-gray-300">L'imposante statue de l'Agodjié à Cotonou, hommage aux célèbres guerrières du Dahomey.</p>
                            <Link :href="route('places.show', { id: 1 })" class="mt-4 text-[#FF9F1C] font-bold text-sm uppercase tracking-widest hover:underline inline-block">Explorer →</Link>
                        </div>
                    </div>

                    <!-- Parachute Card -->
                    <div class="group relative overflow-hidden rounded-3xl bg-white shadow-xl transition-all hover:-translate-y-2">
                        <div class="aspect-[4/5] overflow-hidden">
                            <img src="/images/image_4.png" alt="Statue équestre de Bio Guéra" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110 brightness-75" />
                        </div>
                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent"></div>
                        <div class="absolute bottom-0 p-6">
                            <h3 class="text-xl font-bold text-white">Statue équestre de Bio Guéra</h3>
                            <p class="mt-2 text-sm text-gray-300">La statue équestre de Bio Guéra, héros national de la résistance contre la colonisation.</p>
                            <Link :href="route('places.show', { id: 1 })" class="mt-4 text-[#FF9F1C] font-bold text-sm uppercase tracking-widest hover:underline inline-block">Explorer →</Link>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Services Section -->
        <section class="bg-gray-50 py-32 px-6">
            <div class="mx-auto max-w-7xl">
                <div class="flex flex-col md:flex-row justify-between items-end mb-20 gap-8">
                    <div class="max-w-2xl text-left">
                        <span class="text-[#FF9F1C] font-bold uppercase tracking-[0.3em] text-sm">Services</span>
                        <h2 class="mt-4 text-5xl font-black text-[#1A1A1A]">Nous offrons le meilleur jeu pour découvrir le Bénin</h2>
                        <p class="mt-6 text-gray-500 text-lg">
                            Nous nous occupons de tout pour que vous puissiez vous concentrer sur l'aventure. De la planification du voyage à l'expertise locale, nous sommes là pour vous.
                        </p>
                    </div>
                    <div class="flex gap-4">
                        <button class="h-14 w-14 rounded-full border border-gray-200 flex items-center justify-center hover:bg-[#FF9F1C] hover:text-white transition-all">←</button>
                        <button class="h-14 w-14 rounded-full bg-[#FF9F1C] text-white flex items-center justify-center hover:bg-[#e68a00] transition-all">→</button>
                    </div>
                </div>

                <div class="grid gap-8 md:grid-cols-4">
                    <div class="bg-white p-10 rounded-3xl shadow-sm hover:shadow-xl transition-all text-center">
                        <div class="mx-auto mb-6 h-16 w-16 bg-orange-50 rounded-2xl flex items-center justify-center">
                            <span class="text-3xl">☁️</span>
                        </div>
                        <h4 class="text-xl font-bold">Météo Prévisionnelle</h4>
                        <p class="mt-4 text-gray-500">Une planification précise basée sur les modèles climatiques locaux.</p>
                    </div>
                    <div class="bg-white p-10 rounded-3xl shadow-sm hover:shadow-xl transition-all text-center">
                        <div class="mx-auto mb-6 h-16 w-16 bg-orange-50 rounded-2xl flex items-center justify-center">
                            <span class="text-3xl">✈️</span>
                        </div>
                        <h4 class="text-xl font-bold">Meilleur Vol</h4>
                        <p class="mt-4 text-gray-500">Des itinéraires optimisés et un confort de voyage premium.</p>
                    </div>
                    <div class="bg-[#FF9F1C] p-10 rounded-3xl shadow-xl transition-all text-center text-white">
                        <div class="mx-auto mb-6 h-16 w-16 bg-white/20 rounded-2xl flex items-center justify-center">
                            <span class="text-3xl">📅</span>
                        </div>
                        <h4 class="text-xl font-bold">Événements Locaux</h4>
                        <p class="mt-4 text-white/80">Immergez-vous dans des expériences culturelles authentiques.</p>
                    </div>
                    <div class="bg-white p-10 rounded-3xl shadow-sm hover:shadow-xl transition-all text-center">
                        <div class="mx-auto mb-6 h-16 w-16 bg-orange-50 rounded-2xl flex items-center justify-center">
                            <span class="text-3xl">🛠️</span>
                        </div>
                        <h4 class="text-xl font-bold">Circuit Personnalisé</h4>
                        <p class="mt-4 text-gray-500">Des aventures sur mesure conçues spécialement pour vous.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="bg-[#1A1A1A] py-20 px-6 text-white">
            <div class="mx-auto max-w-7xl grid gap-12 md:grid-cols-4">
                <div class="space-y-6">
                    <div class="flex items-center gap-2">
                        <img src="/images/cityplay.png" alt="Logo Cityplay" class="h-10 w-auto brightness-0 invert" />
                        <span class="text-2xl font-bold tracking-tight">Cityplay</span>
                    </div>
                    <p class="text-gray-400">Rendre le monde accessible à tous ceux qui osent explorer. Votre aventure commence ici.</p>
                </div>
                <div>
                    <h5 class="text-lg font-bold mb-6">Entreprise</h5>
                    <ul class="space-y-4 text-gray-400">
                        <li><a href="#" class="hover:text-[#FF9F1C]">À Propos</a></li>
                        <li><a href="#" class="hover:text-[#FF9F1C]">Carrières</a></li>
                        <li><a href="#" class="hover:text-[#FF9F1C]">Presse</a></li>
                    </ul>
                </div>
                <div>
                    <h5 class="text-lg font-bold mb-6">Assistance</h5>
                    <ul class="space-y-4 text-gray-400">
                        <li><a href="#" class="hover:text-[#FF9F1C]">Contactez-nous</a></li>
                        <li><a href="#" class="hover:text-[#FF9F1C]">Centre d'aide</a></li>
                        <li><a href="#" class="hover:text-[#FF9F1C]">Sécurité</a></li>
                    </ul>
                </div>
                <div>
                    <h5 class="text-lg font-bold mb-6">Newsletter</h5>
                    <p class="text-gray-400 mb-6">Recevez les dernières nouvelles d'aventure dans votre boîte mail.</p>
                    <div class="flex">
                        <input type="email" placeholder="Adresse e-mail" class="w-full bg-white/10 border-none rounded-l-xl px-4 focus:ring-1 focus:ring-[#FF9F1C]" />
                        <button class="bg-[#FF9F1C] px-4 rounded-r-xl font-bold hover:bg-[#e68a00]">Go</button>
                    </div>
                </div>
            </div>
            <div class="mx-auto max-w-7xl mt-20 pt-8 border-t border-white/10 text-center text-gray-500 text-sm">
                &copy; 2026 Touringo Adventure Travels. Tous droits réservés.
            </div>
        </footer>
    </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800;900&display=swap');

.font-sans {
    font-family: 'Outfit', sans-serif;
}
</style>
