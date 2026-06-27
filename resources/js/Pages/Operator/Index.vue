<script setup>
import { ref, watch } from 'vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    operators: Object, // Paginated object
    filters: Object,
});

// Search and Filter States
const search = ref(props.filters.search || '');
const unit = ref(props.filters.unit || '');
const grup_shift = ref(props.filters.grup_shift || '');
const status = ref(props.filters.status || '');

// Form Modal State
const isModalOpen = ref(false);
const isEditing = ref(false);
const editingOperatorId = ref(null);

const form = useForm({
    nama: '',
    unit: '',
    grup_shift: '',
    status: 'Aktif',
    npk: '',
    keterangan: '',
});

// Debounce helper
const debounce = (fn, delay) => {
    let timeoutId;
    return (...args) => {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => fn(...args), delay);
    };
};

// Watch for search/filters changes and reload via Inertia
const reloadData = debounce(() => {
    router.get('/operator', {
        search: search.value,
        unit: unit.value,
        grup_shift: grup_shift.value,
        status: status.value,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

watch([search, unit, grup_shift, status], reloadData);

const openAddModal = () => {
    isEditing.value = false;
    editingOperatorId.value = null;
    form.reset();
    isModalOpen.value = true;
};

const openEditModal = (operator) => {
    isEditing.value = true;
    editingOperatorId.value = operator.id;
    form.nama = operator.nama;
    form.unit = operator.unit;
    form.grup_shift = operator.grup_shift;
    form.status = operator.status;
    
    form.npk = operator.npk || '';
    
    form.keterangan = operator.keterangan || '';
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    form.reset();
};

const submitForm = () => {
    if (isEditing.value) {
        form.put(`/operator/${editingOperatorId.value}`, {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post('/operator', {
            onSuccess: () => closeModal(),
        });
    }
};

const toggleStatus = (operator) => {
    const actionStr = operator.status === 'Aktif' ? 'menonaktifkan' : 'mengaktifkan';
    if (confirm(`Apakah Anda yakin ingin ${actionStr} operator "${operator.nama}"?`)) {
        router.post(`/operator/${operator.id}/toggle-status`);
    }
};

const getUnitBadgeClass = (u) => {
    switch (u) {
        case 'BTG': return 'bg-blue-50 text-blue-700 border-blue-100';
        case 'C&AHS': return 'bg-indigo-50 text-indigo-700 border-indigo-100';
        case 'Power Distribution': return 'bg-purple-50 text-purple-700 border-purple-100';
        default: return 'bg-gray-50 text-gray-700 border-gray-100';
    }
};

const getGroupBadgeClass = (g) => {
    switch (g) {
        case 'A': return 'bg-amber-50 text-amber-700 border-amber-100';
        case 'B': return 'bg-teal-50 text-teal-700 border-teal-100';
        case 'C': return 'bg-pink-50 text-pink-700 border-pink-100';
        case 'D': return 'bg-cyan-50 text-cyan-700 border-cyan-100';
        default: return 'bg-gray-50 text-gray-700 border-gray-100';
    }
};

const formatDate = (dateStr) => {
    if (!dateStr) return '—';
    const date = new Date(dateStr);
    return date.toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'long',
        year: 'numeric'
    });
};

const resetFilters = () => {
    search.value = '';
    unit.value = '';
    grup_shift.value = '';
    status.value = '';
};
</script>

<template>
    <AppLayout>
        <template #title>Master Operator</template>

        <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <p class="text-sm text-gray-500">Kelola data master operator PT Borneo Alumina Indonesia (PT. BAI) untuk rekapitulasi absensi harian.</p>
            </div>
            <button
                @click="openAddModal"
                class="flex items-center gap-2 bg-primary-600 hover:bg-primary-700 text-white px-4 py-2.5 rounded-xl text-sm font-semibold transition-all duration-200 shadow-md shadow-primary-500/10 cursor-pointer w-fit"
            >
                <span class="material-symbols-outlined text-sm">person_add</span>
                Tambah Operator
            </button>
        </div>

        <!-- Filter Bar -->
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 mb-6 flex flex-col gap-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Search -->
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-[20px]">search</span>
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Cari nama atau ID..."
                        class="w-full bg-white border border-gray-200 rounded-xl pl-10 pr-3.5 py-2.5 text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500 transition-all"
                    />
                </div>

                <!-- Unit Filter -->
                <div>
                    <select
                        v-model="unit"
                        class="w-full bg-white border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm text-gray-800 focus:outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500 transition-all"
                    >
                        <option value="">Semua Unit</option>
                        <option value="BTG">BTG</option>
                        <option value="C&AHS">C&AHS</option>
                        <option value="Power Distribution">Power Distribution</option>
                    </select>
                </div>

                <!-- Group Shift Filter -->
                <div>
                    <select
                        v-model="grup_shift"
                        class="w-full bg-white border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm text-gray-800 focus:outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500 transition-all"
                    >
                        <option value="">Semua Grup Shift</option>
                        <option value="A">Grup A</option>
                        <option value="B">Grup B</option>
                        <option value="C">Grup C</option>
                        <option value="D">Grup D</option>
                    </select>
                </div>

                <!-- Status Filter -->
                <div>
                    <select
                        v-model="status"
                        class="w-full bg-white border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm text-gray-800 focus:outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500 transition-all"
                    >
                        <option value="">Semua Status</option>
                        <option value="Aktif">Aktif</option>
                        <option value="Nonaktif">Nonaktif</option>
                    </select>
                </div>
            </div>

            <!-- Active Filters Summary -->
            <div v-if="search || unit || grup_shift || status" class="flex items-center justify-between border-t border-gray-100 pt-3 text-xs text-gray-500">
                <div class="flex items-center gap-2">
                    <span>Filter aktif:</span>
                    <span v-if="search" class="bg-gray-100 px-2 py-0.5 rounded-md text-gray-700">Pencarian: "{{ search }}"</span>
                    <span v-if="unit" class="bg-gray-100 px-2 py-0.5 rounded-md text-gray-700">Unit: {{ unit }}</span>
                    <span v-if="grup_shift" class="bg-gray-100 px-2 py-0.5 rounded-md text-gray-700">Grup: {{ grup_shift }}</span>
                    <span v-if="status" class="bg-gray-100 px-2 py-0.5 rounded-md text-gray-700">Status: {{ status }}</span>
                </div>
                <button @click="resetFilters" class="text-primary-600 hover:text-primary-700 font-semibold cursor-pointer">
                    Reset Filter
                </button>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100">
                            <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">ID Operator</th>
                            <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Nama</th>
                            <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Unit</th>
                            <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Grup Shift</th>
                            <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">NPK</th>
                            <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Keterangan</th>
                            <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr
                            v-for="op in operators.data"
                            :key="op.id"
                            class="hover:bg-gray-50/50 transition-colors"
                        >
                            <td class="px-6 py-4 text-sm font-mono font-bold text-primary-600">{{ op.id_operator }}</td>
                            <td class="px-6 py-4 text-sm font-semibold text-gray-800">{{ op.nama }}</td>
                            <td class="px-6 py-4 text-sm">
                                <span :class="['px-2.5 py-1 rounded-full text-xs font-semibold border', getUnitBadgeClass(op.unit)]">
                                    {{ op.unit }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <span :class="['px-2.5 py-1 rounded-full text-xs font-semibold border', getGroupBadgeClass(op.grup_shift)]">
                                    Grup {{ op.grup_shift }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 font-mono">{{ op.npk }}</td>
                            <td class="px-6 py-4 text-sm">
                                <button
                                    @click="toggleStatus(op)"
                                    :class="[
                                        'inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold cursor-pointer border transition-colors',
                                        op.status === 'Aktif'
                                            ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100'
                                            : 'bg-red-50 text-red-700 border-red-200 hover:bg-red-100'
                                    ]"
                                    :title="op.status === 'Aktif' ? 'Klik untuk Nonaktifkan' : 'Klik untuk Aktifkan'"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full" :class="op.status === 'Aktif' ? 'bg-emerald-500' : 'bg-red-500'"></span>
                                    {{ op.status }}
                                </button>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-400 max-w-[200px] truncate" :title="op.keterangan">
                                {{ op.keterangan || '—' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-right">
                                <div class="flex justify-end gap-2">
                                    <button
                                        @click="openEditModal(op)"
                                        class="p-1.5 text-gray-400 hover:text-primary-600 hover:bg-primary-50 rounded-lg transition-colors cursor-pointer"
                                        title="Edit Data"
                                    >
                                        <span class="material-symbols-outlined text-[20px]">edit</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="operators.data.length === 0">
                            <td colspan="8" class="px-6 py-12 text-center text-gray-400">
                                <span class="material-symbols-outlined text-4xl mb-2 text-gray-300">person_off</span>
                                <p class="text-sm">Tidak ada operator yang ditemukan.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Section -->
            <div v-if="operators.links.length > 3" class="px-6 py-4 bg-gray-50/50 border-t border-gray-100 flex items-center justify-between">
                <span class="text-xs text-gray-400">
                    Menampilkan {{ operators.from || 0 }} sampai {{ operators.to || 0 }} dari {{ operators.total || 0 }} operator
                </span>
                <div class="flex gap-1">
                    <template v-for="(link, i) in operators.links" :key="i">
                        <div v-if="link.url === null" class="px-3 py-1.5 text-xs text-gray-300 bg-white border border-gray-200 rounded-lg cursor-default select-none" v-html="link.label"></div>
                        <Link
                            v-else
                            :href="link.url"
                            :class="[
                                'px-3 py-1.5 text-xs rounded-lg border font-medium transition-colors',
                                link.active
                                    ? 'bg-primary-600 border-primary-600 text-white shadow-sm'
                                    : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'
                            ]"
                            v-html="link.label"
                        />
                    </template>
                </div>
            </div>
        </div>

        <!-- Add/Edit Modal -->
        <transition
            enter-active-class="transition-opacity duration-250 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
                <div class="bg-white rounded-2xl w-full max-w-lg overflow-hidden shadow-2xl border border-gray-100">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                        <h3 class="font-bold text-gray-800 flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary-500">
                                {{ isEditing ? 'manage_accounts' : 'person_add' }}
                            </span>
                            {{ isEditing ? 'Edit Data Operator' : 'Tambah Operator Baru' }}
                        </h3>
                        <button @click="closeModal" class="text-gray-400 hover:text-gray-600 transition-colors cursor-pointer">
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>

                    <form @submit.prevent="submitForm" class="p-6 space-y-4">
                        <!-- Nama -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Nama Lengkap</label>
                            <input
                                v-model="form.nama"
                                type="text"
                                required
                                placeholder="Masukkan nama lengkap operator"
                                class="w-full bg-white border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm text-gray-800 focus:outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500 transition-all"
                            />
                            <p v-if="form.errors.nama" class="text-xs text-red-500 mt-1">{{ form.errors.nama }}</p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Unit -->
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Unit Kerja</label>
                                <select
                                    v-model="form.unit"
                                    required
                                    class="w-full bg-white border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm text-gray-800 focus:outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500 transition-all"
                                >
                                    <option value="" disabled>Pilih Unit</option>
                                    <option value="BTG">BTG</option>
                                    <option value="C&AHS">C&AHS</option>
                                    <option value="Power Distribution">Power Distribution</option>
                                </select>
                                <p v-if="form.errors.unit" class="text-xs text-red-500 mt-1">{{ form.errors.unit }}</p>
                            </div>

                            <!-- Grup Shift -->
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Grup Shift</label>
                                <select
                                    v-model="form.grup_shift"
                                    required
                                    class="w-full bg-white border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm text-gray-800 focus:outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500 transition-all"
                                >
                                    <option value="" disabled>Pilih Grup</option>
                                    <option value="A">Grup A</option>
                                    <option value="B">Grup B</option>
                                    <option value="C">Grup C</option>
                                    <option value="D">Grup D</option>
                                </select>
                                <p v-if="form.errors.grup_shift" class="text-xs text-red-500 mt-1">{{ form.errors.grup_shift }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- NPK -->
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">NPK</label>
                                <input
                                    v-model="form.npk"
                                    type="text"
                                    required
                                    placeholder="Masukkan NPK"
                                    class="w-full bg-white border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm text-gray-800 focus:outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500 transition-all"
                                />
                                <p v-if="form.errors.npk" class="text-xs text-red-500 mt-1">{{ form.errors.npk }}</p>
                            </div>

                            <!-- Status -->
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Status</label>
                                <select
                                    v-model="form.status"
                                    required
                                    class="w-full bg-white border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm text-gray-800 focus:outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500 transition-all"
                                >
                                    <option value="Aktif">Aktif</option>
                                    <option value="Nonaktif">Nonaktif</option>
                                </select>
                                <p v-if="form.errors.status" class="text-xs text-red-500 mt-1">{{ form.errors.status }}</p>
                            </div>
                        </div>

                        <!-- Keterangan -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Keterangan Tambahan</label>
                            <textarea
                                v-model="form.keterangan"
                                placeholder="Tulis catatan opsional..."
                                rows="3"
                                class="w-full bg-white border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm text-gray-800 focus:outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500 transition-all resize-none"
                            ></textarea>
                            <p v-if="form.errors.keterangan" class="text-xs text-red-500 mt-1">{{ form.errors.keterangan }}</p>
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
                                {{ form.processing ? 'Menyimpan...' : (isEditing ? 'Simpan Perubahan' : 'Tambah Operator') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </transition>
    </AppLayout>
</template>
