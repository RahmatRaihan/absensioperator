<script setup>
import { ref, computed, watch, reactive } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    periodes: Array,
    operators: Array,
    selectedPeriodeId: Number,
    selectedOperatorId: Number,
    absensi: Object,
    calendarDays: Array,
    existingKeterangan: Object, // key: YYYY-MM-DD, value: { kode, alasan }
});

// Selectors
const periodeId = ref(props.selectedPeriodeId || '');
const operatorId = ref(props.selectedOperatorId || '');

// Input Mode: 'calendar' | 'string'
const inputMode = ref('calendar');

// Attendance string state
const kodeString = ref(props.absensi?.kode_string || '');
const generalKeterangan = ref(props.absensi?.keterangan || '');

// Reasons for S/I/A (Key: YYYY-MM-DD, Value: { kode: S|I|A, alasan: string })
const reasons = reactive({ ...props.existingKeterangan });

// Autosave Status: 'saved' | 'unsaved' | 'saving' | 'error'
const saveStatus = ref('saved');

// Reason Modal State
const isReasonModalOpen = ref(false);
const modalDate = ref('');
const modalCode = ref('');
const modalReasonText = ref('');

// Watch for drop-down selection changes and redirect
const handleSelectionChange = () => {
    if (periodeId.value && operatorId.value) {
        router.get('/absensi/entry', {
            periode_id: periodeId.value,
            operator_id: operatorId.value
        });
    }
};

// Debounce helper
const debounce = (fn, delay) => {
    let timeoutId;
    return (...args) => {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => fn(...args), delay);
    };
};

// Map code character to CSS classes
const getCodeStyle = (char) => {
    switch (char?.toUpperCase()) {
        case '1': return 'bg-sky-50 text-sky-800 border-sky-300 font-bold';
        case '2': return 'bg-blue-100 text-blue-800 border-blue-400 font-bold';
        case '3': return 'bg-indigo-200 text-indigo-900 border-indigo-600 font-bold';
        case 'H': return 'bg-gray-100 text-gray-500 border-gray-300 font-semibold';
        case 'L': return 'bg-rose-100 text-rose-800 border-rose-400 font-bold';
        case 'S': return 'bg-amber-100 text-amber-800 border-amber-400 font-bold';
        case 'I': return 'bg-orange-100 text-orange-800 border-orange-400 font-bold';
        case 'A': return 'bg-red-100 text-red-800 border-red-400 font-bold';
        case 'C': return 'bg-purple-100 text-purple-800 border-purple-400 font-bold';
        case 'K': return 'bg-pink-100 text-pink-800 border-pink-400 font-bold';
        default: return 'bg-white text-gray-300 border-gray-200';
    }
};

// Split string into array of single characters
const codeChars = computed({
    get: () => {
        if (!kodeString.value) return [];
        return kodeString.value.split('');
    },
    set: (val) => {
        kodeString.value = val.join('');
    }
});

// Live Stats Calculation (derived locally for immediate feedback)
const localStats = computed(() => {
    const chars = codeChars.value;
    let s1 = 0, s2 = 0, s3 = 0, h = 0, ln = 0, s = 0, i = 0, a = 0, ct = 0, ck = 0;
    
    chars.forEach(char => {
        switch (char?.toUpperCase()) {
            case '1': s1++; break;
            case '2': s2++; break;
            case '3': s3++; break;
            case 'H': h++; break;
            case 'L': ln++; break;
            case 'S': s++; break;
            case 'I': i++; break;
            case 'A': a++; break;
            case 'C': ct++; break;
            case 'K': ck++; break;
        }
    });

    const activePeriode = props.periodes.find(p => p.id === Number(periodeId.value)) || null;
    const lnDates = activePeriode?.libur_nasional || [];
    let lnCount = 0;

    if (lnDates.length > 0 && props.calendarDays) {
        props.calendarDays.forEach(day => {
            const char = chars[day.index];
            if (char && ['1', '2', '3'].includes(char)) {
                if (lnDates.includes(day.date)) {
                    lnCount++;
                }
            }
        });
    }

    const totalMasuk = s1 + s2 + s3;
    const hkReguler = totalMasuk - lnCount;
    const menitKerja = hkReguler * 460;
    const jamKerja = Math.round((menitKerja / 60) * 100) / 100;
    const selisihJam = Math.round((jamKerja - 173) * 100) / 100;

    return {
        jml_shift1: s1,
        jml_shift2: s2,
        jml_shift3: s3,
        jml_holiday: h,
        jml_libur_nasional: ln,
        jml_sakit: s,
        jml_izin: i,
        jml_alpa: a,
        jml_cuti_biasa: ct,
        jml_cuti_khusus: ck,
        total_shift: totalMasuk,
        total_masuk: totalMasuk,
        ln_count: lnCount,
        hk_reguler: hkReguler,
        jam_kerja: jamKerja,
        selisih_jam: selisihJam,
    };
});

// Update code character at index
const setCodeAt = (index, char) => {
    const chars = [...codeChars.value];
    
    // Ensure array is padded to length
    while (chars.length <= index) {
        chars.push(' ');
    }
    
    chars[index] = char;
    codeChars.value = chars;
    
    saveStatus.value = 'unsaved';

    // If code is S, I, A, C, or K, open the reason modal automatically
    const dateObj = props.calendarDays[index];
    if (dateObj && ['S', 'I', 'A', 'C', 'K', 'L'].includes(char.toUpperCase())) {
        openReasonModal(dateObj.date, char.toUpperCase());
    } else {
        // If changed away from S/I/A, remove reason
        if (dateObj && reasons[dateObj.date]) {
            delete reasons[dateObj.date];
        }
        triggerAutosave();
    }
};

// Handle reason modal
const openReasonModal = (dateStr, char) => {
    modalDate.value = dateStr;
    modalCode.value = char;
    modalReasonText.value = reasons[dateStr]?.alasan || '';
    isReasonModalOpen.value = true;
};

const saveReason = () => {
    if (modalReasonText.value.trim() === '') {
        alert('Alasan tidak boleh kosong untuk status khusus ini.');
        return;
    }
    reasons[modalDate.value] = {
        kode: modalCode.value,
        alasan: modalReasonText.value
    };
    isReasonModalOpen.value = false;
    triggerAutosave();
};

const cancelReason = () => {
    // If cancelling and no previous reason existed, maybe reset code back to holiday/shift 1
    if (!reasons[modalDate.value]) {
        // Find index of this date
        const index = props.calendarDays.findIndex(d => d.date === modalDate.value);
        if (index !== -1) {
            const isWeekend = props.calendarDays[index].is_weekend;
            // Reset character to 'H' or '1'
            const chars = [...codeChars.value];
            chars[index] = isWeekend ? 'H' : '1';
            codeChars.value = chars;
        }
    }
    isReasonModalOpen.value = false;
    saveStatus.value = 'unsaved';
    triggerAutosave();
};

// Perform Autosave API Call
const performAutosave = async () => {
    if (!props.selectedPeriodeId || !props.selectedOperatorId) return;
    
    saveStatus.value = 'saving';
    try {
        const response = await fetch('/absensi/entry/autosave', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                periode_id: props.selectedPeriodeId,
                operator_id: props.selectedOperatorId,
                kode_string: kodeString.value,
                keterangan: generalKeterangan.value,
                reasons: reasons,
            })
        });

        if (!response.ok) {
            throw new Error('Network response was not ok');
        }

        saveStatus.value = 'saved';
    } catch (err) {
        console.error(err);
        saveStatus.value = 'error';
    }
};

const triggerAutosave = debounce(performAutosave, 1500);

// Watchers for autosave
watch(generalKeterangan, () => {
    saveStatus.value = 'unsaved';
    triggerAutosave();
});

watch(kodeString, (newVal) => {
    // Validate characters on string mode typing
    if (inputMode.value === 'string') {
        const validated = newVal.toUpperCase().replace(/[^123HSIACKL]/g, '');
        if (validated !== newVal) {
            kodeString.value = validated;
        }
    }
    saveStatus.value = 'unsaved';
    triggerAutosave();
});

// Manual save as fallback / form submit
const manualSave = () => {
    router.post('/absensi/entry', {
        periode_id: props.selectedPeriodeId,
        operator_id: props.selectedOperatorId,
        kode_string: kodeString.value,
        keterangan: generalKeterangan.value,
        reasons: reasons,
    }, {
        onSuccess: () => {
            saveStatus.value = 'saved';
        }
    });
};

const formatDate = (dateStr) => {
    const date = new Date(dateStr);
    return date.toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
    });
};

// Searchable Operator Dropdown States & Functions
const searchOperatorQuery = ref('');
const isOperatorDropdownOpen = ref(false);

const filteredOperators = computed(() => {
    const query = searchOperatorQuery.value.toLowerCase().trim();
    if (!query) return props.operators;
    return props.operators.filter(op => 
        op.nama.toLowerCase().includes(query) || 
        op.id_operator.toLowerCase().includes(query) ||
        op.grup_shift.toLowerCase().includes(query) ||
        (op.npk && op.npk.toLowerCase().includes(query))
    );
});

const selectedOperator = computed(() => {
    return props.operators.find(op => op.id === operatorId.value) || null;
});

const operatorInputLabel = computed({
    get: () => {
        if (isOperatorDropdownOpen.value) {
            return searchOperatorQuery.value;
        }
        return selectedOperator.value 
            ? `${selectedOperator.value.npk ? selectedOperator.value.npk + ' - ' : ''}${selectedOperator.value.nama} (Grup ${selectedOperator.value.grup_shift})`
            : '';
    },
    set: (val) => {
        searchOperatorQuery.value = val;
    }
});

const handleOperatorInputFocus = () => {
    isOperatorDropdownOpen.value = true;
    searchOperatorQuery.value = '';
};

const handleOperatorInputBlur = () => {
    setTimeout(() => {
        isOperatorDropdownOpen.value = false;
    }, 200);
};

const selectOperator = (op) => {
    operatorId.value = op.id;
    isOperatorDropdownOpen.value = false;
    searchOperatorQuery.value = '';
    handleSelectionChange();
};

const isLiburNasional = (dateStr) => {
    const activePeriode = props.periodes.find(p => p.id === Number(periodeId.value)) || null;
    return activePeriode?.libur_nasional?.includes(dateStr) || false;
};
</script>

<template>
    <AppLayout>
        <template #title>Entry Absensi Harian</template>

        <!-- Selectors Card -->
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 mb-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 items-end">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Pilih Periode</label>
                    <select
                        v-model="periodeId"
                        @change="handleSelectionChange"
                        class="w-full bg-white border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm text-gray-800 focus:outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500 transition-all"
                    >
                        <option value="" disabled>Pilih Periode</option>
                        <option v-for="p in periodes" :key="p.id" :value="p.id">
                            {{ p.label }} ({{ p.status }})
                        </option>
                    </select>
                </div>

                <div class="relative">
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Pilih Operator</label>
                    <div class="relative">
                        <input
                            type="text"
                            v-model="operatorInputLabel"
                            @focus="handleOperatorInputFocus"
                            @blur="handleOperatorInputBlur"
                            placeholder="Cari Operator..."
                            class="w-full bg-white border border-gray-200 rounded-xl pl-3.5 pr-10 py-2.5 text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500 transition-all font-semibold"
                        />
                        <button 
                            type="button"
                            @click="isOperatorDropdownOpen = !isOperatorDropdownOpen"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors flex items-center justify-center cursor-pointer"
                        >
                            <span class="material-symbols-outlined transition-transform duration-200" :class="isOperatorDropdownOpen ? 'rotate-180' : ''">
                                keyboard_arrow_down
                            </span>
                        </button>
                        
                        <!-- Dropdown List -->
                        <transition
                            enter-active-class="transition ease-out duration-100"
                            enter-from-class="transform opacity-0 scale-95"
                            enter-to-class="transform opacity-100 scale-100"
                            leave-active-class="transition ease-in duration-75"
                            leave-from-class="transform opacity-100 scale-100"
                            leave-to-class="transform opacity-0 scale-95"
                        >
                            <div 
                                v-show="isOperatorDropdownOpen"
                                class="absolute left-0 right-0 z-50 mt-1 max-h-60 overflow-y-auto bg-white border border-gray-200 rounded-xl shadow-xl py-1 divide-y divide-gray-50"
                            >
                                <div 
                                    v-if="filteredOperators.length === 0" 
                                    class="px-4 py-3 text-xs text-gray-400 text-center"
                                >
                                    Tidak ada operator ditemukan
                                </div>
                                <div
                                    v-else
                                    v-for="op in filteredOperators"
                                    :key="op.id"
                                    @mousedown="selectOperator(op)"
                                    class="px-4 py-2.5 text-sm text-gray-700 hover:bg-primary-50 hover:text-primary-800 cursor-pointer flex items-center justify-between transition-colors text-left"
                                >
                                    <span class="flex items-center gap-2">
                                        <span v-if="op.npk" class="font-mono text-xs text-emerald-700 font-bold bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200">{{ op.npk }}</span>
                                        <span class="font-semibold">{{ op.nama }}</span>
                                    </span>
                                    <span class="text-xs text-gray-400 bg-gray-100 px-2 py-0.5 rounded border">Grup {{ op.grup_shift }}</span>
                                </div>
                            </div>
                        </transition>
                    </div>
                </div>

                <div v-if="selectedPeriodeId && selectedOperatorId" class="flex justify-end sm:col-span-2 lg:col-span-1">
                    <!-- Autosave Indicator -->
                    <div class="flex items-center gap-2 text-xs font-semibold px-3 py-1.5 rounded-full border bg-gray-50">
                        <span class="relative flex h-2 w-2">
                            <span :class="[
                                'animate-ping absolute inline-flex h-full w-full rounded-full opacity-75',
                                saveStatus === 'saved' ? 'bg-emerald-400' : (saveStatus === 'saving' ? 'bg-amber-400 animate-spin' : 'bg-red-400')
                            ]"></span>
                            <span :class="[
                                'relative inline-flex rounded-full h-2 w-2',
                                saveStatus === 'saved' ? 'bg-emerald-500' : (saveStatus === 'saving' ? 'bg-amber-500' : 'bg-red-500')
                            ]"></span>
                        </span>
                        <span :class="[
                            saveStatus === 'saved' ? 'text-emerald-700' : (saveStatus === 'saving' ? 'text-amber-700' : 'text-red-700')
                        ]">
                            {{ saveStatus === 'saved' ? 'Autosave: Tersimpan' : (saveStatus === 'saving' ? 'Menyimpan...' : 'Ada perubahan unsaved') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-if="!selectedPeriodeId || !selectedOperatorId" class="bg-white rounded-2xl p-12 shadow-sm border border-gray-100 text-center">
            <div class="w-16 h-16 bg-blue-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <span class="material-symbols-outlined text-primary-500 text-3xl">edit_calendar</span>
            </div>
            <h3 class="text-lg font-semibold text-gray-800 mb-2">Mulai Entry Data</h3>
            <p class="text-gray-400 max-w-sm mx-auto text-sm">
                Silakan pilih periode absensi dan nama operator di atas untuk membuka lembar entri absensi interaktif.
            </p>
        </div>

        <!-- Interactive Sheet -->
        <div v-else class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <!-- Left Side: Interactive Calendar / String input -->
            <div class="lg:col-span-3 space-y-6">
                <!-- Modes Tabs -->
                <div class="bg-white rounded-2xl p-2.5 shadow-sm border border-gray-100 flex gap-2">
                    <button
                        @click="inputMode = 'calendar'"
                        :class="[
                            'flex-1 flex items-center justify-center gap-2 py-2 rounded-xl text-sm font-semibold transition-all cursor-pointer',
                            inputMode === 'calendar' ? 'bg-primary-500 text-white shadow-sm' : 'text-gray-500 hover:bg-gray-50'
                        ]"
                    >
                        <span class="material-symbols-outlined text-[18px]">calendar_view_month</span>
                        Mode Kalender Interaktif
                    </button>
                    <button
                        @click="inputMode = 'string'"
                        :class="[
                            'flex-1 flex items-center justify-center gap-2 py-2 rounded-xl text-sm font-semibold transition-all cursor-pointer',
                            inputMode === 'string' ? 'bg-primary-500 text-white shadow-sm' : 'text-gray-500 hover:bg-gray-50'
                        ]"
                    >
                        <span class="material-symbols-outlined text-[18px]">notes</span>
                        Mode Kode String Absensi
                    </button>
                </div>

                <!-- Calendar Mode -->
                <div v-if="inputMode === 'calendar'" class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                    <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary-500">grid_on</span>
                        Grid Kalender Hari
                    </h3>

                    <!-- Calendar Grid -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-7 gap-3">
                        <div
                            v-for="day in calendarDays"
                            :key="day.date"
                            :class="[
                                'border rounded-xl p-3 flex flex-col justify-between h-24 hover:shadow-md transition-shadow relative group',
                                isLiburNasional(day.date)
                                    ? 'bg-rose-50/60 border-rose-200'
                                    : (day.is_weekend ? 'bg-gray-50 border-gray-200' : 'bg-white border-gray-100')
                            ]"
                        >
                            <!-- Date Label -->
                            <div class="flex items-center justify-between">
                                <span :class="['text-xs font-semibold font-mono flex items-center gap-1', isLiburNasional(day.date) ? 'text-rose-700 font-bold' : 'text-gray-400']">
                                    {{ formatDate(day.date) }}
                                    <span v-if="isLiburNasional(day.date)" class="text-[9px] bg-rose-100 text-rose-800 px-1 py-0.5 rounded font-sans font-bold">Libur</span>
                                </span>
                                <span :class="['text-[10px] px-1.5 py-0.5 rounded font-bold uppercase tracking-wider', day.is_weekend ? 'text-gray-400 bg-gray-200' : 'text-gray-500 bg-gray-100']">
                                    {{ day.day_name.substring(0, 3) }}
                                </span>
                            </div>

                            <!-- Selector Dropdown -->
                            <select
                                :value="codeChars[day.index] || 'H'"
                                @change="setCodeAt(day.index, $event.target.value)"
                                :class="[
                                    'w-full text-center border rounded-lg text-sm py-1.5 focus:outline-none transition-colors cursor-pointer',
                                    getCodeStyle(codeChars[day.index])
                                ]"
                            >
                                <option value="1">1 (Shift 1)</option>
                                <option value="2">2 (Shift 2)</option>
                                <option value="3">3 (Shift 3)</option>
                                <option value="H">H (Off/Holiday)</option>
                                <option value="L">LN (Libur Nasional)</option>
                                <option value="S">S (Sakit)</option>
                                <option value="I">I (Izin)</option>
                                <option value="A">A (Alpa)</option>
                                <option value="C">CT (Cuti Biasa)</option>
                                <option value="K">CK (Cuti Khusus)</option>
                            </select>

                            <!-- Reason Icon for S/I/A/C/K -->
                            <div
                                v-if="['S', 'I', 'A', 'C', 'K', 'L'].includes((codeChars[day.index] || '').toUpperCase())"
                                @click="openReasonModal(day.date, codeChars[day.index].toUpperCase())"
                                class="absolute top-1.5 right-1.5 w-4 h-4 bg-amber-500 hover:bg-amber-600 text-white rounded-full flex items-center justify-center cursor-pointer shadow-sm"
                                :title="reasons[day.date]?.alasan || 'Masukkan alasan...'"
                            >
                                <span class="material-symbols-outlined text-[10px]">info</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- String Mode -->
                <div v-else class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="font-bold text-gray-800 flex items-center gap-2">
                            <span class="material-symbols-outlined text-primary-500">description</span>
                            Input String Absensi Massal
                        </h3>
                        <span class="text-xs text-gray-400 font-mono">Panjang string: {{ kodeString.length }} / {{ calendarDays.length }} karakter</span>
                    </div>

                    <p class="text-xs text-gray-400 leading-relaxed">
                        Masukkan kode absensi dalam format string panjang. Setiap karakter mewakili satu hari. Karakter yang diperbolehkan: <code class="font-bold text-gray-700 bg-gray-100 px-1 py-0.5 rounded font-mono">1</code>, <code class="font-bold text-gray-700 bg-gray-100 px-1 py-0.5 rounded font-mono">2</code>, <code class="font-bold text-gray-700 bg-gray-100 px-1 py-0.5 rounded font-mono">3</code>, <code class="font-bold text-gray-700 bg-gray-100 px-1 py-0.5 rounded font-mono">H</code>, <code class="font-bold text-gray-700 bg-gray-100 px-1 py-0.5 rounded font-mono">L</code> (Libur Nasional), <code class="font-bold text-gray-700 bg-gray-100 px-1 py-0.5 rounded font-mono">S</code>, <code class="font-bold text-gray-700 bg-gray-100 px-1 py-0.5 rounded font-mono">I</code>, <code class="font-bold text-gray-700 bg-gray-100 px-1 py-0.5 rounded font-mono">A</code>, <code class="font-bold text-gray-700 bg-gray-100 px-1 py-0.5 rounded font-mono">C</code> (Cuti Biasa), <code class="font-bold text-gray-700 bg-gray-100 px-1 py-0.5 rounded font-mono">K</code> (Cuti Khusus).
                    </p>

                    <textarea
                        v-model="kodeString"
                        rows="3"
                        class="w-full bg-white border border-gray-200 rounded-xl p-4 font-mono text-lg tracking-widest uppercase focus:outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500 transition-all uppercase resize-none"
                        placeholder="e.g. 11111HH22222HHS33333HHH111HHH"
                        :maxlength="calendarDays.length"
                    ></textarea>

                    <div v-if="kodeString.length !== calendarDays.length" class="bg-amber-50 border border-amber-100 rounded-xl p-3 flex items-center gap-2 text-xs text-amber-800">
                        <span class="material-symbols-outlined text-[16px] flex-shrink-0">warning</span>
                        <span>Panjang string ({{ kodeString.length }} karakter) tidak cocok dengan rentang hari periode ({{ calendarDays.length }} hari). Silakan lengkapi atau ubah.</span>
                    </div>
                </div>

                <!-- Reasons Log Table -->
                <div v-if="Object.keys(reasons).length > 0" class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                    <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                        <span class="material-symbols-outlined text-amber-500">assignment_late</span>
                        Catatan Alasan Ketidakhadiran (S/I/A/C/K/LN)
                    </h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-100">
                                    <th class="px-4 py-3 font-semibold text-gray-500 uppercase tracking-wider">Tanggal</th>
                                    <th class="px-4 py-3 font-semibold text-gray-500 uppercase tracking-wider">Jenis</th>
                                    <th class="px-4 py-3 font-semibold text-gray-500 uppercase tracking-wider">Keterangan / Alasan</th>
                                    <th class="px-4 py-3 font-semibold text-gray-500 uppercase tracking-wider text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="(val, dateKey) in reasons" :key="dateKey" class="hover:bg-gray-50/50">
                                    <td class="px-4 py-3 font-mono font-semibold text-gray-600">{{ formatDate(dateKey) }}</td>
                                    <td class="px-4 py-3">
                                        <span :class="[
                                            'px-2 py-0.5 rounded text-[10px] font-bold border uppercase',
                                            val.kode === 'S' ? 'bg-amber-50 text-amber-700 border-amber-200' :
                                            val.kode === 'I' ? 'bg-orange-50 text-orange-700 border-orange-200' :
                                            val.kode === 'A' ? 'bg-red-50 text-red-700 border-red-200' :
                                            val.kode === 'C' ? 'bg-purple-50 text-purple-700 border-purple-200' :
                                            val.kode === 'L' ? 'bg-rose-50 text-rose-700 border-rose-200' :
                                            'bg-pink-50 text-pink-700 border-pink-200'
                                        ]">
                                            {{ val.kode === 'S' ? 'Sakit' : (val.kode === 'I' ? 'Izin' : (val.kode === 'A' ? 'Alpa' : (val.kode === 'C' ? 'Cuti Biasa' : (val.kode === 'L' ? 'Libur Nasional' : 'Cuti Khusus')))) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-700 font-medium">{{ val.alasan }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <button
                                            @click="openReasonModal(dateKey, val.kode)"
                                            class="text-primary-600 hover:text-primary-700 font-semibold cursor-pointer"
                                        >
                                            Edit Alasan
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Right Side: Stats Summary & Action Buttons -->
            <div class="space-y-6">
                <!-- Statistics Card -->
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 space-y-4">
                    <h3 class="font-bold text-gray-800 flex items-center gap-2 border-b border-gray-50 pb-3">
                        <span class="material-symbols-outlined text-primary-500">analytics</span>
                        Ringkasan Absensi
                    </h3>

                    <!-- Stats Values -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500 flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded bg-sky-100 border border-sky-300"></span>
                                Shift 1 (Pagi)
                            </span>
                            <span class="font-bold text-gray-800">{{ localStats.jml_shift1 }} hari</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500 flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded bg-blue-200 border border-blue-400"></span>
                                Shift 2 (Siang)
                            </span>
                            <span class="font-bold text-gray-800">{{ localStats.jml_shift2 }} hari</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500 flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded bg-indigo-300 border border-indigo-500"></span>
                                Shift 3 (Malam)
                            </span>
                            <span class="font-bold text-gray-800">{{ localStats.jml_shift3 }} hari</span>
                        </div>
                        <div class="flex items-center justify-between text-sm border-t border-gray-50 pt-2.5">
                            <span class="font-semibold text-gray-700 flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-[16px] text-gray-400">work</span>
                                Total Masuk Shift (MS)
                            </span>
                            <span class="font-extrabold text-primary-600">{{ localStats.total_masuk }} Hari</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500 flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded bg-amber-500"></span>
                                Libur Nasional (LN)
                            </span>
                            <span class="font-bold text-gray-800">{{ localStats.ln_count }} Hari</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500 flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded bg-emerald-500"></span>
                                Hari Kerja Reguler (HK)
                            </span>
                            <span class="font-bold text-gray-800">{{ localStats.hk_reguler }} Hari</span>
                        </div>
                        <div class="flex items-center justify-between text-sm border-t border-gray-50 pt-2.5">
                            <span class="font-semibold text-gray-700 flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-[16px] text-gray-400">schedule</span>
                                Total Jam Kerja (JK)
                            </span>
                            <span class="font-extrabold text-gray-850">{{ localStats.jam_kerja }} Jam</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-semibold text-gray-700 flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-[16px] text-gray-400">compare_arrows</span>
                                Selisih Jam Kerja
                            </span>
                            <span :class="['font-extrabold', localStats.selisih_jam >= 0 ? 'text-emerald-600' : 'text-rose-600']">
                                {{ localStats.selisih_jam >= 0 ? '+' : '' }}{{ localStats.selisih_jam }} Jam
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-sm border-t border-gray-50 pt-2.5">
                            <span class="text-gray-500 flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded bg-gray-200 border border-gray-400"></span>
                                Hari Libur (H)
                            </span>
                            <span class="font-bold text-gray-800">{{ localStats.jml_holiday }} hari</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500 flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded bg-rose-200 border border-rose-400"></span>
                                Libur Nasional (LN)
                            </span>
                            <span class="font-bold text-gray-800">{{ localStats.jml_libur_nasional }} hari</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500 flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded bg-amber-200 border border-amber-400"></span>
                                Sakit (S)
                            </span>
                            <span class="font-bold text-gray-800">{{ localStats.jml_sakit }} hari</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500 flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded bg-orange-200 border border-orange-400"></span>
                                Izin (I)
                            </span>
                            <span class="font-bold text-gray-800">{{ localStats.jml_izin }} hari</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500 flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded bg-red-200 border border-red-400"></span>
                                Mangkir/Alpa (A)
                            </span>
                            <span class="font-bold text-red-600">{{ localStats.jml_alpa }} hari</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500 flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded bg-purple-200 border border-purple-400"></span>
                                Cuti Biasa (CT)
                            </span>
                            <span class="font-bold text-gray-800">{{ localStats.jml_cuti_biasa }} hari</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500 flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded bg-pink-200 border border-pink-400"></span>
                                Cuti Khusus (CK)
                            </span>
                            <span class="font-bold text-gray-800">{{ localStats.jml_cuti_khusus }} hari</span>
                        </div>
                    </div>
                </div>

                <!-- Notes Card & Action -->
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Catatan Umum</label>
                        <textarea
                            v-model="generalKeterangan"
                            placeholder="Catatan umum tentang absensi..."
                            rows="4"
                            class="w-full bg-white border border-gray-200 rounded-xl px-3 py-2 text-sm text-gray-800 focus:outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500 transition-all resize-none text-xs"
                        ></textarea>
                    </div>

                    <div class="space-y-2">
                        <button
                            @click="manualSave"
                            :disabled="kodeString.length !== calendarDays.length"
                            class="w-full bg-primary-600 hover:bg-primary-700 text-white py-3 rounded-xl text-sm font-semibold transition-all shadow-md shadow-primary-500/10 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                        >
                            <span class="material-symbols-outlined text-sm">save</span>
                            Simpan Absensi
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alasan Modal (Popup for S/I/A) -->
        <transition
            enter-active-class="transition-opacity duration-250 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="isReasonModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
                <div class="bg-white rounded-2xl w-full max-w-md overflow-hidden shadow-2xl border border-gray-100">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                        <h3 class="font-bold text-gray-800 flex items-center gap-2">
                            <span class="material-symbols-outlined text-amber-500">assignment_late</span>
                            Alasan Ketidakhadiran
                        </h3>
                    </div>

                    <div class="p-6 space-y-4">
                        <div class="text-sm text-gray-600">
                            Operator ditandai dengan status <strong class="text-primary-600">
                                {{ modalCode === 'S' ? 'Sakit (S)' : (modalCode === 'I' ? 'Izin (I)' : (modalCode === 'A' ? 'Alpa (A)' : (modalCode === 'C' ? 'Cuti Biasa (CT)' : (modalCode === 'L' ? 'Libur Nasional (LN)' : 'Cuti Khusus (CK)')))) }}
                            </strong> pada tanggal <strong class="text-gray-800 font-mono">{{ formatDate(modalDate) }}</strong>. Silakan masukkan alasan/keterangan:
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Alasan / Catatan Alasan</label>
                            <textarea
                                v-model="modalReasonText"
                                placeholder="Masukkan alasan ketidakhadiran operator (e.g. Surat Dokter, Izin Keluarga)..."
                                rows="3"
                                class="w-full bg-white border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm text-gray-800 focus:outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500 transition-all resize-none"
                            ></textarea>
                        </div>

                        <div class="pt-4 flex justify-end gap-3 border-t border-gray-100">
                            <button
                                type="button"
                                @click="cancelReason"
                                class="px-4 py-2 text-sm font-semibold text-gray-500 hover:text-gray-700 hover:bg-gray-50 rounded-xl transition-all cursor-pointer"
                            >
                                Batal
                            </button>
                            <button
                                type="button"
                                @click="saveReason"
                                class="bg-primary-600 hover:bg-primary-700 text-white px-5 py-2 rounded-xl text-sm font-semibold transition-all shadow-md shadow-primary-500/10 cursor-pointer"
                            >
                                Simpan Keterangan
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </transition>
    </AppLayout>
</template>
