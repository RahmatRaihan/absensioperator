<script setup>
import { ref, computed, watch } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    periodes: Array,
    selectedPeriodeId: Number,
    periode: Object,
    records: Array,
    subtotals: Object,
    grandTotal: Object,
    filters: Object,
});

// Filters
const selectedPeriode = ref(props.selectedPeriodeId || '');
const search = ref(props.filters.search || '');
const unit = ref(props.filters.unit || '');
const grup_shift = ref(props.filters.grup_shift || '');
const exceptionsOnly = ref(props.filters.exceptions_only === 'true');

// Expanded row indices
const expandedRows = ref({});

const toggleRow = (id) => {
    expandedRows.value[id] = !expandedRows.value[id];
};

// Debounce helper
const debounce = (fn, delay) => {
    let timeoutId;
    return (...args) => {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => fn(...args), delay);
    };
};

// Watchers for filtering
const reloadData = debounce(() => {
    router.get('/rekap', {
        periode_id: selectedPeriode.value,
        search: search.value,
        unit: unit.value,
        grup_shift: grup_shift.value,
        exceptions_only: exceptionsOnly.value ? 'true' : 'false',
    }, {
        preserveState: true,
        replace: true,
    });
}, 300);

watch([selectedPeriode, search, unit, grup_shift, exceptionsOnly], reloadData);

// Generate calendar dates for expanded view
const calendarDays = computed(() => {
    if (!props.periode) return [];
    
    const days = [];
    const start = new Date(props.periode.tgl_mulai);
    const end = new Date(props.periode.tgl_selesai);
    
    let current = new Date(start);
    while (current <= end) {
        days.push({
            dateStr: current.toISOString().split('T')[0],
            dayOfMonth: current.getDate(),
            dayName: current.toLocaleDateString('id-ID', { weekday: 'short' }),
            isWeekend: current.getDay() === 0 || current.getDay() === 6
        });
        current.setDate(current.getDate() + 1);
    }
    return days;
});

const recordsWithTeamIndex = computed(() => {
    if (!props.records) return [];
    
    let currentTeam = null;
    let teamCounter = 0;
    return props.records.map((rec) => {
        const team = rec.operator?.grup_shift || '—';
        if (team !== currentTeam) {
            currentTeam = team;
            teamCounter = 1;
        } else {
            teamCounter++;
        }
        return {
            ...rec,
            teamIndex: teamCounter
        };
    });
});

const formatDate = (dateStr) => {
    if (!dateStr) return '—';
    const date = new Date(dateStr);
    return date.toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'long',
        year: 'numeric'
    });
};

const getCodeStyle = (char) => {
    switch (char?.toUpperCase()) {
        case '1': return 'bg-sky-100 text-sky-800 border-sky-300 font-bold';
        case '2': return 'bg-blue-100 text-blue-800 border-blue-400 font-bold';
        case '3': return 'bg-indigo-100 text-indigo-900 border-indigo-500 font-bold';
        case 'H': return 'bg-gray-100 text-gray-500 border-gray-200 font-semibold';
        case 'L': return 'bg-rose-100 text-rose-800 border-rose-400 font-bold';
        case 'S': return 'bg-amber-100 text-amber-800 border-amber-400 font-bold';
        case 'I': return 'bg-orange-100 text-orange-800 border-orange-400 font-bold';
        case 'A': return 'bg-red-100 text-red-800 border-red-400 font-bold';
        default: return 'bg-white text-gray-200 border-gray-200';
    }
};

const getCodeBadgeStyle = (char) => {
    switch (char?.toUpperCase()) {
        case '1':
        case '2':
        case '3': return 'bg-slate-100 text-slate-800 border border-slate-300';
        case 'H': return 'bg-[#820509] text-white';
        case 'L': return 'bg-[#a83e42] text-white'; // LN
        case 'S': return 'bg-yellow-500 text-white';
        case 'I': return 'bg-orange-500 text-white';
        case 'A': return 'bg-red-500 text-white';
        case 'C': return 'bg-[#fce4d6] text-gray-800'; // CT
        case 'K': return 'bg-[#fce4d6] text-gray-800'; // CK
        default: return 'text-gray-300';
    }
};

const getReason = (record, dateStr) => {
    return record.keterangan_absensis?.find(k => k.tanggal === dateStr);
};

const triggerPrint = () => {
    window.print();
};

const getExportUrl = (type) => {
    const params = new URLSearchParams({
        periode_id: selectedPeriode.value,
        search: search.value,
        unit: unit.value,
        grup_shift: grup_shift.value,
        exceptions_only: exceptionsOnly.value ? 'true' : 'false',
    });
    return `/export/${type}?${params.toString()}`;
};

const isLiburNasional = (dateStr) => {
    return props.periode?.libur_nasional?.includes(dateStr) || false;
};
</script>

<template>
    <AppLayout>
        <template #title>Rekapitulasi Laporan Absensi</template>

        <!-- Print-only Title Header -->
        <div class="hidden print:block border-b-2 border-gray-800 pb-4 mb-6">
            <div class="flex justify-between items-end">
                <div>
                    <h1 class="text-xl font-bold text-gray-900 uppercase">Laporan Rekapitulasi Absensi Operator</h1>
                    <p class="text-xs text-gray-500 mt-1">Siklus Kerja: Tanggal 21 s/d 20</p>
                    <p class="text-sm font-semibold text-gray-800 mt-2">Periode: {{ periode?.label }}</p>
                </div>
                <div class="flex items-center gap-3 text-right text-xs">
                    <div>
                        <p class="font-bold">PT Borneo Alumina Indonesia (PT. BAI)</p>
                        <p>Unit Induk Wilayah</p>
                    </div>
                    <img src="/logo.png?v=2" alt="Logo PT. BAI" class="w-12 h-12 object-contain" />
                </div>
            </div>
        </div>

        <!-- Filter Toolbar -->
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 mb-6 flex flex-col gap-4 print:hidden">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
                <!-- Periode Selector -->
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Periode Laporan</label>
                    <select
                        v-model="selectedPeriode"
                        class="w-full bg-white border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm text-gray-800 focus:outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500 transition-all font-semibold"
                    >
                        <option v-for="p in periodes" :key="p.id" :value="p.id">
                            {{ p.label }} ({{ p.status }})
                        </option>
                    </select>
                </div>

                <!-- Unit Filter -->
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Unit Kerja</label>
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

                <!-- Grup Shift Filter -->
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Grup Shift</label>
                    <select
                        v-model="grup_shift"
                        class="w-full bg-white border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm text-gray-800 focus:outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500 transition-all"
                    >
                        <option value="">Semua Grup</option>
                        <option value="A">Grup A</option>
                        <option value="B">Grup B</option>
                        <option value="C">Grup C</option>
                        <option value="D">Grup D</option>
                    </select>
                </div>

                <!-- Search -->
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Cari Operator</label>
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Nama atau ID..."
                        class="w-full bg-white border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm text-gray-800 focus:outline-none focus:border-primary-500"
                    />
                </div>

                <!-- Exception toggle -->
                <div class="flex items-center h-11 pb-2">
                    <label class="inline-flex items-center gap-2 cursor-pointer select-none text-sm text-gray-700 font-semibold">
                        <input
                            v-model="exceptionsOnly"
                            type="checkbox"
                            class="rounded text-primary-600 focus:ring-primary-500 w-4 h-4 border-gray-300"
                        />
                        Hanya Sakit/Izin/Alpa
                    </label>
                </div>
            </div>

            <!-- Export Buttons -->
            <div class="flex flex-wrap gap-2 border-t border-gray-100 pt-4 justify-between items-center">
                <div class="text-xs text-gray-400">
                    Siklus Rentang: <span class="font-bold text-gray-600 font-mono">{{ formatDate(periode?.tgl_mulai) }}</span> s/d <span class="font-bold text-gray-600 font-mono">{{ formatDate(periode?.tgl_selesai) }}</span> ({{ periode?.total_hari }} Hari)
                </div>
                <div class="flex gap-2">
                    <a
                        :href="getExportUrl('excel')"
                        download
                        rel="external"
                        class="flex items-center gap-1.5 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200 px-4 py-2 rounded-xl text-xs font-semibold transition-all cursor-pointer"
                    >
                        <span class="material-symbols-outlined text-[16px]">file_download</span>
                        Export Excel
                    </a>
                    <a
                        :href="getExportUrl('pdf')"
                        class="flex items-center gap-1.5 bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200 px-4 py-2 rounded-xl text-xs font-semibold transition-all cursor-pointer"
                    >
                        <span class="material-symbols-outlined text-[16px]">picture_as_pdf</span>
                        Export PDF
                    </a>
                    <button
                        @click="triggerPrint"
                        class="flex items-center gap-1.5 bg-gray-50 text-gray-700 hover:bg-gray-100 border border-gray-200 px-4 py-2 rounded-xl text-xs font-semibold transition-all cursor-pointer"
                    >
                        <span class="material-symbols-outlined text-[16px]">print</span>
                        Cetak Laporan
                    </button>
                </div>
            </div>
        </div>

        <!-- Subtotals Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 print:hidden">
            <div v-for="(val, key) in subtotals" :key="key" class="bg-white border border-gray-100 rounded-2xl p-4 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs text-gray-400 font-semibold uppercase tracking-wider">Subtotal Shift {{ key }}</p>
                    <p class="text-xl font-bold text-gray-800 mt-1">{{ val.total_shift }} Shift</p>
                </div>
                <div class="text-right text-xs text-gray-400">
                    <p>{{ val.count }} Operator</p>
                    <p class="mt-0.5 font-mono text-[10px] bg-gray-50 px-1.5 py-0.5 rounded border">
                        S:{{ val.jml_sakit }} I:{{ val.jml_izin }} A:{{ val.jml_alpa }} LN:{{ val.jml_libur_nasional }} CT:{{ val.jml_cuti_biasa }} CK:{{ val.jml_cuti_khusus }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Main Report Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="w-full overflow-x-auto overflow-y-auto max-h-[70vh] custom-scrollbar">
                <table class="w-full min-w-max text-left border-collapse text-xs md:text-sm print:text-[9px] font-outfit whitespace-nowrap">
                    <thead>
                        <tr class="border-b border-gray-100 print:bg-transparent print:border-b-2 print:border-gray-800 text-center">
                            <th class="sticky top-0 lg:left-0 z-20 bg-gray-50 px-3 py-3.5 font-bold text-gray-700 print:text-black uppercase text-left w-12 min-w-[48px] max-w-[48px] text-[12px] md:text-[13px]">No</th>
                            <th class="sticky top-0 lg:left-[48px] z-20 bg-gray-50 px-3 py-3.5 font-bold text-gray-700 print:text-black uppercase text-left w-44 min-w-[176px] max-w-[176px] text-[12px] md:text-[13px]">Nama</th>
                            <th class="sticky top-0 lg:left-[224px] z-20 bg-gray-50 px-2 py-3.5 font-bold text-gray-700 print:text-black uppercase w-14 min-w-[56px] max-w-[56px] text-[12px] md:text-[13px] text-center">Tim</th>
                            <th class="sticky top-0 lg:left-[280px] z-20 bg-gray-50 px-3 py-3.5 font-bold text-gray-700 print:text-black uppercase text-left w-32 min-w-[128px] max-w-[128px] text-[12px] md:text-[13px]">Discipline</th>
                            <!-- Daily Columns -->
                            <th
                                v-for="day in calendarDays"
                                :key="day.dateStr"
                                :class="[
                                    'sticky top-0 z-10 px-1 py-3.5 font-extrabold w-10 min-w-[40px] max-w-[40px] border-x border-gray-100/50 text-[12px] md:text-[13px] text-center',
                                    isLiburNasional(day.dateStr) 
                                        ? 'bg-rose-50 text-rose-700 font-extrabold' 
                                        : (day.isWeekend ? 'bg-gray-50 text-gray-500' : 'bg-gray-50 text-gray-700')
                                ]"
                                :title="isLiburNasional(day.dateStr) ? 'Libur Nasional' : ''"
                            >
                                {{ day.dayOfMonth }}
                            </th>
                            <!-- Summary Columns -->
                            <th class="sticky top-0 z-10 bg-gray-50 px-1 py-3.5 font-bold text-emerald-600 print:text-black uppercase w-10 min-w-[40px] max-w-[40px] text-xs md:text-[13px]">S1</th>
                            <th class="sticky top-0 z-10 bg-gray-50 px-1 py-3.5 font-bold text-blue-600 print:text-black uppercase w-10 min-w-[40px] max-w-[40px] text-xs md:text-[13px]">S2</th>
                            <th class="sticky top-0 z-10 bg-gray-50 px-1 py-3.5 font-bold text-indigo-600 print:text-black uppercase w-10 min-w-[40px] max-w-[40px] text-xs md:text-[13px]">S3</th>
                            <th class="sticky top-0 z-10 bg-gray-50 px-1 py-3.5 font-bold text-gray-500 print:text-black uppercase w-10 min-w-[40px] max-w-[40px] text-xs md:text-[13px]">H</th>
                            <th class="sticky top-0 z-10 bg-gray-50 px-1 py-3.5 font-bold text-rose-600 print:text-black uppercase w-10 min-w-[40px] max-w-[40px] text-xs md:text-[13px]" title="Libur Nasional (Kode)">LN</th>
                            <th class="sticky top-0 z-10 bg-gray-50 px-1 py-3.5 font-extrabold text-primary-600 print:text-black uppercase w-12 min-w-[48px] max-w-[48px] text-xs md:text-[13px]" title="Masuk Shift">MS</th>
                            <th class="sticky top-0 z-10 bg-gray-50 px-1 py-3.5 font-extrabold text-amber-600 print:text-black uppercase w-12 min-w-[48px] max-w-[48px] text-xs md:text-[13px]" title="Libur Nasional Shift (Hari shift yang jatuh di LN)">LN(S)</th>
                            <th class="sticky top-0 z-10 bg-gray-50 px-1 py-3.5 font-extrabold text-primary-600 print:text-black uppercase w-12 min-w-[48px] max-w-[48px] text-xs md:text-[13px]" title="Hari Kerja Reguler">HK</th>
                            <th class="sticky top-0 z-10 bg-gray-50 px-1 py-3.5 font-extrabold text-primary-700 print:text-black uppercase w-20 min-w-[80px] max-w-[80px] text-xs md:text-[13px]" title="Jam Kerja">JK</th>
                            <th class="sticky top-0 z-10 bg-gray-50 px-1 py-3.5 font-extrabold text-teal-600 print:text-black uppercase w-20 min-w-[80px] max-w-[80px] text-xs md:text-[13px]" title="Selisih Jam Kerja vs 173">Selisih</th>
                            <th class="sticky top-0 z-10 bg-gray-50 px-1 py-3.5 font-bold text-amber-600 print:text-black uppercase w-10 min-w-[40px] max-w-[40px] text-xs md:text-[13px]">S</th>
                            <th class="sticky top-0 z-10 bg-gray-50 px-1 py-3.5 font-bold text-orange-600 print:text-black uppercase w-10 min-w-[40px] max-w-[40px] text-xs md:text-[13px]">I</th>
                            <th class="sticky top-0 z-10 bg-gray-50 px-1 py-3.5 font-bold text-red-600 print:text-black uppercase w-10 min-w-[40px] max-w-[40px] text-xs md:text-[13px]">A</th>
                            <th class="sticky top-0 z-10 bg-gray-50 px-1 py-3.5 font-bold text-purple-600 print:text-black uppercase w-10 min-w-[40px] max-w-[40px] text-xs md:text-[13px]">CT</th>
                            <th class="sticky top-0 z-10 bg-gray-50 px-1 py-3.5 font-bold text-pink-600 print:text-black uppercase w-10 min-w-[40px] max-w-[40px] text-xs md:text-[13px]">CK</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <template v-for="(rec, index) in recordsWithTeamIndex" :key="rec.id">
                            <!-- Team Separator Row -->
                            <tr v-if="index === 0 || rec.operator?.grup_shift !== recordsWithTeamIndex[index - 1].operator?.grup_shift" class="print:break-inside-avoid">
                                <td :colspan="calendarDays.length + 19" class="sticky left-0 z-10 bg-primary-50 border-y border-primary-100 px-5 py-3 text-left font-extrabold text-primary-800 uppercase tracking-wider text-xs md:text-sm print:bg-gray-100 print:text-black print:border-black font-outfit">
                                    Tim / Grup {{ rec.operator?.grup_shift || '—' }}
                                </td>
                            </tr>
                            <!-- Operator Row -->
                            <tr
                                @click="toggleRow(rec.id)"
                                class="group hover:bg-gray-50/50 transition-colors cursor-pointer select-none text-center font-outfit"
                            >
                                <td class="lg:sticky lg:left-0 z-10 bg-white group-hover:bg-gray-50/80 transition-colors px-3 py-3 font-medium text-gray-400 text-left w-12 min-w-[48px] max-w-[48px] truncate text-xs md:text-sm">{{ rec.teamIndex }}</td>
                                <td class="lg:sticky lg:left-[48px] z-10 bg-white group-hover:bg-gray-50/80 transition-colors px-3 py-3 font-bold text-gray-800 print:text-black text-left w-44 min-w-[176px] max-w-[176px] truncate text-xs md:text-sm" :title="rec.operator?.nama">{{ rec.operator?.nama }}</td>
                                <td class="lg:sticky lg:left-[224px] z-10 bg-white group-hover:bg-gray-50/80 transition-colors px-2 py-3 font-semibold text-gray-700 w-14 min-w-[56px] max-w-[56px] text-xs md:text-sm text-center">{{ rec.operator?.grup_shift }}</td>
                                <td class="lg:sticky lg:left-[280px] z-10 bg-white group-hover:bg-gray-50/80 transition-colors px-3 py-3 text-gray-600 text-left w-32 min-w-[128px] max-w-[128px] truncate text-xs md:text-sm" :title="rec.operator?.unit">{{ rec.operator?.unit }}</td>
                                
                                <!-- Daily Status Cells -->
                                <td
                                    v-for="day in calendarDays"
                                    :key="day.dateStr"
                                    :class="[
                                        'px-1 py-2 border-x border-gray-100/30 text-center w-10 min-w-[40px] max-w-[40px]',
                                        isLiburNasional(day.dateStr) ? 'bg-rose-50/30' : ''
                                    ]"
                                >
                                    <span
                                        v-if="rec.kode_string[calendarDays.indexOf(day)] && rec.kode_string[calendarDays.indexOf(day)] !== ' '"
                                        :class="[
                                            'inline-flex items-center justify-center w-7 h-7 rounded-lg font-extrabold text-xs md:text-sm cursor-help',
                                            getCodeBadgeStyle(rec.kode_string[calendarDays.indexOf(day)])
                                        ]"
                                        :title="getReason(rec, day.dateStr) ? `${day.dayOfMonth} ${periode?.label}: ${getReason(rec, day.dateStr).kode === 'C' ? 'CT' : (getReason(rec, day.dateStr).kode === 'K' ? 'CK' : (getReason(rec, day.dateStr).kode === 'L' ? 'LN' : getReason(rec, day.dateStr).kode))} - ${getReason(rec, day.dateStr).alasan}` : ''"
                                    >
                                        {{ rec.kode_string[calendarDays.indexOf(day)] === 'C' ? 'CT' : (rec.kode_string[calendarDays.indexOf(day)] === 'K' ? 'CK' : (rec.kode_string[calendarDays.indexOf(day)] === 'L' ? 'LN' : rec.kode_string[calendarDays.indexOf(day)])) }}
                                    </span>
                                    <span v-else class="text-gray-300 font-bold text-xs md:text-sm">-</span>
                                </td>
 
                                <!-- Summary Values -->
                                <td class="px-1 py-3 font-bold text-emerald-600 text-xs md:text-sm w-10 min-w-[40px] max-w-[40px] text-center">{{ rec.jml_shift1 }}</td>
                                <td class="px-1 py-3 font-bold text-blue-600 text-xs md:text-sm w-10 min-w-[40px] max-w-[40px] text-center">{{ rec.jml_shift2 }}</td>
                                <td class="px-1 py-3 font-bold text-indigo-600 text-xs md:text-sm w-10 min-w-[40px] max-w-[40px] text-center">{{ rec.jml_shift3 }}</td>
                                <td class="px-1 py-3 font-bold text-gray-500 text-xs md:text-sm w-10 min-w-[40px] max-w-[40px] text-center">{{ rec.jml_holiday }}</td>
                                <td class="px-1 py-3 font-bold text-xs md:text-sm w-10 min-w-[40px] max-w-[40px] text-center" :class="rec.jml_libur_nasional > 0 ? 'text-rose-600 bg-rose-50/30' : 'text-gray-400'">{{ rec.jml_libur_nasional }}</td>
                                <td class="px-1 py-3 font-extrabold text-gray-800 text-xs md:text-sm w-12 min-w-[48px] max-w-[48px] text-center">{{ rec.total_masuk }}</td>
                                <td class="px-1 py-3 font-extrabold text-gray-800 text-xs md:text-sm w-12 min-w-[48px] max-w-[48px] text-center">{{ rec.ln_count }}</td>
                                <td class="px-1 py-3 font-extrabold text-gray-800 text-xs md:text-sm w-12 min-w-[48px] max-w-[48px] text-center">{{ rec.hk_reguler }}</td>
                                <td class="px-1 py-3 font-extrabold text-gray-800 text-xs md:text-sm w-20 min-w-[80px] max-w-[80px] text-center">{{ rec.jam_kerja?.toFixed(1) }}</td>
                                <td class="px-1 py-3 font-extrabold text-xs md:text-sm w-20 min-w-[80px] max-w-[80px] text-center" :class="rec.selisih_jam >= 0 ? 'text-emerald-600' : 'text-rose-600'">
                                    {{ rec.selisih_jam >= 0 ? '+' : '' }}{{ rec.selisih_jam?.toFixed(1) }}
                                </td>
                                <td class="px-1 py-3 font-bold text-xs md:text-sm w-10 min-w-[40px] max-w-[40px] text-center" :class="rec.jml_sakit > 0 ? 'text-amber-600 bg-amber-50/30' : 'text-gray-400'">{{ rec.jml_sakit }}</td>
                                <td class="px-1 py-3 font-bold text-xs md:text-sm w-10 min-w-[40px] max-w-[40px] text-center" :class="rec.jml_izin > 0 ? 'text-orange-600 bg-orange-50/30' : 'text-gray-400'">{{ rec.jml_izin }}</td>
                                <td class="px-1 py-3 font-bold text-xs md:text-sm w-10 min-w-[40px] max-w-[40px] text-center" :class="rec.jml_alpa > 0 ? 'text-red-600 bg-red-50/30' : 'text-gray-400'">{{ rec.jml_alpa }}</td>
                                <td class="px-1 py-3 font-bold text-xs md:text-sm w-10 min-w-[40px] max-w-[40px] text-center" :class="rec.jml_cuti_biasa > 0 ? 'text-purple-600 bg-purple-50/30' : 'text-gray-400'">{{ rec.jml_cuti_biasa }}</td>
                                <td class="px-1 py-3 font-bold text-xs md:text-sm w-10 min-w-[40px] max-w-[40px] text-center" :class="rec.jml_cuti_khusus > 0 ? 'text-pink-600 bg-pink-50/30' : 'text-gray-400'">{{ rec.jml_cuti_khusus }}</td>
                            </tr>
 
                            <!-- Expanded Row: Calendar Details & Leave reasons -->
                            <tr v-if="expandedRows[rec.id]" class="bg-gray-50/50 print:bg-transparent">
                                <td :colspan="calendarDays.length + 19" class="px-6 py-4">
                                    <div class="space-y-3 text-left">
                                        <p class="font-semibold text-gray-600">Catatan Khusus (Sakit/Izin/Alpa/Cuti):</p>
                                        <div v-if="rec.keterangan_absensis?.length > 0">
                                            <ul class="space-y-1 text-xs">
                                                <li v-for="ket in rec.keterangan_absensis" :key="ket.id" class="flex gap-2">
                                                    <span class="font-semibold font-mono text-gray-600">{{ formatDate(ket.tanggal) }}:</span>
                                                    <span :class="['px-1.5 rounded text-[10px] font-bold border uppercase',
                                                        ket.kode === 'S' ? 'bg-amber-50 text-amber-700 border-amber-200' :
                                                        ket.kode === 'I' ? 'bg-orange-50 text-orange-700 border-orange-200' :
                                                        ket.kode === 'A' ? 'bg-red-50 text-red-700 border-red-200' :
                                                        ket.kode === 'C' ? 'bg-purple-50 text-purple-700 border-purple-200' :
                                                        ket.kode === 'L' ? 'bg-rose-50 text-rose-700 border-rose-200' :
                                                        'bg-pink-50 text-pink-700 border-pink-200'
                                                    ]">
                                                        {{ ket.kode === 'S' ? 'Sakit' : (ket.kode === 'I' ? 'Izin' : (ket.kode === 'A' ? 'Alpa' : (ket.kode === 'C' ? 'Cuti Biasa' : (ket.kode === 'L' ? 'Libur Nasional' : 'Cuti Khusus')))) }}
                                                    </span>
                                                    <span class="text-gray-700">— {{ ket.alasan }}</span>
                                                </li>
                                            </ul>
                                        </div>
                                        <p v-else class="text-xs text-gray-400 italic">Tidak ada catatan ketidakhadiran khusus di periode ini.</p>
                                        
                                        <div v-if="rec.keterangan" class="border-t border-gray-100 pt-2.5 mt-2">
                                            <p class="text-xs font-semibold text-gray-500">Keterangan Umum:</p>
                                            <p class="text-xs text-gray-700 mt-0.5">{{ rec.keterangan }}</p>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </template>
 
                        <!-- Grand Total Footer -->
                        <tr v-if="records.length > 0" class="bg-gray-100 border-t-2 border-gray-200 font-extrabold print:border-t-2 print:border-gray-800 text-center">
                            <td colspan="4" class="sticky left-0 z-10 bg-gray-100 px-4 py-3 uppercase tracking-wider text-gray-800 print:text-black text-left text-xs md:text-sm">Grand Total</td>
                            <td v-for="day in calendarDays" :key="day.dateStr" class="px-1 py-3"></td>
                            <td class="px-1 py-3 font-bold text-emerald-600 text-xs md:text-sm w-10 min-w-[40px] max-w-[40px] text-center">{{ grandTotal.jml_shift1 }}</td>
                            <td class="px-1 py-3 font-bold text-blue-600 text-xs md:text-sm w-10 min-w-[40px] max-w-[40px] text-center">{{ grandTotal.jml_shift2 }}</td>
                            <td class="px-1 py-3 font-bold text-indigo-600 text-xs md:text-sm w-10 min-w-[40px] max-w-[40px] text-center">{{ grandTotal.jml_shift3 }}</td>
                            <td class="px-1 py-3 font-bold text-gray-500 text-xs md:text-sm w-10 min-w-[40px] max-w-[40px] text-center">{{ grandTotal.jml_holiday }}</td>
                            <td class="px-1 py-3 font-bold text-rose-700 text-xs md:text-sm w-10 min-w-[40px] max-w-[40px] text-center">{{ grandTotal.jml_libur_nasional }}</td>
                            <td class="px-1 py-3 font-extrabold text-gray-800 text-xs md:text-sm w-12 min-w-[48px] max-w-[48px] text-center">{{ grandTotal.total_masuk }}</td>
                            <td class="px-1 py-3 font-extrabold text-gray-800 text-xs md:text-sm w-12 min-w-[48px] max-w-[48px] text-center">{{ grandTotal.ln_count }}</td>
                            <td class="px-1 py-3 font-extrabold text-gray-800 text-xs md:text-sm w-12 min-w-[48px] max-w-[48px] text-center">{{ grandTotal.hk_reguler }}</td>
                            <td class="px-1 py-3 font-extrabold text-gray-800 text-xs md:text-sm w-20 min-w-[80px] max-w-[80px] text-center">{{ grandTotal.jam_kerja?.toFixed(1) }}</td>
                            <td class="px-1 py-3 font-extrabold text-xs md:text-sm w-20 min-w-[80px] max-w-[80px] text-center" :class="grandTotal.selisih_jam >= 0 ? 'text-emerald-600' : 'text-rose-600'">
                                {{ grandTotal.selisih_jam >= 0 ? '+' : '' }}{{ grandTotal.selisih_jam?.toFixed(1) }}
                            </td>
                            <td class="px-1 py-3 font-bold text-amber-700 text-xs md:text-sm w-10 min-w-[40px] max-w-[40px] text-center">{{ grandTotal.jml_sakit }}</td>
                            <td class="px-1 py-3 font-bold text-orange-700 text-xs md:text-sm w-10 min-w-[40px] max-w-[40px] text-center">{{ grandTotal.jml_izin }}</td>
                            <td class="px-1 py-3 font-bold text-red-700 text-xs md:text-sm w-10 min-w-[40px] max-w-[40px] text-center">{{ grandTotal.jml_alpa }}</td>
                            <td class="px-1 py-3 font-bold text-purple-700 text-xs md:text-sm w-10 min-w-[40px] max-w-[40px] text-center">{{ grandTotal.jml_cuti_biasa }}</td>
                            <td class="px-1 py-3 font-bold text-pink-700 text-xs md:text-sm w-10 min-w-[40px] max-w-[40px] text-center">{{ grandTotal.jml_cuti_khusus }}</td>
                        </tr>
 
                        <!-- Empty State -->
                        <tr v-if="records.length === 0">
                            <td :colspan="calendarDays.length + 19" class="px-4 py-12 text-center text-gray-400">
                                <span class="material-symbols-outlined text-4xl mb-2 text-gray-300">summarize</span>
                                <p class="text-sm">Tidak ada rekap absensi yang cocok dengan filter.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Print-only Signature Footer -->
        <div class="hidden print:block mt-12">
            <div class="grid grid-cols-2 text-center text-xs">
                <div>
                    <p>Mengetahui,</p>
                    <p class="font-bold mt-1">Supervisor Operator</p>
                    <div class="h-16"></div>
                    <p class="underline font-bold">...............................................</p>
                    <p>NIP. .......................................</p>
                </div>
                <div>
                    <p>Dibuat oleh,</p>
                    <p class="font-bold mt-1">Admin Operator</p>
                    <div class="h-16"></div>
                    <p class="underline font-bold">...............................................</p>
                    <p>NIP. .......................................</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style>
/* Custom scrollbar for the table */
.custom-scrollbar::-webkit-scrollbar {
    height: 12px;
    width: 12px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: #f1f5f9; /* slate-100 */
    border-radius: 6px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #cbd5e1; /* slate-300 */
    border-radius: 6px;
    border: 3px solid #f1f5f9;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #94a3b8; /* slate-400 */
}

/* CSS styles for print mode */
@media print {
    body {
        background-color: transparent !important;
    }
    header, aside, footer, .print\:hidden {
        display: none !important;
    }
    .ml-64, .ml-56, .ml-\[72px\] {
        margin-left: 0 !important;
    }
    main {
        padding: 0 !important;
    }
    table {
        border-color: #000000 !important;
    }
    th, td {
        border-color: #e5e7eb !important;
    }
    .overflow-x-auto, .overflow-y-auto {
        overflow: visible !important;
        max-height: none !important;
    }
    th, td {
        position: static !important;
    }
}
</style>
