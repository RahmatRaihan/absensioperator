<script setup>
import { ref } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';

const showPassword = ref(false);

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    sessionStorage.removeItem('splash_shown');
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Masuk" />

    <div class="min-h-screen flex flex-col md:flex-row bg-[#F4F6F9] overflow-hidden">
        <!-- Left Pane: Sidebar Dark Blue Accent (#0A1628) -->
        <div class="md:w-[42%] bg-[#0A1628] relative overflow-hidden flex flex-col justify-between p-8 md:p-12 text-white">
            <!-- Ambient backgrounds for a high-end feel -->
            <div class="absolute -top-40 -left-40 w-[450px] h-[450px] bg-primary-500/10 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-40 -right-40 w-[450px] h-[450px] bg-primary-600/10 rounded-full blur-3xl"></div>

            <!-- Top Brand (Desktop) -->
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

            <!-- Footer copyright (Desktop) -->
            <div class="relative z-10 text-[11px] text-white/30 hidden md:block">
                Absensi Operator v1.0 — © 2026 PT Borneo Alumina Indonesia
            </div>
        </div>

        <!-- Right Pane: Login Form -->
        <div class="flex-1 flex items-center justify-center p-6 md:p-12 relative overflow-hidden bg-[#F4F6F9]">
            <div class="absolute -top-40 -right-40 w-96 h-96 bg-primary-200/30 rounded-full blur-3xl"></div>
            
            <div class="w-full max-w-md z-10">
                <!-- Mobile Logo Header (Only shown on mobile) -->
                <div class="flex flex-col items-center mb-8 md:hidden">
                    <div class="w-16 h-16 bg-[#0A1628] rounded-2xl flex items-center justify-center p-3 mb-3 border border-white/10 shadow-lg">
                        <img src="/logo.png?v=2" alt="Logo PT. BAI" class="w-full h-full object-contain" />
                    </div>
                    <h2 class="text-xl font-bold text-gray-800">Absensi Operator</h2>
                    <p class="text-xs text-gray-500">PT Borneo Alumina Indonesia</p>
                </div>

                <!-- Login Card -->
                <div class="bg-white/95 backdrop-blur-xl rounded-3xl shadow-xl shadow-gray-200/50 border border-white p-8 md:p-10">
                    <div class="mb-6">
                        <h2 class="text-xl font-bold text-gray-800">Selamat Datang</h2>
                        <p class="text-xs text-gray-400 mt-1">Silakan masuk menggunakan akun administrator Anda.</p>
                    </div>

                    <form @submit.prevent="submit" class="space-y-5">
                        <!-- Email field -->
                        <div>
                            <label for="email" class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                                Alamat Email
                            </label>
                            <div class="relative">
                                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-[20px]">
                                    mail
                                </span>
                                <input
                                    id="email"
                                    type="email"
                                    v-model="form.email"
                                    required
                                    autofocus
                                    placeholder="nama@email.com"
                                    :class="[
                                        'w-full pl-12 pr-4 py-3 bg-gray-50/50 hover:bg-gray-50 border rounded-2xl text-sm transition-all focus:outline-none focus:ring-2',
                                        form.errors.email
                                            ? 'border-red-300 focus:ring-red-200 focus:border-red-500 text-red-900'
                                            : 'border-gray-200 focus:ring-primary-100 focus:border-primary-500 text-gray-800'
                                    ]"
                                />
                            </div>
                            <p v-if="form.errors.email" class="text-xs text-red-500 mt-1.5 flex items-center gap-1">
                                <span class="material-symbols-outlined text-[14px]">error</span>
                                {{ form.errors.email }}
                            </p>
                        </div>

                        <!-- Password field -->
                        <div>
                            <label for="password" class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                                Kata Sandi
                            </label>
                            <div class="relative">
                                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-[20px]">
                                    lock
                                </span>
                                <input
                                    id="password"
                                    :type="showPassword ? 'text' : 'password'"
                                    v-model="form.password"
                                    required
                                    placeholder="••••••••"
                                    :class="[
                                        'w-full pl-12 pr-12 py-3 bg-gray-50/50 hover:bg-gray-50 border rounded-2xl text-sm transition-all focus:outline-none focus:ring-2',
                                        form.errors.password
                                            ? 'border-red-300 focus:ring-red-200 focus:border-red-500 text-red-900'
                                            : 'border-gray-200 focus:ring-primary-100 focus:border-primary-500 text-gray-800'
                                    ]"
                                />
                                <button
                                    type="button"
                                    @click="showPassword = !showPassword"
                                    class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors flex items-center justify-center"
                                >
                                    <span class="material-symbols-outlined text-[20px]">
                                        {{ showPassword ? 'visibility_off' : 'visibility' }}
                                    </span>
                                </button>
                            </div>
                            <p v-if="form.errors.password" class="text-xs text-red-500 mt-1.5 flex items-center gap-1">
                                <span class="material-symbols-outlined text-[14px]">error</span>
                                {{ form.errors.password }}
                            </p>
                        </div>

                        <!-- Remember Me -->
                        <div class="flex items-center justify-between">
                            <label class="flex items-center cursor-pointer select-none">
                                <input
                                    type="checkbox"
                                    v-model="form.remember"
                                    class="w-4 h-4 rounded text-primary-600 border-gray-300 focus:ring-primary-500 focus:ring-offset-0 transition-colors cursor-pointer"
                                />
                                <span class="ml-2 text-xs text-gray-500 font-medium hover:text-gray-700 transition-colors">
                                    Ingat Saya
                                </span>
                            </label>
                        </div>

                        <!-- Submit Button -->
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full py-3.5 bg-[#0060AF] hover:bg-[#004D8C] disabled:bg-primary-300 text-white font-semibold rounded-2xl text-sm shadow-lg shadow-primary-500/25 transition-all duration-200 flex items-center justify-center gap-2 cursor-pointer disabled:cursor-not-allowed transform active:scale-[0.98]"
                        >
                            <span v-if="form.processing" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                            <span v-else class="material-symbols-outlined text-[18px]">login</span>
                            <span>Masuk ke Sistem</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>
