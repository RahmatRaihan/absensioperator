<script setup>
import { ref, computed } from 'vue';
import { useForm, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    periodes: Array,
});

const pageProps = computed(() => usePage().props);
const uploadErrors = computed(() => pageProps.value.flash?.uploadErrors || []);
const uploadPreview = computed(() => pageProps.value.flash?.uploadPreview || []);
const uploadPeriodeId = computed(() => pageProps.value.flash?.uploadPeriodeId || null);

// Form for template download
const templateForm = useForm({
    periode_id: props.periodes[0]?.id || '',
});

// Form for file upload
const uploadForm = useForm({
    periode_id: props.periodes[0]?.id || '',
    file: null,
});

// Drag and drop state
const isDragging = ref(false);
const fileInput = ref(null);

const handleFileChange = (e) => {
    uploadForm.file = e.target.files[0];
};

const handleDragOver = (e) => {
    e.preventDefault();
    isDragging.value = true;
};

const handleDragLeave = () => {
    isDragging.value = false;
};

const handleDrop = (e) => {
    e.preventDefault();
    isDragging.value = false;
    if (e.dataTransfer.files.length > 0) {
        uploadForm.file = e.dataTransfer.files[0];
    }
};

const downloadTemplate = () => {
    if (!templateForm.periode_id) {
        alert('Silakan pilih periode terlebih dahulu.');
        return;
    }
    // Direct link trigger for binary download
    window.location.href = `/absensi/upload/template?periode_id=${templateForm.periode_id}`;
};

const submitUpload = () => {
    if (!uploadForm.periode_id) {
        alert('Silakan pilih periode target upload.');
        return;
    }
    if (!uploadForm.file) {
        alert('Silakan pilih file Excel terlebih dahulu.');
        return;
    }

    uploadForm.post('/absensi/upload', {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            uploadForm.file = null;
            if (fileInput.value) fileInput.value.value = '';
        }
    });
};

const confirmImport = () => {
    if (uploadPreview.value.length === 0) return;

    router.post('/absensi/upload/confirm', {
        periode_id: uploadPeriodeId.value,
        entries: uploadPreview.value.map(row => ({
            operator_id: row.operator_id,
            kode_string: row.kode_string,
            keterangan: row.keterangan
        }))
    });
};

const cancelImport = () => {
    // Clear flash data from session by reloading route cleanly
    router.get('/absensi/upload');
};
</script>

<template>
    <AppLayout>
        <template #title>Upload Bulk Absensi (Excel)</template>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <!-- Download Card -->
            <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm space-y-4">
                <h3 class="font-bold text-gray-800 flex items-center gap-2 border-b border-gray-50 pb-3">
                    <span class="material-symbols-outlined text-primary-500">file_download</span>
                    Langkah 1: Download Template
                </h3>
                <p class="text-xs text-gray-400 leading-relaxed">
                    Pilih periode yang ingin Anda isi. Sistem akan membuat template Excel prefilled berisi daftar operator aktif yang siap untuk diisi kodenya.
                </p>

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Pilih Periode</label>
                        <select
                            v-model="templateForm.periode_id"
                            class="w-full bg-white border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm text-gray-800 focus:outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500 transition-all font-semibold"
                        >
                            <option v-for="p in periodes" :key="p.id" :value="p.id">
                                {{ p.label }} ({{ p.status }})
                            </option>
                        </select>
                    </div>

                    <button
                        @click="downloadTemplate"
                        class="w-full bg-primary-600 hover:bg-primary-700 text-white py-2.5 rounded-xl text-sm font-semibold transition-all shadow-md shadow-primary-500/10 cursor-pointer flex items-center justify-center gap-2"
                    >
                        <span class="material-symbols-outlined text-sm">download</span>
                        Download Template Excel
                    </button>
                </div>
            </div>

            <!-- Upload Card -->
            <div class="bg-white rounded-2xl p-5 border border-gray-100 shadow-sm lg:col-span-2 space-y-4">
                <h3 class="font-bold text-gray-800 flex items-center gap-2 border-b border-gray-50 pb-3">
                    <span class="material-symbols-outlined text-primary-500">upload_file</span>
                    Langkah 2: Upload File Terisi
                </h3>
                
                <form @submit.prevent="submitUpload" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-end">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Pilih Periode Target</label>
                            <select
                                v-model="uploadForm.periode_id"
                                class="w-full bg-white border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm text-gray-800 focus:outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500 transition-all"
                            >
                                <option v-for="p in periodes" :key="p.id" :value="p.id">
                                    {{ p.label }} ({{ p.status }})
                                </option>
                            </select>
                        </div>
                        
                        <div>
                            <button
                                type="submit"
                                :disabled="uploadForm.processing || !uploadForm.file"
                                class="w-full bg-primary-600 hover:bg-primary-700 text-white py-2.5 rounded-xl text-sm font-semibold transition-all shadow-md shadow-primary-500/10 cursor-pointer disabled:opacity-50 flex items-center justify-center gap-2"
                            >
                                <span class="material-symbols-outlined text-sm">publish</span>
                                {{ uploadForm.processing ? 'Memproses...' : 'Upload & Validasi' }}
                            </button>
                        </div>
                    </div>

                    <!-- Drag and Drop Box -->
                    <div
                        @dragover="handleDragOver"
                        @dragleave="handleDragLeave"
                        @drop="handleDrop"
                        :class="[
                            'border-2 border-dashed rounded-2xl p-6 text-center cursor-pointer transition-all flex flex-col items-center justify-center min-h-[140px]',
                            isDragging ? 'border-primary-500 bg-primary-50/20' : 'border-gray-200 hover:border-gray-300 bg-gray-50/30'
                        ]"
                        @click="fileInput.click()"
                    >
                        <input
                            ref="fileInput"
                            type="file"
                            accept=".xlsx, .xls"
                            class="hidden"
                            @change="handleFileChange"
                        />
                        <span class="material-symbols-outlined text-gray-400 text-4xl mb-2">cloud_upload</span>
                        <p class="text-sm font-semibold text-gray-700">
                            {{ uploadForm.file ? uploadForm.file.name : 'Tarik file Excel Anda di sini, atau cari file' }}
                        </p>
                        <p class="text-xs text-gray-400 mt-1">Hanya file berekstensi .xlsx atau .xls</p>
                    </div>
                </form>
            </div>
        </div>

        <!-- Validation Errors Display -->
        <div v-if="uploadErrors.length > 0" class="bg-white rounded-2xl p-5 border border-red-100 shadow-sm mb-6 space-y-3">
            <h3 class="font-bold text-red-600 flex items-center gap-2 border-b border-red-50 pb-3">
                <span class="material-symbols-outlined">error</span>
                Ditemukan {{ uploadErrors.length }} Kesalahan Data Pada Excel
            </h3>
            <p class="text-xs text-gray-400">File tidak dapat di-import karena berisi kesalahan berikut. Harap perbaiki file Excel Anda dan upload kembali.</p>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-red-50/50 border-b border-red-100">
                            <th class="px-4 py-2.5 font-semibold text-red-700">Baris</th>
                            <th class="px-4 py-2.5 font-semibold text-red-700">ID Operator</th>
                            <th class="px-4 py-2.5 font-semibold text-red-700">Nama Operator</th>
                            <th class="px-4 py-2.5 font-semibold text-red-700">Detail Kesalahan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="err in uploadErrors" :key="err.row" class="hover:bg-red-50/10">
                            <td class="px-4 py-3 font-bold font-mono text-gray-700">{{ err.row }}</td>
                            <td class="px-4 py-3 font-mono font-semibold text-gray-600">{{ err.id_operator || '—' }}</td>
                            <td class="px-4 py-3 text-gray-700 font-semibold">{{ err.nama }}</td>
                            <td class="px-4 py-3">
                                <ul class="list-disc pl-4 text-red-600 space-y-0.5 font-medium">
                                    <li v-for="(msg, i) in err.errors" :key="i">{{ msg }}</li>
                                </ul>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Valid Preview Display -->
        <div v-if="uploadPreview.length > 0 && uploadErrors.length === 0" class="bg-white rounded-2xl p-5 border border-emerald-100 shadow-sm mb-6 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-emerald-50 pb-3 gap-3">
                <h3 class="font-bold text-emerald-700 flex items-center gap-2">
                    <span class="material-symbols-outlined">check_circle</span>
                    Validasi Berhasil: {{ uploadPreview.length }} Data Siap Di-import
                </h3>
                <div class="flex gap-2">
                    <button
                        @click="cancelImport"
                        class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-xl text-xs font-semibold transition-colors cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        @click="confirmImport"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2 rounded-xl text-xs font-semibold transition-all shadow-md shadow-emerald-500/10 cursor-pointer"
                    >
                        Konfirmasi & Simpan Ke Database
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-emerald-50/30 border-b border-emerald-100">
                            <th class="px-4 py-2.5 font-semibold text-emerald-700">Baris</th>
                            <th class="px-4 py-2.5 font-semibold text-emerald-700">Operator</th>
                            <th class="px-4 py-2.5 font-semibold text-emerald-700">Unit / Grup</th>
                            <th class="px-4 py-2.5 font-semibold text-emerald-700">Kode String</th>
                            <th class="px-3 py-2.5 font-semibold text-emerald-700 text-center">S1</th>
                            <th class="px-3 py-2.5 font-semibold text-emerald-700 text-center">S2</th>
                            <th class="px-3 py-2.5 font-semibold text-emerald-700 text-center">S3</th>
                            <th class="px-3 py-2.5 font-semibold text-emerald-700 text-center">H</th>
                            <th class="px-3 py-2.5 font-semibold text-emerald-700 text-center">S</th>
                            <th class="px-3 py-2.5 font-semibold text-emerald-700 text-center">I</th>
                            <th class="px-3 py-2.5 font-semibold text-emerald-700 text-center">A</th>
                            <th class="px-4 py-2.5 font-semibold text-emerald-700 text-center">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="row in uploadPreview" :key="row.row" class="hover:bg-emerald-50/10">
                            <td class="px-4 py-3 font-mono text-gray-500">{{ row.row }}</td>
                            <td class="px-4 py-3">
                                <span class="font-mono text-gray-400 font-bold bg-gray-100 px-1.5 py-0.5 rounded text-[10px] mr-1.5">{{ row.id_operator }}</span>
                                <span class="font-semibold text-gray-800">{{ row.nama }}</span>
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ row.unit }} / Grup {{ row.grup_shift }}</td>
                            <td class="px-4 py-3 font-mono tracking-widest text-gray-500">{{ row.kode_string }}</td>
                            <td class="px-3 py-3 text-center text-gray-700 font-medium">{{ row.totals?.jml_shift1 }}</td>
                            <td class="px-3 py-3 text-center text-gray-700 font-medium">{{ row.totals?.jml_shift2 }}</td>
                            <td class="px-3 py-3 text-center text-gray-700 font-medium">{{ row.totals?.jml_shift3 }}</td>
                            <td class="px-3 py-3 text-center text-gray-400">{{ row.totals?.jml_holiday }}</td>
                            <td class="px-3 py-3 text-center text-gray-400">{{ row.totals?.jml_sakit }}</td>
                            <td class="px-3 py-3 text-center text-gray-400">{{ row.totals?.jml_izin }}</td>
                            <td class="px-3 py-3 text-center text-gray-400">{{ row.totals?.jml_alpa }}</td>
                            <td class="px-4 py-3 text-center font-bold text-primary-600">{{ row.totals?.total_shift }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
