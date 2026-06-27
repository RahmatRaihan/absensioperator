<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const sidebarOpen = ref(true);
const currentUrl = computed(() => usePage().url);
const activePeriode = computed(() => usePage().props.activePeriode);
const user = computed(() => usePage().props.auth?.user);

const showSplash = ref(false);
const splashExiting = ref(false);

onMounted(() => {
    // Tutup sidebar secara default jika diakses dari tablet / HP (< 1024px)
    if (typeof window !== 'undefined' && window.innerWidth < 1024) {
        sidebarOpen.value = false;
    }

    if (!sessionStorage.getItem('splash_shown')) {
        showSplash.value = true;
        setTimeout(() => {
            splashExiting.value = true;
            setTimeout(() => {
                showSplash.value = false;
                sessionStorage.setItem('splash_shown', 'true');
            }, 1000);
        }, 150);
    }
});

const handleLogout = () => {
    sessionStorage.removeItem('splash_shown');
};

const toast = ref(null);
const toastType = ref('success');

const showToast = (message, type = 'success') => {
    toast.value = message;
    toastType.value = type;
    setTimeout(() => {
        if (toast.value === message) {
            toast.value = null;
        }
    }, 4000);
};

// Watch for flash messages
watch(
    () => usePage().props.flash,
    (flash) => {
        if (flash?.success) {
            showToast(flash.success, 'success');
        } else if (flash?.error) {
            showToast(flash.error, 'error');
        }
    },
    { deep: true, immediate: true }
);

const navItems = [
    { name: 'Dashboard', href: '/', icon: 'dashboard' },
    { name: 'Operator', href: '/operator', icon: 'people' },
    { name: 'Entry Absensi', href: '/absensi/entry', icon: 'edit_calendar' },
    { name: 'Rekap Laporan', href: '/rekap', icon: 'summarize' },
    { name: 'Upload Bulk', href: '/absensi/upload', icon: 'upload_file' },
    { name: 'Periode', href: '/periode', icon: 'date_range' },
];

const isActive = (href) => {
    if (href === '/') return currentUrl.value === '/';
    return currentUrl.value.startsWith(href);
};

const toggleSidebar = () => {
    sidebarOpen.value = !sidebarOpen.value;
};
</script>

<template>
    <div class="min-h-screen bg-[#F4F6F9] flex">
        <!-- Login Screen Pull-up Overlay (Matches Split-screen Design) -->
        <div
            v-if="showSplash"
            :class="[
                'fixed inset-0 z-50 flex flex-col md:flex-row bg-[#F4F6F9] overflow-hidden transition-transform duration-1000 ease-in-out',
                splashExiting ? '-translate-y-full' : 'translate-y-0'
            ]"
        >
            <!-- Left Pane: Sidebar Dark Blue Accent (#0A1628) -->
            <div class="md:w-[42%] bg-[#0A1628] relative overflow-hidden flex flex-col justify-between p-8 md:p-12 text-white">
                <div class="absolute -top-40 -left-40 w-[450px] h-[450px] bg-primary-500/10 rounded-full blur-3xl"></div>
                <div class="absolute -bottom-40 -right-40 w-[450px] h-[450px] bg-primary-600/10 rounded-full blur-3xl"></div>

                <!-- Top Brand -->
                <div class="relative z-10 flex items-center gap-3">
                    <div class="w-9 h-9 bg-white/10 rounded-xl p-1.5 flex items-center justify-center backdrop-blur-md">
                        <img src="/logo.png?v=2" alt="Logo PT. BAI" class="w-full h-full object-contain" />
                    </div>
                    <div>
                        <h2 class="font-bold text-sm leading-tight tracking-wide text-white">PT. BAI</h2>
                        <p class="text-[10px] text-white/50 leading-none mt-0.5">Borneo Alumina Indonesia</p>
                    </div>
                </div>

                <!-- Center Welcome Message -->
                <div class="relative z-10 my-auto py-12 md:py-0">
                    <h1 class="text-3xl md:text-[38px] font-extrabold tracking-tight leading-tight text-white font-outfit">
                        Sistem Rekapitulasi <br class="hidden md:block"/> Absensi Operator
                    </h1>
                    <div class="w-12 h-1 bg-[#0060AF] rounded-full my-5"></div>
                    <p class="text-sm text-white/60 leading-relaxed max-w-sm">
                        Platform internal untuk kelola data absensi, periode kerja, dan laporan rekapitulasi shift operator PT Borneo Alumina Indonesia secara terpusat.
                    </p>
                </div>

                <!-- Footer copyright -->
                <div class="relative z-10 text-[11px] text-white/30 hidden md:block">
                    Absensi Operator v1.0 — © 2026 PT Borneo Alumina Indonesia
                </div>
            </div>

            <!-- Right Pane: Login Form Mockup -->
            <div class="flex-1 flex items-center justify-center p-6 md:p-12 relative overflow-hidden bg-[#F4F6F9]">
                <div class="absolute -top-40 -right-40 w-96 h-96 bg-primary-200/30 rounded-full blur-3xl"></div>
                
                <div class="w-full max-w-md z-10">
                    <!-- Mobile Logo Header -->
                    <div class="flex flex-col items-center mb-8 md:hidden">
                        <div class="w-16 h-16 bg-[#0A1628] rounded-2xl flex items-center justify-center p-3 mb-3 border border-white/10 shadow-lg">
                            <img src="/logo.png?v=2" alt="Logo PT. BAI" class="w-full h-full object-contain" />
                        </div>
                        <h2 class="text-xl font-bold text-gray-800">Absensi Operator</h2>
                        <p class="text-xs text-gray-500">PT Borneo Alumina Indonesia</p>
                    </div>

                    <!-- Mockup Login Card -->
                    <div class="bg-white/95 backdrop-blur-xl rounded-3xl shadow-xl shadow-gray-200/50 border border-white p-8 md:p-10 w-full">
                        <div class="mb-6">
                            <h2 class="text-xl font-bold text-gray-800">Selamat Datang</h2>
                            <p class="text-xs text-gray-400 mt-1 font-sans">Memuat dashboard admin...</p>
                        </div>

                        <div class="space-y-5 opacity-40">
                            <!-- Email field mockup -->
                            <div>
                                <div class="w-24 h-2.5 bg-gray-300 rounded mb-2"></div>
                                <div class="h-11 bg-gray-50 border border-gray-200 rounded-2xl"></div>
                            </div>

                            <!-- Password field mockup -->
                            <div>
                                <div class="w-20 h-2.5 bg-gray-300 rounded mb-2"></div>
                                <div class="h-11 bg-gray-50 border border-gray-200 rounded-2xl"></div>
                            </div>

                            <!-- Button mockup -->
                            <div class="h-12 bg-[#0060AF] rounded-2xl flex items-center justify-center">
                                <span class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Sidebar -->
        <aside
            :class="[
                'fixed inset-y-0 left-0 z-40 flex flex-col bg-sidebar transition-all duration-300 ease-in-out',
                sidebarOpen ? 'w-56 translate-x-0' : 'w-[72px] -translate-x-full lg:translate-x-0'
            ]"
        >
            <!-- Logo Area -->
            <div class="flex items-center h-16 px-4 border-b border-white/10">
                <div class="flex items-center gap-3 overflow-hidden">
                    <img src="/logo.png?v=2" alt="Logo PT. BAI" class="w-9 h-9 object-contain flex-shrink-0" />
                    <transition
                        enter-active-class="transition-opacity duration-200"
                        enter-from-class="opacity-0"
                        enter-to-class="opacity-100"
                        leave-active-class="transition-opacity duration-100"
                        leave-from-class="opacity-100"
                        leave-to-class="opacity-0"
                    >
                        <div v-if="sidebarOpen" class="whitespace-nowrap">
                            <h1 class="text-white font-bold text-sm leading-tight">Absensi</h1>
                            <p class="text-white/50 text-xs">PT Borneo Alumina Indonesia</p>
                        </div>
                    </transition>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 py-4 px-3 space-y-1 overflow-y-auto">
                <Link
                    v-for="item in navItems"
                    :key="item.name"
                    :href="item.href"
                    :class="[
                        'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-200 group',
                        isActive(item.href)
                            ? 'bg-sidebar-active text-white shadow-lg shadow-primary-500/25'
                            : 'text-white/60 hover:bg-sidebar-hover hover:text-white'
                    ]"
                >
                    <span class="material-symbols-outlined text-[20px] flex-shrink-0" :class="isActive(item.href) ? 'text-white' : 'text-white/50 group-hover:text-white'">
                        {{ item.icon }}
                    </span>
                    <transition
                        enter-active-class="transition-opacity duration-200"
                        enter-from-class="opacity-0"
                        enter-to-class="opacity-100"
                        leave-active-class="transition-opacity duration-100"
                        leave-from-class="opacity-100"
                        leave-to-class="opacity-0"
                    >
                        <span v-if="sidebarOpen" class="whitespace-nowrap">{{ item.name }}</span>
                    </transition>
                </Link>
            </nav>

            <!-- Footer -->
            <div class="p-3 border-t border-white/10">
                <button
                    @click="toggleSidebar"
                    class="flex items-center justify-center w-full px-3 py-2 rounded-lg text-white/50 hover:text-white hover:bg-sidebar-hover transition-colors"
                >
                    <span class="material-symbols-outlined text-[20px]" :class="sidebarOpen ? '' : 'rotate-180'">
                        chevron_left
                    </span>
                    <transition
                        enter-active-class="transition-opacity duration-200"
                        enter-from-class="opacity-0"
                        enter-to-class="opacity-100"
                        leave-active-class="transition-opacity duration-100"
                        leave-from-class="opacity-100"
                        leave-to-class="opacity-0"
                    >
                        <span v-if="sidebarOpen" class="ml-2 text-sm whitespace-nowrap">Tutup Sidebar</span>
                    </transition>
                </button>
            </div>
        </aside>

        <!-- Mobile Overlay -->
        <div
            v-if="sidebarOpen"
            @click="toggleSidebar"
            class="fixed inset-0 bg-black/50 z-30 lg:hidden"
        ></div>

        <!-- Main Content -->
        <div :class="['flex-1 flex flex-col transition-all duration-300 min-w-0', sidebarOpen ? 'lg:ml-56' : 'lg:ml-[72px]']">
            <!-- Top Bar -->
            <header class="sticky top-0 z-20 bg-white/80 backdrop-blur-lg border-b border-gray-200/60 h-16 flex items-center px-6 justify-between">
                <div class="flex items-center gap-3">
                    <button @click="toggleSidebar" class="lg:hidden p-2 rounded-lg hover:bg-gray-100 transition-colors">
                        <span class="material-symbols-outlined text-gray-600">menu</span>
                    </button>
                    <div>
                        <h2 class="text-gray-800 font-semibold text-lg">
                            <slot name="title">Dashboard</slot>
                        </h2>
                    </div>
                </div>
                <div class="flex items-center gap-6">
                    <div class="text-right hidden sm:block">
                        <p class="text-xs text-gray-400">Periode Aktif</p>
                        <p class="text-sm font-medium text-primary-600">
                            <slot name="periode">{{ activePeriode?.label || 'Tidak Ada Periode Aktif' }}</slot>
                        </p>
                    </div>

                    <div v-if="user" class="h-8 w-px bg-gray-200 hidden sm:block"></div>

                    <!-- User Profile & Logout -->
                    <div class="flex items-center gap-3" v-if="user">
                        <div class="text-right hidden md:block">
                            <p class="text-sm font-semibold text-gray-700 leading-none">{{ user.name }}</p>
                            <p class="text-xs text-gray-400 mt-1 leading-none">{{ user.email }}</p>
                        </div>
                        <div class="w-9 h-9 rounded-full bg-primary-50 text-primary-600 flex items-center justify-center font-bold text-sm border border-primary-100 shadow-sm">
                            {{ user.name ? user.name.charAt(0).toUpperCase() : 'A' }}
                        </div>
                        <Link
                            href="/logout"
                            method="post"
                            as="button"
                            @click="handleLogout"
                            class="p-2 text-gray-400 hover:text-red-500 rounded-lg hover:bg-red-50 transition-all flex items-center justify-center cursor-pointer active:scale-95"
                            title="Keluar"
                        >
                            <span class="material-symbols-outlined text-[20px] block">logout</span>
                        </Link>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 p-6 min-w-0">
                <slot />
            </main>

            <!-- Footer -->
            <footer class="px-6 py-3 border-t border-gray-200/60 bg-white/60">
                <p class="text-xs text-gray-400 text-center">
                    Absensi Operator v1.0 — PT Borneo Alumina Indonesia (PT. BAI)
                </p>
            </footer>
        </div>

        <!-- Toast Notification -->
        <transition
            enter-active-class="transform ease-out duration-300 transition"
            enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
            enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
            leave-active-class="transition ease-in duration-100"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="toast" class="fixed top-5 right-5 z-50 max-w-sm w-full bg-white rounded-2xl shadow-xl border border-gray-100 p-4 flex items-start gap-3">
                <div :class="[
                    'w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0',
                    toastType === 'success' ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600'
                ]">
                    <span class="material-symbols-outlined text-xl">
                        {{ toastType === 'success' ? 'check_circle' : 'error' }}
                    </span>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-semibold text-gray-800">
                        {{ toastType === 'success' ? 'Berhasil' : 'Kesalahan' }}
                    </p>
                    <p class="text-xs text-gray-400 mt-0.5">
                        {{ toast }}
                    </p>
                </div>
                <button @click="toast = null" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <span class="material-symbols-outlined text-lg">close</span>
                </button>
            </div>
        </transition>
    </div>
</template>

<style scoped>
@keyframes progress {
    0% { width: 0%; }
    100% { width: 100%; }
}
.animate-progress-bar {
    animation: progress 1.2s ease-in-out forwards;
}
</style>
