<script setup>
import { ref, watch, computed } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Bar, Doughnut } from 'vue-chartjs';
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement } from 'chart.js';

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale, ArcElement);

const props = defineProps({
    periodes: Array,
    selectedPeriodeId: Number,
    activePeriode: Object,
    stats: Object,
    chartUnitData: Object,
    chartCodeData: Object,
    recentUpdates: Array,
});

const selectedPeriode = ref(props.selectedPeriodeId || '');

watch(selectedPeriode, (newVal) => {
    if (newVal) {
        router.get('/', { periode_id: newVal }, { preserveState: true });
    }
});

// Bar Chart: Total Shift per Unit
const barChartData = computed(() => {
    return {
        labels: ['BTG', 'C&AHS', 'Power Distribution'],
        datasets: [
            {
                label: 'Total Shift Kerja',
                backgroundColor: ['#0060AF', '#4F46E5', '#8B5CF6'],
                borderRadius: 8,
                data: [
                    props.chartUnitData?.['BTG'] || 0,
                    props.chartUnitData?.['C&AHS'] || 0,
                    props.chartUnitData?.['Power Distribution'] || 0,
                ]
            }
        ]
    };
});

const barChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: false,
        }
    },
    scales: {
        y: {
            beginAtZero: true,
            grid: {
                color: '#f3f4f6'
            }
        },
        x: {
            grid: {
                display: false
            }
        }
    }
};

// Doughnut Chart: Distribution of Codes
const doughnutChartData = computed(() => {
    return {
        labels: ['Shift 1', 'Shift 2', 'Shift 3', 'Holiday', 'Sakit', 'Izin', 'Alpa', 'Cuti Biasa', 'Cuti Khusus'],
        datasets: [
            {
                backgroundColor: [
                    '#38bdf8', // s1
                    '#3b82f6', // s2
                    '#4f46e5', // s3
                    '#9ca3af', // h
                    '#fbbf24', // s
                    '#f97316', // i
                    '#ef4444', // a
                    '#a855f7', // ct (Cuti Biasa)
                    '#ec4899', // ck (Cuti Khusus)
                ],
                borderWidth: 2,
                borderColor: '#ffffff',
                data: [
                    props.chartCodeData?.['Shift 1'] || 0,
                    props.chartCodeData?.['Shift 2'] || 0,
                    props.chartCodeData?.['Shift 3'] || 0,
                    props.chartCodeData?.['Holiday'] || 0,
                    props.chartCodeData?.['Sakit'] || 0,
                    props.chartCodeData?.['Izin'] || 0,
                    props.chartCodeData?.['Alpa'] || 0,
                    props.chartCodeData?.['Cuti Biasa'] || 0,
                    props.chartCodeData?.['Cuti Khusus'] || 0,
                ]
            }
        ]
    };
});

const doughnutChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'right',
            labels: {
                boxWidth: 12,
                font: {
                    size: 11
                }
            }
        }
    },
    cutout: '65%'
};

const formatTimeAgo = (dateStr) => {
    if (!dateStr) return '—';
    const date = new Date(dateStr);
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    
    if (diffMins < 1) return 'Baru saja';
    if (diffMins < 60) return `${diffMins} menit lalu`;
    
    const diffHours = Math.floor(diffMins / 60);
    if (diffHours < 24) return `${diffHours} jam lalu`;
    
    return date.toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>

<template>
    <AppLayout>
        <template #title>Dashboard Statistik</template>

        <!-- Period Selector Toolbar -->
        <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 mb-6 flex flex-col sm:flex-row justify-between items-center gap-4">
            <div class="text-sm font-semibold text-gray-700 flex items-center gap-2">
                <span class="material-symbols-outlined text-primary-500">date_range</span>
                <span>Periode Tinjauan: <span class="text-primary-600 font-bold">{{ activePeriode?.label }}</span></span>
                <span class="text-xs text-gray-400 font-mono" v-if="activePeriode?.tgl_mulai">
                    ({{ new Date(activePeriode.tgl_mulai).toLocaleDateString('id-ID', {day: '2-digit', month: 'short'}) }} - {{ new Date(activePeriode.tgl_selesai).toLocaleDateString('id-ID', {day: '2-digit', month: 'short'}) }})
                </span>
            </div>
            <div class="flex items-center gap-3 w-full sm:w-auto justify-end">
                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider whitespace-nowrap">Pilih Periode:</label>
                <select
                    v-model="selectedPeriode"
                    class="bg-white border border-gray-200 rounded-xl px-3 py-2 text-sm text-gray-800 focus:outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500 transition-all font-semibold"
                >
                    <option v-for="p in periodes" :key="p.id" :value="p.id">
                        {{ p.label }} ({{ p.status }})
                    </option>
                </select>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-6">
            <!-- Operator Card -->
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-shadow duration-300">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 bg-primary-50 rounded-xl flex items-center justify-center">
                        <span class="material-symbols-outlined text-primary-500">groups</span>
                    </div>
                    <span class="text-xs font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-100">Aktif</span>
                </div>
                <p class="text-2xl font-bold text-gray-800">{{ stats?.total_operators || 0 }}</p>
                <p class="text-xs text-gray-400 mt-0.5">Total Operator Master</p>
            </div>

            <!-- Total Shift Card -->
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-shadow duration-300">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center">
                        <span class="material-symbols-outlined text-blue-500">work_history</span>
                    </div>
                    <span class="text-xs font-medium text-gray-500 bg-gray-100 px-2 py-0.5 rounded-full border border-gray-200">Siklus</span>
                </div>
                <p class="text-2xl font-bold text-gray-800">{{ stats?.total_shift || 0 }}</p>
                <p class="text-xs text-gray-400 mt-0.5">Total Shift Kerja Tercatat</p>
            </div>

            <!-- Sakit / Izin Card -->
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-shadow duration-300">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 bg-amber-50 rounded-xl flex items-center justify-center">
                        <span class="material-symbols-outlined text-amber-500">event_busy</span>
                    </div>
                    <span class="text-xs font-semibold text-amber-600 bg-amber-50 px-2 py-0.5 rounded-full border border-amber-100">Khusus</span>
                </div>
                <p class="text-2xl font-bold text-gray-800">{{ stats?.sakit_izin || 0 }}</p>
                <p class="text-xs text-gray-400 mt-0.5">Jumlah Sakit & Izin Hari</p>
            </div>

            <!-- Alpa Card -->
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-shadow duration-300">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-10 h-10 bg-red-50 rounded-xl flex items-center justify-center">
                        <span class="material-symbols-outlined text-red-500">person_off</span>
                    </div>
                    <span class="text-xs font-semibold text-red-600 bg-red-50 px-2 py-0.5 rounded-full border border-red-100">Alpa</span>
                </div>
                <p class="text-2xl font-bold text-red-600">{{ stats?.alpa || 0 }}</p>
                <p class="text-xs text-gray-400 mt-0.5">Total Mangkir/Alpa</p>
            </div>
        </div>

        <!-- Charts Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-6 mb-6">
            <!-- Shift per Unit -->
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 lg:col-span-3 flex flex-col h-[350px]">
                <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary-500">bar_chart</span>
                    Total Shift per Unit
                </h3>
                <div class="flex-1 relative">
                    <Bar :data="barChartData" :options="barChartOptions" />
                </div>
            </div>

            <!-- Distribution of Codes -->
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 lg:col-span-2 flex flex-col h-[350px]">
                <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary-500">pie_chart</span>
                    Distribusi Status Absensi
                </h3>
                <div class="flex-1 relative">
                    <Doughnut :data="doughnutChartData" :options="doughnutChartOptions" />
                </div>
            </div>
        </div>

        <!-- Row 3: Quick Actions & Recent Updates -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Quick Actions -->
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 space-y-4">
                <h3 class="font-bold text-gray-800 border-b border-gray-50 pb-3 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary-500">bolt</span>
                    Akses Cepat
                </h3>

                <div class="grid grid-cols-2 gap-3">
                    <Link
                        href="/absensi/entry"
                        class="flex flex-col items-center justify-center p-4 bg-blue-50/50 hover:bg-blue-50 border border-blue-100 rounded-2xl text-center transition-colors group cursor-pointer"
                    >
                        <span class="material-symbols-outlined text-blue-600 text-2xl group-hover:scale-110 transition-transform">edit_calendar</span>
                        <span class="text-xs font-semibold text-gray-700 mt-2">Entry Absensi</span>
                    </Link>

                    <Link
                        href="/rekap"
                        class="flex flex-col items-center justify-center p-4 bg-indigo-50/50 hover:bg-indigo-50 border border-indigo-100 rounded-2xl text-center transition-colors group cursor-pointer"
                    >
                        <span class="material-symbols-outlined text-indigo-600 text-2xl group-hover:scale-110 transition-transform">summarize</span>
                        <span class="text-xs font-semibold text-gray-700 mt-2">Rekap Laporan</span>
                    </Link>

                    <Link
                        href="/absensi/upload"
                        class="flex flex-col items-center justify-center p-4 bg-purple-50/50 hover:bg-purple-50 border border-purple-100 rounded-2xl text-center transition-colors group cursor-pointer"
                    >
                        <span class="material-symbols-outlined text-purple-600 text-2xl group-hover:scale-110 transition-transform">upload_file</span>
                        <span class="text-xs font-semibold text-gray-700 mt-2">Upload Bulk</span>
                    </Link>

                    <Link
                        href="/periode"
                        class="flex flex-col items-center justify-center p-4 bg-emerald-50/50 hover:bg-emerald-50 border border-emerald-100 rounded-2xl text-center transition-colors group cursor-pointer"
                    >
                        <span class="material-symbols-outlined text-emerald-600 text-2xl group-hover:scale-110 transition-transform">date_range</span>
                        <span class="text-xs font-semibold text-gray-700 mt-2">Ubah Periode</span>
                    </Link>
                </div>
            </div>

            <!-- Recent Updates -->
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 lg:col-span-2 flex flex-col">
                <h3 class="font-bold text-gray-800 border-b border-gray-50 pb-3 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary-500">history</span>
                    Aktivitas Entri Terbaru
                </h3>

                <div class="flex-1 overflow-x-auto mt-3">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-100">
                                <th class="px-4 py-2.5 font-semibold text-gray-500">Operator</th>
                                <th class="px-4 py-2.5 font-semibold text-gray-500">Unit / Grup</th>
                                <th class="px-4 py-2.5 font-semibold text-gray-500">Total Shift</th>
                                <th class="px-4 py-2.5 font-semibold text-gray-500">Diupdate</th>
                                <th class="px-4 py-2.5 font-semibold text-gray-500 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="up in recentUpdates" :key="up.id" class="hover:bg-gray-50/50">
                                <td class="px-4 py-3 flex items-center gap-2">
                                    <span class="font-mono text-gray-400 font-bold bg-gray-100 px-1.5 py-0.5 rounded text-[10px]">{{ up.operator?.id_operator }}</span>
                                    <span class="font-semibold text-gray-800">{{ up.operator?.nama }}</span>
                                </td>
                                <td class="px-4 py-3 text-gray-600">
                                    {{ up.operator?.unit }} / Grup {{ up.operator?.grup_shift }}
                                </td>
                                <td class="px-4 py-3 font-semibold text-primary-600">
                                    {{ up.total_shift }} Shift
                                </td>
                                <td class="px-4 py-3 text-gray-400">
                                    {{ formatTimeAgo(up.updated_at) }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <Link
                                        :href="`/absensi/entry?periode_id=${up.periode_id}&operator_id=${up.operator_id}`"
                                        class="text-primary-600 hover:text-primary-700 font-semibold cursor-pointer"
                                    >
                                        Edit
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="recentUpdates.length === 0">
                                <td colspan="5" class="px-4 py-8 text-center text-gray-400">
                                    Belum ada aktivitas pengisian absensi di periode ini.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
