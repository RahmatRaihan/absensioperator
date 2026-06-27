<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    periodes: Array,
    months: Object,
});

// Calculate year options (from 2024 to 2030)
const years = Array.from({ length: 7 }, (_, i) => 2024 + i);
const currentYear = new Date().getFullYear();
const currentMonth = new Date().getMonth() + 1; // 1-indexed

const isModalOpen = ref(false);
const isHolidayModalOpen = ref(false);
const selectedPeriode = ref(null);
const newHolidayDate = ref('');

const form = useForm({
    bulan: currentMonth,
    tahun: currentYear,
});

const holidayForm = useForm({
    libur_nasional: []
});

const openModal = () => {
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    form.reset();
};

const openHolidayModal = (periode) => {
    selectedPeriode.value = { ...periode };
    holidayForm.libur_nasional = [...(periode.libur_nasional || [])];
    newHolidayDate.value = '';
    isHolidayModalOpen.value = true;
};

const closeHolidayModal = () => {
    isHolidayModalOpen.value = false;
    selectedPeriode.value = null;
    holidayForm.reset();
};

const addHoliday = () => {
    if (!newHolidayDate.value) return;
    
    // Check if date is within range
    const date = new Date(newHolidayDate.value);
    // Parse target dates cleanly in local time zone
    const [y, m, d] = newHolidayDate.value.split('-').map(Number);
    const targetDate = new Date(y, m - 1, d, 0, 0, 0, 0);

    const [startY, startM, startD] = selectedPeriode.value.tgl_mulai.split('-').map(Number);
    const startDate = new Date(startY, startM - 1, startD, 0, 0, 0, 0);

    const [endY, endM, endD] = selectedPeriode.value.tgl_selesai.split('-').map(Number);
    const endDate = new Date(endY, endM - 1, endD, 0, 0, 0, 0);
    
    if (targetDate < startDate || targetDate > endDate) {
        alert(`Tanggal harus berada dalam rentang siklus periode: ${formatDate(selectedPeriode.value.tgl_mulai)} s/d ${formatDate(selectedPeriode.value.tgl_selesai)}`);
        return;
    }
    
    if (holidayForm.libur_nasional.includes(newHolidayDate.value)) {
        alert('Tanggal libur nasional sudah ada dalam daftar.');
        return;
    }
    
    holidayForm.libur_nasional.push(newHolidayDate.value);
    holidayForm.libur_nasional.sort();
    newHolidayDate.value = '';
};

const removeHoliday = (index) => {
    holidayForm.libur_nasional.splice(index, 1);
};

const submitHolidayForm = () => {
    holidayForm.put(`/periode/${selectedPeriode.value.id}`, {
        onSuccess: () => {
            closeHolidayModal();
        }
    });
};

const submitForm = () => {
    form.post('/periode', {
        onSuccess: () => {
            closeModal();
        },
    });
};

const toggleStatus = (id, currentStatus) => {
    const newStatus = currentStatus === 'Aktif' ? 'Selesai' : 'Aktif';
    
    // We only confirm if deactivating or changing
    router.put(`/periode/${id}`, {
        status: newStatus,
    });
};

const deletePeriode = (id, label) => {
    if (confirm(`Apakah Anda yakin ingin menghapus periode "${label}"? Semua data absensi di periode ini akan terhapus permanen.`)) {
        router.delete(`/periode/${id}`);
    }
};

const formatDate = (dateStr) => {
    if (!dateStr) return '—';
    // Get only the date portion (YYYY-MM-DD) if it's an ISO string to avoid parsing issues and timezone shifts
    const cleanDateStr = dateStr.includes('T') ? dateStr.split('T')[0] : dateStr;
    const parts = cleanDateStr.split('-');
    if (parts.length === 3) {
        const year = parseInt(parts[0], 10);
        const month = parseInt(parts[1], 10) - 1;
        const day = parseInt(parts[2], 10);
        const date = new Date(year, month, day);
        return date.toLocaleDateString('id-ID', {
            day: '2-digit',
            month: 'long',
            year: 'numeric'
        });
    }
    const date = new Date(cleanDateStr);
    return date.toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'long',
        year: 'numeric'
    });
};
</script>

<template>
    <AppLayout>
        <template #title>Manajemen Periode Absensi</template>

        <div class="mb-6 flex justify-between items-center">
            <div>
                <p class="text-sm text-gray-500">Kelola periode siklus absensi bulanan operator (siklus tanggal 21 s/d 20).</p>
            </div>
            <button
                @click="openModal"
                class="flex items-center gap-2 bg-primary-600 hover:bg-primary-700 text-white px-4 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 shadow-md shadow-primary-500/10 cursor-pointer"
            >
                <span class="material-symbols-outlined text-sm">add</span>
                Buat Periode Baru
            </button>
        </div>

        <!-- Periodes Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100">
                            <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">No</th>
                            <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Periode</th>
                            <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Kode</th>
                            <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Rentang Siklus</th>
                            <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Durasi</th>
                            <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Libur Nasional</th>
                            <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Operator</th>
                            <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr
                             v-for="(periode, index) in periodes"
                            :key="periode.id"
                            class="hover:bg-gray-50/50 transition-colors"
                        >
                            <td class="px-6 py-4 text-sm font-medium text-gray-500">{{ index + 1 }}</td>
                            <td class="px-6 py-4 text-sm font-semibold text-gray-800">{{ periode.label }}</td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <span class="font-mono bg-gray-100 text-gray-700 px-2 py-1 rounded text-xs">{{ periode.kode_periode }}</span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <div class="flex items-center gap-1.5">
                                    <span>{{ formatDate(periode.tgl_mulai) }}</span>
                                    <span class="text-gray-400">s/d</span>
                                    <span>{{ formatDate(periode.tgl_selesai) }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 font-medium">
                                {{ periode.total_hari }} Hari
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 font-medium">
                                <span class="flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-gray-400 text-[18px]">event_busy</span>
                                    {{ (periode.libur_nasional || []).length }} Hari
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 font-medium">
                                <span class="flex items-center gap-1.5">
                                    <span class="material-symbols-outlined text-gray-400 text-[18px]">groups</span>
                                    {{ periode.absensis_count || 0 }} Operator
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <button
                                    @click="toggleStatus(periode.id, periode.status)"
                                    :class="[
                                        'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold transition-all cursor-pointer border',
                                        periode.status === 'Aktif'
                                            ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100'
                                             : 'bg-gray-100 text-gray-600 border-gray-200 hover:bg-gray-200'
                                    ]"
                                    :title="periode.status === 'Aktif' ? 'Klik untuk selesaikan periode' : 'Klik untuk aktifkan periode (menonaktifkan periode lain)'"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full" :class="periode.status === 'Aktif' ? 'bg-emerald-500' : 'bg-gray-400'"></span>
                                    {{ periode.status }}
                                </button>
                            </td>
                            <td class="px-6 py-4 text-sm text-right">
                                <div class="flex justify-end gap-2">
                                    <button
                                        @click="openHolidayModal(periode)"
                                        class="p-1.5 text-gray-400 hover:text-primary-600 hover:bg-primary-50 rounded-lg transition-colors cursor-pointer"
                                        title="Kelola Libur Nasional"
                                    >
                                        <span class="material-symbols-outlined text-[20px]">calendar_month</span>
                                    </button>
                                    <button
                                        @click="deletePeriode(periode.id, periode.label)"
                                        class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors cursor-pointer"
                                        title="Hapus Periode"
                                    >
                                        <span class="material-symbols-outlined text-[20px]">delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="periodes.length === 0">
                            <td colspan="9" class="px-6 py-12 text-center text-gray-400">
                                <span class="material-symbols-outlined text-4xl mb-2 text-gray-300">calendar_today</span>
                                <p class="text-sm">Belum ada periode yang dibuat.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Add Periode Modal -->
        <transition
            enter-active-class="transition-opacity duration-250 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
                <div class="bg-white rounded-2xl w-full max-w-md overflow-hidden shadow-2xl border border-gray-100">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                        <h3 class="font-bold text-gray-800 flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary-500">date_range</span>
                            Buat Periode Baru
                        </h3>
                        <button @click="closeModal" class="text-gray-400 hover:text-gray-600 transition-colors cursor-pointer">
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>

                    <form @submit.prevent="submitForm" class="p-6 space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Bulan</label>
                            <select
                                v-model="form.bulan"
                                class="w-full bg-white border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm text-gray-800 focus:outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500 transition-all"
                            >
                                <option v-for="(name, index) in months" :key="index" :value="index">
                                    {{ name }}
                                </option>
                            </select>
                            <p v-if="form.errors.bulan" class="text-xs text-red-500 mt-1">{{ form.errors.bulan }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Tahun</label>
                            <select
                                v-model="form.tahun"
                                class="w-full bg-white border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm text-gray-800 focus:outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500 transition-all"
                            >
                                <option v-for="year in years" :key="year" :value="year">
                                    {{ year }}
                                </option>
                            </select>
                            <p v-if="form.errors.tahun" class="text-xs text-red-500 mt-1">{{ form.errors.tahun }}</p>
                        </div>

                        <div class="bg-blue-50 border border-blue-100 rounded-xl p-3.5 text-xs text-blue-800 space-y-1">
                            <p class="font-semibold flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-[16px]">info</span>
                                Perhitungan Siklus Kerja:
                            </p>
                            <p>Siklus dimulai dari tanggal 21 bulan sebelumnya hingga tanggal 20 bulan terpilih. Ketika dibuat, periode baru otomatis berstatus <strong>Aktif</strong>.</p>
                        </div>

                        <div class="pt-4 flex justify-end gap-3 border-t border-gray-100">
                            <button
                                type="button"
                                @click="closeModal"
                                class="px-4 py-2 text-sm font-semibold text-gray-500 hover:text-gray-700 hover:bg-gray-50 rounded-xl transition-all cursor-pointer"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="bg-primary-600 hover:bg-primary-700 text-white px-5 py-2 rounded-xl text-sm font-semibold transition-all shadow-md shadow-primary-500/10 cursor-pointer disabled:opacity-50"
                            >
                                {{ form.processing ? 'Menyimpan...' : 'Simpan Periode' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </transition>

        <!-- Manage Libur Nasional Modal -->
        <transition
            enter-active-class="transition-opacity duration-250 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="isHolidayModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
                <div class="bg-white rounded-2xl w-full max-w-md overflow-hidden shadow-2xl border border-gray-100">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                        <div>
                            <h3 class="font-bold text-gray-800 flex items-center gap-2">
                                <span class="material-symbols-outlined text-primary-500">calendar_month</span>
                                Kelola Libur Nasional
                            </h3>
                            <p class="text-xs text-gray-500 mt-0.5" v-if="selectedPeriode">
                                Periode: {{ selectedPeriode.label }}
                            </p>
                        </div>
                        <button @click="closeHolidayModal" class="text-gray-400 hover:text-gray-600 transition-colors cursor-pointer">
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>

                    <div class="p-6 space-y-4">
                        <div class="bg-gray-50 border border-gray-100 rounded-xl p-3.5 text-xs text-gray-600 space-y-1" v-if="selectedPeriode">
                            <div class="flex items-center gap-1.5 font-semibold text-gray-700">
                                <span class="material-symbols-outlined text-[16px] text-gray-400">info_i</span>
                                Rentang Tanggal Siklus Periode:
                            </div>
                            <div>{{ formatDate(selectedPeriode.tgl_mulai) }} s/d {{ formatDate(selectedPeriode.tgl_selesai) }}</div>
                        </div>

                        <!-- Add Holiday Form -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider">Tambah Tanggal Libur</label>
                            <div class="flex gap-2">
                                <input
                                    type="date"
                                    v-model="newHolidayDate"
                                    :min="selectedPeriode?.tgl_mulai"
                                    :max="selectedPeriode?.tgl_selesai"
                                    class="flex-1 bg-white border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm text-gray-800 focus:outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500 transition-all"
                                />
                                <button
                                    type="button"
                                    @click="addHoliday"
                                    class="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2.5 rounded-xl text-sm font-semibold transition-all flex items-center gap-1 cursor-pointer"
                                >
                                    <span class="material-symbols-outlined text-[18px]">add</span>
                                    Tambah
                                </button>
                            </div>
                        </div>

                        <!-- Holiday List -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider">Daftar Tanggal Libur Terdaftar</label>
                            
                            <div v-if="holidayForm.libur_nasional.length === 0" class="border border-dashed border-gray-200 rounded-xl p-6 text-center text-gray-400">
                                <span class="material-symbols-outlined text-3xl mb-1 text-gray-300">event_busy</span>
                                <p class="text-xs">Belum ada hari libur nasional ditambahkan.</p>
                            </div>

                            <div v-else class="max-h-48 overflow-y-auto border border-gray-100 rounded-xl divide-y divide-gray-50">
                                <div
                                    v-for="(date, idx) in holidayForm.libur_nasional"
                                    :key="date"
                                    class="flex items-center justify-between px-4 py-2.5 hover:bg-gray-50/50 transition-colors"
                                >
                                    <span class="text-sm font-medium text-gray-700 font-mono">{{ formatDate(date) }}</span>
                                    <button
                                        type="button"
                                        @click="removeHoliday(idx)"
                                        class="text-gray-400 hover:text-red-500 transition-colors p-1 rounded-lg hover:bg-red-50 cursor-pointer"
                                        title="Hapus"
                                    >
                                        <span class="material-symbols-outlined text-[18px]">close</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="pt-4 flex justify-end gap-3 border-t border-gray-100">
                            <button
                                type="button"
                                @click="closeHolidayModal"
                                class="px-4 py-2 text-sm font-semibold text-gray-500 hover:text-gray-700 hover:bg-gray-50 rounded-xl transition-all cursor-pointer"
                            >
                                Batal
                            </button>
                            <button
                                type="button"
                                @click="submitHolidayForm"
                                :disabled="holidayForm.processing"
                                class="bg-primary-600 hover:bg-primary-700 text-white px-5 py-2 rounded-xl text-sm font-semibold transition-all shadow-md shadow-primary-500/10 cursor-pointer disabled:opacity-50"
                            >
                                {{ holidayForm.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </transition>
    </AppLayout>
</template>
