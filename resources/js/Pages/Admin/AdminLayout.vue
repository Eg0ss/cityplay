<script setup>
import { Link, router } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import { useConfirm } from 'primevue/useconfirm';
import { 
    LogOut, 
    AlertTriangle, 
    Moon, 
    Sun, 
    X, 
    Menu, 
    LayoutDashboard, 
    Building2, 
    MapPin, 
    ShieldCheck, 
    Globe 
} from 'lucide-vue-next';

const isMobileMenuOpen = ref(false);
const isDark = ref(true);

const toggleTheme = () => {
    isDark.value = !isDark.value;
    localStorage.setItem('cityplay-theme', isDark.value ? 'dark' : 'light');
    updateTheme();
};

const updateTheme = () => {
    if (isDark.value) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
};

const confirm = useConfirm();

const confirmLogout = () => {
    confirm.require({
        message: 'Êtes-vous sûr de vouloir vous déconnecter du terminal d\'administration ?',
        header: 'Se déconnecter',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Rester',
        acceptLabel: 'Quitter',
        rejectClass: 'p-button-secondary p-button-outlined text-gray-300 border-gray-600 hover:bg-gray-800 px-4 py-2 rounded-lg mr-2',
        acceptClass: 'p-button-danger bg-red-600 border-red-600 text-white hover:bg-red-500 px-4 py-2 rounded-lg',
        accept: () => {
            if (document.activeElement) {
                document.activeElement.blur();
            }
            document.body.focus();
            
            router.post(route('logout'));
        }
    });
};

onMounted(() => {
    const savedTheme = localStorage.getItem('cityplay-theme');
    if (savedTheme) {
        isDark.value = savedTheme === 'dark';
    }
    updateTheme();
});
</script>

<template>
    <Toast />
    <ConfirmDialog :autoFocus="false">
        <template #container="{ message, acceptCallback, rejectCallback }">
            <div class="bg-[#10101c] border-2 border-[#7751de] p-8 rounded-[2rem] shadow-[0_0_45px_rgba(119,81,222,0.45)] max-w-sm w-full mx-auto relative overflow-hidden animate-slide-up text-center">
                <div class="absolute -top-10 -left-10 w-24 h-24 bg-[#7751de]/10 rounded-full blur-2xl pointer-events-none"></div>
                
                <div class="mb-5 space-y-2">
                    <AlertTriangle :size="48" class="text-[#ffc628] mx-auto text-glow-yellow" />
                    <h3 class="text-lg font-black uppercase italic tracking-tighter text-[#ffc628] text-glow-yellow">
                        {{ message.header }}
                    </h3>
                </div>
                
                <p class="text-gray-400 text-sm font-medium leading-relaxed mb-8">
                    {{ message.message }}
                </p>
                
                <div class="flex gap-4 justify-center">
                    <button @click="rejectCallback" class="px-6 py-3.5 rounded-xl font-black uppercase text-[9px] tracking-widest bg-[#1c183a] border border-[#2a245c] text-white hover:text-[#87d74e] transition-colors">
                        {{ message.rejectLabel || 'Annuler' }}
                    </button>
                    <button @click="acceptCallback" class="btn-3d btn-3d-red px-6 py-3.5 rounded-xl font-black uppercase text-[9px] tracking-widest text-white shadow-[0_4px_0_#9e2318]">
                        {{ message.acceptLabel || 'Confirmer' }}
                    </button>
                </div>
            </div>
        </template>
    </ConfirmDialog>
    <div class="min-h-screen font-sans flex overflow-hidden transition-colors duration-300" 
         :class="isDark ? 'bg-[#0A0A0B] text-white' : 'bg-gray-50 text-gray-900'">
        
        <!-- Mobile Header -->
        <header class="lg:hidden fixed top-0 w-full z-[60] px-6 py-4 flex justify-between items-center border-b backdrop-blur-md"
                :class="isDark ? 'bg-[#0A0A0B]/80 border-white/5' : 'bg-white/80 border-gray-200'">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 flex items-center justify-center">
                    <img src="/images/cityplay.png" class="h-full w-full object-contain" alt="Logo" />
                </div>
                <div class="flex flex-col">
                    <span class="font-black uppercase italic text-sm tracking-tighter leading-none">Cityplay</span>
                    <span class="text-[8px] text-[#FF9F1C] font-bold tracking-widest uppercase opacity-80">Admin</span>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <button @click="toggleTheme" class="h-10 w-10 flex items-center justify-center rounded-xl dark:bg-white/5 bg-gray-100">
                    <Moon v-if="isDark" :size="20" />
                    <Sun v-else :size="20" />
                </button>
                <button @click="isMobileMenuOpen = !isMobileMenuOpen" 
                    class="h-10 w-10 flex items-center justify-center rounded-xl dark:bg-white/5 bg-gray-100 text-white">
                    <X v-if="isMobileMenuOpen" :size="24" />
                    <Menu v-else :size="24" />
                </button>
            </div>
        </header>

        <!-- Sidebar -->
        <aside :class="[
                    isDark ? 'bg-[#111113] border-white/5' : 'bg-white border-gray-200',
                    isMobileMenuOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
                ]"
               class="w-72 border-r flex flex-col fixed h-full z-[70] transition-transform duration-500 ease-in-out lg:z-20 shadow-2xl lg:shadow-none">
            
            <div class="px-8 py-10">
                <Link href="/" class="flex items-center gap-4 group">
                    <div class="h-16 w-16 flex items-center justify-center">
                        <img src="/images/cityplay.png" class="h-full w-full object-contain" alt="Logo" />
                    </div>
                    <div class="flex flex-col">
                        <span class="text-2xl font-black uppercase italic tracking-tighter leading-none dark:text-white text-gray-900 group-hover:text-[#FF9F1C] transition-colors">Cityplay</span>
                        <span class="text-[9px] font-black uppercase tracking-[0.3em] text-[#FF9F1C] mt-1">Control Center</span>
                    </div>
                </Link>
            </div>

            <nav class="flex-1 px-6 py-8 space-y-3">
                <Link :href="route('admin.dashboard')" 
                    @click="isMobileMenuOpen = false"
                    :class="route().current('admin.dashboard') ? 'bg-[#FF9F1C] text-black shadow-lg' : (isDark ? 'text-gray-400 hover:bg-white/5 hover:text-white' : 'text-gray-600 hover:bg-gray-100')" 
                    class="flex items-center gap-4 px-5 py-4 rounded-2xl transition-all duration-300 group">
                    <span class="text-xl">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
                    </span>
                    <span class="font-black uppercase text-sm tracking-wider">Dashboard</span>
                </Link>

                <Link :href="route('admin.cities')" 
                    @click="isMobileMenuOpen = false"
                    :class="route().current('admin.cities*') || route().current('admin.cities.places*') ? 'bg-[#FF9F1C] text-black shadow-lg' : (isDark ? 'text-gray-400 hover:bg-white/5 hover:text-white' : 'text-gray-600 hover:bg-gray-100')" 
                    class="flex items-center gap-4 px-5 py-4 rounded-2xl transition-all duration-300 group">
                    <span class="text-xl">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22V10a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v12"/><path d="M18 22H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h13a2 2 0 0 1 2 2v4.5"/><path d="M22 22V15a2 2 0 0 0-2-2h-2"/><rect width="4" height="4" x="6" y="12" rx="1"/><rect width="4" height="4" x="10" y="12" rx="1"/></svg>
                    </span>
                    <span class="font-black uppercase text-sm tracking-wider">Cités</span>
                </Link>

                <Link :href="route('admin.places.all')" 
                    @click="isMobileMenuOpen = false"
                    :class="route().current('admin.places.all') ? 'bg-[#FF9F1C] text-black shadow-lg' : (isDark ? 'text-gray-400 hover:bg-white/5 hover:text-white' : 'text-gray-600 hover:bg-gray-100')" 
                    class="flex items-center gap-4 px-5 py-4 rounded-2xl transition-all duration-300 group">
                    <span class="text-xl">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                    </span>
                    <span class="font-black uppercase text-sm tracking-wider">Tous les lieux</span>
                </Link>

                <Link :href="route('admin.enigmas.all')" 
                    @click="isMobileMenuOpen = false"
                    :class="route().current('admin.enigmas.all') ? 'bg-[#FF9F1C] text-black shadow-lg' : (isDark ? 'text-gray-400 hover:bg-white/5 hover:text-white' : 'text-gray-600 hover:bg-gray-100')" 
                    class="flex items-center gap-4 px-5 py-4 rounded-2xl transition-all duration-300 group">
                    <span class="text-xl">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/><path d="m9 12 2 2 4-4"/></svg>
                    </span>
                    <span class="font-black uppercase text-sm tracking-wider">Toutes les énigmes</span>
                </Link>

                <div class="pt-8">
                    <p class="px-5 text-[10px] font-bold text-gray-500 uppercase tracking-[0.2em] mb-4">Système</p>
                    <button @click="toggleTheme" 
                        :class="isDark ? 'text-gray-400 hover:bg-white/5' : 'text-gray-600 hover:bg-gray-100'"
                        class="w-full flex items-center gap-4 px-5 py-4 rounded-2xl transition-all group mb-2">
                        <span class="text-xl">
                            <Moon v-if="isDark" :size="20" />
                            <Sun v-else :size="20" />
                        </span>
                        <span class="font-bold uppercase text-xs tracking-wider">{{ isDark ? 'Mode Sombre' : 'Mode Clair' }}</span>
                    </button>
                    <Link href="/" class="flex items-center gap-4 px-5 py-4 rounded-2xl transition-all group"
                        :class="isDark ? 'text-gray-400 hover:bg-white/5' : 'text-gray-600 hover:bg-gray-100'">
                        <span class="text-xl">
                            <Globe :size="20" />
                        </span>
                        <span class="font-bold uppercase text-xs tracking-wider">Site Public</span>
                    </Link>
                </div>
            </nav>

            <div class="p-8 border-t bg-opacity-50" :class="isDark ? 'border-white/5 bg-[#0D0D0F]' : 'border-gray-100 bg-gray-50'">
                <div class="flex items-center gap-3 mb-6">
                    <div class="h-10 w-10 rounded-full bg-gray-200 border flex items-center justify-center font-bold text-xs text-[#FF9F1C]">
                        AD
                    </div>
                    <div class="overflow-hidden">
                        <p class="text-xs font-black uppercase truncate">{{ $page.props.auth.user.name }}</p>
                        <p class="text-[10px] text-gray-500 font-bold uppercase tracking-tighter">Administrateur</p>
                    </div>
                </div>
                <button @click="confirmLogout" class="w-full py-3 rounded-xl border text-[10px] font-black uppercase tracking-widest transition-all"
                    :class="isDark ? 'border-white/5 text-gray-500 hover:text-red-500' : 'border-gray-200 text-gray-400 hover:text-red-600'">
                    Quitter
                </button>
            </div>
        </aside>

        <!-- Overlay for mobile -->
        <div v-if="isMobileMenuOpen" 
             @click="isMobileMenuOpen = false"
             class="fixed inset-0 bg-black/60 backdrop-blur-sm z-30 lg:hidden"></div>

        <!-- Main Content -->
        <main class="lg:ml-72 flex-1 relative h-screen overflow-y-auto pt-20 lg:pt-0">

            <!-- Decorative Backgrounds (Visible only in Dark Mode) -->
            <div v-if="isDark" class="fixed top-0 right-0 w-[500px] h-[500px] bg-[#FF9F1C]/5 blur-[120px] -z-10 rounded-full"></div>
            
            <div class="p-6 lg:p-12 max-w-7xl mx-auto">
                <slot />
            </div>
        </main>
    </div>
</template>

<style>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800;900&display=swap');

html.dark {
    background-color: #0A0A0B;
}

::-webkit-scrollbar {
    width: 6px;
}

::-webkit-scrollbar-track {
    background: transparent;
}

::-webkit-scrollbar-thumb {
    background: #FF9F1C;
    border-radius: 10px;
}
</style>
