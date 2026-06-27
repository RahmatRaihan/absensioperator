<script setup>
import { onMounted, computed } from 'vue';

const props = defineProps({
    periode: Object,
    records: Array,
    grandTotal: Object,
});

onMounted(() => {
    // Delay to make sure browser finished laying out elements before printing
    setTimeout(() => {
        window.print();
    }, 500);
});

// Generate calendar dates for print view
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

const getCodeBadgeStyle = (char) => {
    switch (char?.toUpperCase()) {
        case '1': return 'bg-emerald-500 text-white';
        case '2': return 'bg-blue-500 text-white';
        case '3': return 'bg-amber-500 text-white';
        case 'H': return 'bg-gray-400 text-white';
        case 'L': return 'bg-rose-500 text-white'; // LN
        case 'S': return 'bg-yellow-500 text-white';
        case 'I': return 'bg-orange-500 text-white';
        case 'A': return 'bg-red-500 text-white';
        case 'C': return 'bg-purple-500 text-white'; // CT
        case 'K': return 'bg-pink-500 text-white'; // CK
        default: return 'text-gray-300';
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

const isLiburNasional = (dateStr) => {
    return props.periode?.libur_nasional?.includes(dateStr) || false;
};
</script>

<template>
    <div class="min-h-screen bg-white p-8 max-w-5xl mx-auto text-black">
        <!-- Print Header -->
        <div class="border-b-2 border-black pb-4 mb-6 flex justify-between items-end">
            <div>
                <h1 class="text-xl font-bold uppercase">Laporan Rekapitulasi Absensi Operator</h1>
                <p class="text-xs text-gray-500 mt-1">Siklus Kerja: Tanggal 21 s/d 20</p>
                <p class="text-sm font-semibold mt-2">Periode: {{ periode?.label }}</p>
                <p class="text-xs text-gray-500">
                    Rentang: {{ formatDate(periode?.tgl_mulai) }} s/d {{ formatDate(periode?.tgl_selesai) }} ({{ periode?.total_hari }} Hari)
                </p>
            </div>
            <div class="flex items-center gap-3 text-right text-xs">
                <div>
                    <p class="font-bold text-sm">PT Borneo Alumina Indonesia (PT. BAI)</p>
                    <p>Unit Induk Wilayah</p>
                    <p>Sektor Pembangkitan</p>
                </div>
                <img src="/logo.png?v=2" alt="Logo PT. BAI" class="w-12 h-12 object-contain" />
            </div>
        </div>

        <!-- Table -->
        <table class="w-full text-left border-collapse text-[10px] border-2 border-black font-outfit">
            <thead>
                <tr class="bg-gray-100 border-b border-black font-bold text-center">
                    <th class="px-1 py-1 border border-black w-5 text-left">No</th>
                    <th class="px-1.5 py-1 border border-black text-left w-36">Nama</th>
                    <th class="px-1 py-1 border border-black w-8">Tim</th>
                    <th class="px-1.5 py-1 border border-black text-left w-24">Discipline</th>
                    <!-- Daily Columns -->
                    <th
                        v-for="day in calendarDays"
                        :key="day.dateStr"
                        :class="[
                            'px-0.5 py-1 border border-black w-5 text-[8px]',
                            isLiburNasional(day.dateStr) ? 'bg-rose-100 text-rose-900 font-bold' : ''
                        ]"
                    >
                        {{ day.dayOfMonth }}
                    </th>
                    <!-- Summary Columns -->
                    <th class="px-1 py-1 border border-black w-6">S1</th>
                    <th class="px-1 py-1 border border-black w-6">S2</th>
                    <th class="px-1 py-1 border border-black w-6">S3</th>
                    <th class="px-1 py-1 border border-black w-6">H</th>
                    <th class="px-1 py-1 border border-black w-6">LN</th>
                    <th class="px-1 py-1 border border-black w-8" title="Masuk Shift">MS</th>
                    <th class="px-1 py-1 border border-black w-8" title="Libur Nasional Shift">LN(S)</th>
                    <th class="px-1 py-1 border border-black w-8" title="Hari Kerja Reguler">HK</th>
                    <th class="px-1 py-1 border border-black w-8" title="Jam Kerja">JK</th>
                    <th class="px-1 py-1 border border-black w-10" title="Selisih">Selisih</th>
                    <th class="px-1 py-1 border border-black w-6">S</th>
                    <th class="px-1 py-1 border border-black w-6">I</th>
                    <th class="px-1 py-1 border border-black w-6">A</th>
                    <th class="px-1 py-1 border border-black w-6">CT</th>
                    <th class="px-1 py-1 border border-black w-6">CK</th>
                </tr>
            </thead>
            <tbody>
                <template v-for="(rec, index) in recordsWithTeamIndex" :key="rec.id">
                    <!-- Team Separator Row -->
                    <tr v-if="index === 0 || rec.operator?.grup_shift !== recordsWithTeamIndex[index - 1].operator?.grup_shift" class="print:break-inside-avoid">
                        <td :colspan="calendarDays.length + 19" class="px-3 py-1.5 border border-black text-left font-extrabold text-[10px] uppercase tracking-wider bg-gray-100 font-outfit">
                            Tim / Grup {{ rec.operator?.grup_shift || '—' }}
                        </td>
                    </tr>
                    <!-- Operator Row -->
                    <tr class="text-center">
                        <td class="px-1 py-1 border border-black text-left font-outfit">{{ rec.teamIndex }}</td>
                        <td class="px-1.5 py-1 border border-black text-left font-bold font-outfit">{{ rec.operator?.nama }}</td>
                    <td class="px-1 py-1 border border-black font-semibold">{{ rec.operator?.grup_shift }}</td>
                    <td class="px-1.5 py-1 border border-black text-left">{{ rec.operator?.unit }}</td>
                    
                    <!-- Daily Status Cells -->
                    <td
                        v-for="day in calendarDays"
                        :key="day.dateStr"
                        :class="[
                            'px-0.5 py-1 border border-black text-[7px]',
                            isLiburNasional(day.dateStr) ? 'bg-rose-50/50' : ''
                        ]"
                    >
                        <span
                            v-if="rec.kode_string[calendarDays.indexOf(day)] && rec.kode_string[calendarDays.indexOf(day)] !== ' '"
                            :class="[
                                'inline-flex items-center justify-center w-[18px] h-[18px] rounded font-bold text-[11px]',
                                getCodeBadgeStyle(rec.kode_string[calendarDays.indexOf(day)])
                            ]"
                        >
                            {{ rec.kode_string[calendarDays.indexOf(day)] === 'C' ? 'CT' : (rec.kode_string[calendarDays.indexOf(day)] === 'K' ? 'CK' : (rec.kode_string[calendarDays.indexOf(day)] === 'L' ? 'LN' : rec.kode_string[calendarDays.indexOf(day)])) }}
                        </span>
                        <span v-else class="text-gray-400 font-bold">-</span>
                    </td>

                    <!-- Summary Values -->
                    <td class="px-1 py-1 border border-black">{{ rec.jml_shift1 }}</td>
                    <td class="px-1 py-1 border border-black">{{ rec.jml_shift2 }}</td>
                    <td class="px-1 py-1 border border-black">{{ rec.jml_shift3 }}</td>
                    <td class="px-1 py-1 border border-black text-gray-500">{{ rec.jml_holiday }}</td>
                    <td class="px-1 py-1 border border-black" :class="{'text-red-600': rec.jml_libur_nasional > 0}">{{ rec.jml_libur_nasional }}</td>
                    <td class="px-1 py-1 border border-black font-bold">{{ rec.total_masuk }}</td>
                    <td class="px-1 py-1 border border-black font-bold">{{ rec.ln_count }}</td>
                    <td class="px-1 py-1 border border-black font-bold">{{ rec.hk_reguler }}</td>
                    <td class="px-1 py-1 border border-black font-bold">{{ rec.jam_kerja?.toFixed(2) }}</td>
                    <td class="px-1 py-1 border border-black font-bold" :class="rec.selisih_jam >= 0 ? 'text-emerald-600' : 'text-rose-600'">
                        {{ rec.selisih_jam >= 0 ? '+' : '' }}{{ rec.selisih_jam?.toFixed(2) }}
                    </td>
                    <td class="px-1 py-1 border border-black" :class="{'text-red-600': rec.jml_sakit > 0}">{{ rec.jml_sakit }}</td>
                    <td class="px-1 py-1 border border-black" :class="{'text-red-600': rec.jml_izin > 0}">{{ rec.jml_izin }}</td>
                    <td class="px-1 py-1 border border-black" :class="{'text-red-600': rec.jml_alpa > 0}">{{ rec.jml_alpa }}</td>
                    <td class="px-1 py-1 border border-black" :class="{'text-red-600': rec.jml_cuti_biasa > 0}">{{ rec.jml_cuti_biasa }}</td>
                    <td class="px-1 py-1 border border-black" :class="{'text-red-600': rec.jml_cuti_khusus > 0}">{{ rec.jml_cuti_khusus }}</td>
                </tr>
                </template>

                <!-- Grand Total -->
                <tr class="bg-gray-100 font-extrabold text-center border-t-2 border-black">
                    <td colspan="4" class="px-1.5 py-1.5 border border-black text-left uppercase">Grand Total</td>
                    <!-- Empty cells for daily columns -->
                    <td v-for="day in calendarDays" :key="day.dateStr" class="px-0.5 py-1.5 border border-black"></td>

                    <td class="px-1 py-1 border border-black">{{ grandTotal?.jml_shift1 }}</td>
                    <td class="px-1 py-1 border border-black">{{ grandTotal?.jml_shift2 }}</td>
                    <td class="px-1 py-1 border border-black">{{ grandTotal?.jml_shift3 }}</td>
                    <td class="px-1 py-1 border border-black text-gray-500">{{ grandTotal?.jml_holiday }}</td>
                    <td class="px-1 py-1 border border-black">{{ grandTotal?.jml_libur_nasional }}</td>
                    <td class="px-1 py-1 border border-black bg-gray-200">{{ grandTotal?.total_masuk }}</td>
                    <td class="px-1 py-1 border border-black bg-gray-200">{{ grandTotal?.ln_count }}</td>
                    <td class="px-1 py-1 border border-black bg-gray-200">{{ grandTotal?.hk_reguler }}</td>
                    <td class="px-1 py-1 border border-black bg-gray-200">{{ grandTotal?.jam_kerja?.toFixed(2) }}</td>
                    <td class="px-1 py-1 border border-black bg-gray-200 font-bold" :class="grandTotal?.selisih_jam >= 0 ? 'text-emerald-600' : 'text-rose-600'">
                        {{ grandTotal?.selisih_jam >= 0 ? '+' : '' }}{{ grandTotal?.selisih_jam?.toFixed(2) }}
                    </td>
                    <td class="px-1 py-1 border border-black">{{ grandTotal?.jml_sakit }}</td>
                    <td class="px-1 py-1 border border-black">{{ grandTotal?.jml_izin }}</td>
                    <td class="px-1 py-1 border border-black">{{ grandTotal?.jml_alpa }}</td>
                    <td class="px-1 py-1 border border-black">{{ grandTotal?.jml_cuti_biasa }}</td>
                    <td class="px-1 py-1 border border-black">{{ grandTotal?.jml_cuti_khusus }}</td>
                </tr>
            </tbody>
        </table>

        <!-- Signatures -->
        <div class="mt-12">
            <div class="grid grid-cols-2 text-center text-xs">
                <div>
                    <p>Mengetahui,</p>
                    <p class="font-bold mt-1">Supervisor Operator</p>
                    <div class="h-20"></div>
                    <p class="underline font-bold">...............................................</p>
                    <p>NIP. .......................................</p>
                </div>
                <div>
                    <p>Dibuat oleh,</p>
                    <p class="font-bold mt-1">Admin Operator</p>
                    <div class="h-20"></div>
                    <p class="underline font-bold">...............................................</p>
                    <p>NIP. .......................................</p>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
@media print {
    body {
        background-color: white !important;
        color: black !important;
    }
}
</style>
