<script setup>
import { ref, onMounted } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { useConfirm } from 'primevue/useconfirm';
import ConfirmDialog from 'primevue/confirmdialog';
import { userStatsStore } from '../store.js';
import AudioWidget from '@/Components/AudioWidget.vue';
import { 
    LogOut, Menu, X, AlertTriangle, Rocket,
    LayoutDashboard, History, Trophy, Zap, Play
} from 'lucide-vue-next';

const isMobileMenuOpen = ref(false);
const confirm = useConfirm();
const page = usePage();

// Fermer le menu mobile à chaque navigation
router.on('start', () => { isMobileMenuOpen.value = false; });

const confirmLogout = () => {
    confirm.require({
        message: 'La partie en cours sera perdue si vous quittez.',
        header: 'Quitter la session',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Rester',
        acceptLabel: 'Quitter',
        rejectClass: 'p-button-secondary p-button-outlined text-gray-300 border-gray-600 hover:bg-gray-800 px-4 py-2 rounded-lg mr-2',
        acceptClass: 'p-button-danger bg-red-600 border-red-600 text-white hover:bg-red-500 px-4 py-2 rounded-lg',
        accept: () => {
            if (document.activeElement) document.activeElement.blur();
            router.post(route('logout'));
        }
    });
};

const isActive = (routeName) => {
    try { return route().current(routeName); } catch { return false; }
};

onMounted(() => {
    document.documentElement.classList.add('dark');
    const pageUser = page.props.auth?.user;
    if (pageUser) {
        userStatsStore.initialize(pageUser.total_points, pageUser.level_name);
    }
});
</script>

<template>
    <div class="min-h-screen font-sans bg-[#171235] text-white">

        <ConfirmDialog :autoFocus="false">
            <template #container="{ message, acceptCallback, rejectCallback }">
                <div class="bg-[#10101c] border-2 border-[#7751de] p-6 rounded-[1.5rem] shadow-[0_0_45px_rgba(119,81,222,0.45)] max-w-sm w-full mx-auto relative overflow-hidden animate-pop-in text-center">
                    <AlertTriangle :size="40" class="text-[#ffc628] mx-auto mb-3 text-glow-yellow" />
                    <h3 class="text-base font-black uppercase italic tracking-tighter text-[#ffc628] mb-2">{{ message.header }}</h3>
                    <p class="text-gray-400 text-sm font-medium leading-relaxed mb-6">{{ message.message }}</p>
                    <div class="flex gap-3 justify-center">
                        <button @click="rejectCallback" class="px-5 py-3 rounded-xl font-black uppercase text-[9px] tracking-widest bg-[#1c183a] border border-[#2a245c] text-white hover:text-[#87d74e] transition-colors">
                            {{ message.rejectLabel || 'Annuler' }}
                        </button>
                        <button @click="acceptCallback" class="btn-3d btn-3d-red px-5 py-3 text-[9px] tracking-widest shadow-[0_4px_0_#9e2318]">
                            {{ message.acceptLabel || 'Confirmer' }}
                        </button>
                    </div>
                </div>
            </template>
        </ConfirmDialog>

        <!-- TOP NAVIGATION -->
        <nav class="fixed top-0 z-50 w-full border-b bg-[#10101c]/95 border-[#2a245c] backdrop-blur-md">
            <div class="mx-auto max-w-7xl px-4 sm:px-6">
                <div class="flex h-14 sm:h-16 items-center justify-between">

                    <div class="flex items-center gap-4">
                        <Link href="/dashboard" class="flex items-center gap-2 shrink-0">
                            <img src="/images/cityplay.png" class="h-8 w-8 object-contain" alt="Logo" />
                            <span class="text-base font-black tracking-tighter uppercase italic hidden sm:block text-white">City<span class="text-[#87d74e] text-glow-green">play</span></span>
                        </Link>

                        <!-- Stats desktop -->
                        <div class="hidden lg:flex items-center gap-3 bg-[#1c183a] border border-[#2a245c] px-3 py-1.5 rounded-xl">
                            <div class="flex flex-col leading-none">
                                <span class="text-[7px] font-black uppercase text-gray-500 tracking-widest">Rang</span>
                                <span class="text-[11px] font-black text-[#87d74e] text-glow-green">{{ userStatsStore.levelName }}</span>
                            </div>
                            <div class="h-4 w-px bg-[#2a245c]"></div>
                            <div class="flex flex-col leading-none">
                                <span class="text-[7px] font-black uppercase text-gray-500 tracking-widest">XP</span>
                                <span class="text-[11px] font-black text-white">{{ userStatsStore.points }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <!-- Nav desktop -->
                        <div class="hidden lg:flex items-center gap-1 border-r border-[#2a245c] pr-3 mr-1">
                            <Link :href="route('dashboard')"
                                :class="isActive('dashboard') || isActive('game.dashboard') ? 'text-[#87d74e] bg-[#87d74e]/10' : 'text-gray-400 hover:text-white hover:bg-white/5'"
                                class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-[9px] font-black uppercase tracking-widest transition-all">
                                <LayoutDashboard :size="12" /> Dashboard
                            </Link>
                            <Link :href="route('game.progression')"
                                :class="isActive('game.progression') ? 'text-[#87d74e] bg-[#87d74e]/10' : 'text-gray-400 hover:text-white hover:bg-white/5'"
                                class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-[9px] font-black uppercase tracking-widest transition-all">
                                <History :size="12" /> Progression
                            </Link>
                            <Link :href="route('leaderboard')"
                                :class="isActive('leaderboard') ? 'text-[#87d74e] bg-[#87d74e]/10' : 'text-gray-400 hover:text-white hover:bg-white/5'"
                                class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-[9px] font-black uppercase tracking-widest transition-all">
                                <Trophy :size="12" /> Classement
                            </Link>
                        </div>

                        <!-- Avatar desktop -->
                        <div class="hidden sm:flex items-center gap-2">
                            <div class="h-8 w-8 rounded-full bg-[#1c183a] border-2 border-[#87d74e] shadow-[0_0_8px_rgba(135,215,78,0.3)] flex items-center justify-center font-black text-[10px] text-[#87d74e]">
                                {{ $page.props.auth.user.name.substring(0, 2).toUpperCase() }}
                            </div>
                        </div>

                        <!-- Déconnexion desktop -->
                        <button @click="confirmLogout" class="hidden sm:flex items-center gap-1.5 btn-3d btn-3d-red px-3 py-2 text-[9px] tracking-widest shadow-[0_3px_0_#9e2318]">
                            <LogOut :size="12" /> Quitter
                        </button>

                        <!-- Hamburger mobile -->
                        <button @click="isMobileMenuOpen = !isMobileMenuOpen"
                            class="sm:hidden p-2 rounded-xl bg-[#1c183a] border border-[#2a245c] text-white active:scale-90 transition-transform">
                            <X v-if="isMobileMenuOpen" :size="20" />
                            <Menu v-else :size="20" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mobile Menu -->
            <transition name="fade">
                <div v-if="isMobileMenuOpen" class="sm:hidden border-t bg-[#10101c] border-[#2a245c] px-4 py-5 space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="p-3 rounded-xl bg-[#1c183a] border border-[#2a245c] flex items-center gap-2">
                            <Zap :size="14" class="text-[#87d74e] shrink-0" />
                            <div>
                                <p class="text-[7px] font-black text-gray-500 uppercase tracking-widest">Niveau</p>
                                <p class="text-xs font-black text-[#87d74e]">{{ userStatsStore.levelName }}</p>
                            </div>
                        </div>
                        <div class="p-3 rounded-xl bg-[#1c183a] border border-[#2a245c] flex items-center gap-2">
                            <Trophy :size="14" class="text-[#ffc628] shrink-0" />
                            <div>
                                <p class="text-[7px] font-black text-gray-500 uppercase tracking-widest">Score</p>
                                <p class="text-xs font-black text-white">{{ userStatsStore.points }} XP</p>
                            </div>
                        </div>
                    </div>
                    <nav class="flex flex-col gap-1">
                        <Link :href="route('dashboard')" class="flex items-center gap-3 text-sm font-black uppercase tracking-widest py-3 px-3 rounded-xl hover:bg-[#1c183a] text-white transition-all">
                            <LayoutDashboard :size="16" /> Dashboard
                        </Link>
                        <Link :href="route('game.progression')" class="flex items-center gap-3 text-sm font-black uppercase tracking-widest py-3 px-3 rounded-xl hover:bg-[#1c183a] text-white transition-all">
                            <History :size="16" /> Ma Progression
                        </Link>
                        <Link :href="route('leaderboard')" class="flex items-center gap-3 text-sm font-black uppercase tracking-widest py-3 px-3 rounded-xl hover:bg-[#1c183a] text-white transition-all">
                            <Trophy :size="16" /> Classement
                        </Link>
                        <button @click="confirmLogout" class="flex items-center gap-3 text-sm font-black uppercase tracking-widest py-3 px-3 rounded-xl text-red-400 text-left w-full hover:bg-red-500/10 transition-all">
                            <LogOut :size="16" /> Déconnexion
                        </button>
                    </nav>
                </div>
            </transition>
        </nav>

        <!-- CONTENT — pt adapté à la hauteur navbar, pb pour bottom nav mobile -->
        <div class="pt-14 sm:pt-16 pb-20 sm:pb-0">
            <main class="mx-auto max-w-7xl px-3 sm:px-6 lg:px-10 py-4 sm:py-6 lg:py-8 relative">
                <div class="absolute top-0 left-1/4 w-72 h-72 bg-[#87d74e]/4 rounded-full blur-[100px] pointer-events-none -z-10"></div>
                <div class="absolute bottom-0 right-1/4 w-72 h-72 bg-purple-600/4 rounded-full blur-[100px] pointer-events-none -z-10"></div>

                <div v-if="$slots.header" class="mb-5 sm:mb-8">
                    <slot name="header" />
                </div>
                <slot />
            </main>
        </div>

        <AudioWidget />

        <!-- BOTTOM NAV MOBILE -->
        <nav class="sm:hidden fixed bottom-0 left-0 right-0 z-50 bg-[#10101c]/97 border-t border-[#2a245c] backdrop-blur-md"
             style="padding-bottom: env(safe-area-inset-bottom, 0px)">
            <div class="flex items-stretch h-14">
                <Link :href="route('dashboard')"
                    :class="isActive('game.dashboard') || isActive('dashboard') ? 'text-[#87d74e]' : 'text-gray-500'"
                    class="flex-1 flex flex-col items-center justify-center gap-0.5 active:bg-white/5 transition-colors">
                    <LayoutDashboard :size="19" />
                    <span class="text-[7px] font-black uppercase tracking-widest">Home</span>
                </Link>

                <!-- Bouton JOUER central surélevé -->
                <div class="flex-1 flex flex-col items-center justify-center relative">
                    <Link :href="route('game.setup')"
                        class="absolute -top-5 w-14 h-14 rounded-2xl btn-3d btn-3d-green shadow-[0_5px_0_#5d9933] flex items-center justify-center">
                        <Play :size="22" fill="currentColor" />
                    </Link>
                    <span class="text-[7px] font-black uppercase tracking-widest text-[#87d74e] mt-7">Jouer</span>
                </div>

                <Link :href="route('game.progression')"
                    :class="isActive('game.progression') ? 'text-[#87d74e]' : 'text-gray-500'"
                    class="flex-1 flex flex-col items-center justify-center gap-0.5 active:bg-white/5 transition-colors">
                    <History :size="19" />
                    <span class="text-[7px] font-black uppercase tracking-widest">Perfs</span>
                </Link>
            </div>
        </nav>
    </div>
</template>

<style>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800;900&display=swap');
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to       { opacity: 0; }
</style>
