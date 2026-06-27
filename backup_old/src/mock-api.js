/**
 * MOCK-API.JS - Simulasi Backend Google Apps Script & Google Spreadsheet
 * 
 * Modul ini berjalan HANYA di lingkungan pengembangan lokal.
 * Menyediakan objek global `google.script.run` tiruan yang mengakses database lokal
 * di dalam localStorage browser.
 */

(function () {
  // Hanya jalankan jika tidak berjalan di server Google Apps Script
  if (typeof google !== 'undefined' && google.script && google.script.run) {
    console.log('Menjalankan di lingkungan Google Apps Script asli. Mock API dinonaktifkan.');
    return;
  }

  // --- 1. DATA SEEDER (Untuk Database Awal) ---
  const INDONESIAN_NAMES = [
    'Ahmad Subarjo', 'Budi Santoso', 'Cahyo Wibowo', 'Dedi Kurniawan', 'Eko Prasetyo',
    'Fajar Hidayat', 'Gunawan Wibisono', 'Hendra Wijaya', 'Irfan Bachdim', 'Joko Susilo',
    'Kusuma Wardhana', 'Lukman Hakim', 'Mulyono', 'Nugroho', 'Oki Setiawan',
    'Prabowo Subianto', 'Qomarudin', 'Rian Hidayat', 'Slamet Riyadi', 'Taufik Hidayat',
    'Untung Suropati', 'Vicky Prasetyo', 'Wahyu Hidayat', 'Xavier Yusuf', 'Yudiantara',
    'Zainal Abidin', 'Adi Wijaya', 'Bambang Pamungkas', 'Cecep Supriadi', 'Deni Setiawan',
    'Endang Setyowati', 'Ferry Irawan', 'Guntur Pratama', 'Herianto', 'Iwan Fals',
    'Joni Iskandar', 'Kiki Reski', 'Lutfi Alamsyah', 'Maman Abdurrahman', 'Nanang Kosasih',
    'Oman Abdurrahman', 'Pepen Effendi', 'Robby Darwis', 'Syarifuddin', 'Tatang Koswara',
    'Ujang Hermawan', 'Victor Igbonefo', 'Wawan Hermawan', 'Yayan Ruhian', 'Zulkifli Syukur',
    'Agus Salim', 'Bagir Manan', 'Chairul Tanjung', 'Dahlan Iskan', 'Emil Salim',
    'Fahri Hamzah', 'Gibran Rakabuming', 'Hatta Rajasa', 'Ignasius Jonan', 'Jusuf Kalla',
    'Khofifah Indar', 'Luhut Binsar', 'Mahfud MD', 'Nadiem Makarim', 'Oesman Sapta',
    'Pratikno', 'Retno Marsudi', 'Sandiaga Uno', 'Tito Karnavian', 'Wiranto',
    'Yasonna Laoly', 'Zulkifli Hasan', 'Anies Baswedan', 'Basuki Tjahaja', 'Cak Imin',
    'Djarot Saiful', 'Erick Thohir', 'Ganjar Pranowo', 'Hari Tanoe', 'Ical Bakrie',
    'Jokowi', 'Kaesang Pangarep', 'Megawati', 'Muhaimin Iskandar', 'Nasrudin Harahap',
    'Prabowo', 'Rizal Ramli', 'Setya Novanto', 'Taufiq Kiemas', 'Utut Adianto',
    'Valen Ronald', 'Wury Estu', 'Yenny Wahid', 'Yusril Ihza', 'Zainudin Amali',
    'Achmad Yani', 'Bung Tomo'
  ]; // Total 97 names

  const UNITS = ['BTG', 'C&AHS', 'Power Distribution'];
  const GROUPS = ['A', 'B', 'C', 'D'];

  function seedDatabase() {
    console.log('Membibitkan (seeding) database lokal di localStorage...');
    
    // 1. Seed Master Operator (97 Operator)
    const operators = [];
    INDONESIAN_NAMES.forEach((name, index) => {
      const id = 'OP' + String(index + 1).padStart(3, '0');
      // Distribusi unit
      const unit = UNITS[index % UNITS.length];
      // Distribusi grup
      const group = GROUPS[index % GROUPS.length];
      // Status (92 aktif, 5 nonaktif)
      const status = index < 92 ? 'Aktif' : 'Nonaktif';
      // Tanggal masuk (1-3 tahun yang lalu)
      const year = 2023 + (index % 3);
      const month = String((index % 12) + 1).padStart(2, '0');
      const date = String((index % 28) + 1).padStart(2, '0');
      
      operators.push({
        ID_Operator: id,
        Nama: name,
        Unit: unit,
        Grup_Shift: group,
        Status: status,
        Tanggal_Masuk: `${year}-${month}-${date}`,
        Keterangan: index % 10 === 0 ? 'Operator senior' : ''
      });
    });
    localStorage.setItem('db_master_operator', JSON.stringify(operators));

    // 2. Seed Periode
    const periods = [
      {
        Kode_Periode: 'APR2026',
        Label: 'April 2026',
        Tgl_Mulai: '2026-03-21',
        Tgl_Selesai: '2026-04-20',
        Total_Hari: 31,
        Status: 'Selesai'
      },
      {
        Kode_Periode: 'MAY2026',
        Label: 'Mei 2026',
        Tgl_Mulai: '2026-04-21',
        Tgl_Selesai: '2026-05-20',
        Total_Hari: 30,
        Status: 'Selesai'
      },
      {
        Kode_Periode: 'JUN2026',
        Label: 'Juni 2026',
        Tgl_Mulai: '2026-05-21',
        Tgl_Selesai: '2026-06-20',
        Total_Hari: 31,
        Status: 'Aktif'
      }
    ];
    localStorage.setItem('db_periode', JSON.stringify(periods));

    // 3. Seed Absensi & KeteranganAbsensi
    const absensi = [];
    const keterangan = [];
    
    // Kita isi absensi secara acak untuk periode MEI2026 dan JUNI2026
    periods.forEach(period => {
      operators.forEach((op, index) => {
        // Operator nonaktif tidak dicatat absensinya di periode berjalan, 
        // tapi ada di periode sebelumnya.
        if (op.Status === 'Nonaktif' && period.Kode_Periode === 'JUN2026') return;
        
        const idRecord = `${period.Kode_Periode}_${op.ID_Operator}`;
        const totalHari = period.Total_Hari;
        
        // Buat string kode absensi acak tapi masuk akal
        // Pola: umumnya shift 1, 2, 3, dan H (Holiday). Sesekali Sakit (S), Izin (I), Alpa (A)
        let codes = [];
        let jml1 = 0, jml2 = 0, jml3 = 0, jmlH = 0, jmlS = 0, jmlI = 0, jmlA = 0;
        
        for (let day = 1; day <= totalHari; day++) {
          const rand = Math.random();
          let code = 'H';
          
          if (rand < 0.22) {
            code = '1'; jml1++;
          } else if (rand < 0.44) {
            code = '2'; jml2++;
          } else if (rand < 0.66) {
            code = '3'; jml3++;
          } else if (rand < 0.95) {
            code = 'H'; jmlH++;
          } else if (rand < 0.97) {
            code = 'S'; jmlS++;
            // Catat keterangan sakit
            keterangan.push({
              ID: `KET_${idRecord}_${day}`,
              ID_Record: idRecord,
              Tanggal: getTanggalForDayIndex(period.Tgl_Mulai, day - 1),
              Kode: 'S',
              Alasan: 'Sakit demam/flu'
            });
          } else if (rand < 0.99) {
            code = 'I'; jmlI++;
            // Catat keterangan izin
            keterangan.push({
              ID: `KET_${idRecord}_${day}`,
              ID_Record: idRecord,
              Tanggal: getTanggalForDayIndex(period.Tgl_Mulai, day - 1),
              Kode: 'I',
              Alasan: 'Acara keluarga'
            });
          } else {
            code = 'A'; jmlA++;
            // Catat keterangan alpa
            keterangan.push({
              ID: `KET_${idRecord}_${day}`,
              ID_Record: idRecord,
              Tanggal: getTanggalForDayIndex(period.Tgl_Mulai, day - 1),
              Kode: 'A',
              Alasan: 'Tanpa keterangan'
            });
          }
          codes.push(code);
        }
        
        const kodeString = codes.join('');
        const totalShift = jml1 + jml2 + jml3;
        const totalHonorarium = totalShift * 8000;
        
        absensi.push({
          ID_Record: idRecord,
          Periode: period.Kode_Periode,
          Tgl_Mulai: period.Tgl_Mulai,
          Tgl_Selesai: period.Tgl_Selesai,
          ID_Operator: op.ID_Operator,
          Nama_Operator: op.Nama,
          Unit: op.Unit,
          Grup_Shift: op.Grup_Shift,
          Kode_String: kodeString,
          Jml_Shift1: jml1,
          Jml_Shift2: jml2,
          Jml_Shift3: jml3,
          Jml_Holiday: jmlH,
          Jml_Sakit: jmlS,
          Jml_Izin: jmlI,
          Jml_Alpa: jmlA,
          Total_Shift: totalShift,
          Total_Honorarium: totalHonorarium,
          Keterangan: jmlS > 0 ? 'Ada sakit' : (jmlI > 0 ? 'Ada izin' : (jmlA > 0 ? 'Ada alpa' : '')),
          Tgl_Update: new Date().toISOString()
        });
      });
    });

    localStorage.setItem('db_absensi', JSON.stringify(absensi));
    localStorage.setItem('db_keterangan', JSON.stringify(keterangan));
    console.log('Seeding database berhasil dilakukan.');
  }

  // Helper mendapatkan tanggal dari index hari
  function getTanggalForDayIndex(startDateStr, index) {
    const d = new Date(startDateStr);
    d.setDate(d.getDate() + index);
    return d.toISOString().split('T')[0];
  }

  // Cek apakah database sudah ada di localStorage, jika belum bibitkan
  if (!localStorage.getItem('db_master_operator')) {
    seedDatabase();
  }

  // --- 2. LOGIKA MOCK BACKEND (Simulasi Service.gs) ---
  const mockBackend = {
    // === 2.1 Get Dashboard Data ===
    getDashboardData: function () {
      const ops = JSON.parse(localStorage.getItem('db_master_operator') || '[]');
      const periods = JSON.parse(localStorage.getItem('db_periode') || '[]');
      const abs = JSON.parse(localStorage.getItem('db_absensi') || '[]');

      const activePeriod = periods.find(p => p.Status === 'Aktif') || periods[periods.length - 1];
      const activePeriodCode = activePeriod ? activePeriod.Kode_Periode : '';

      const totalOpsAktif = ops.filter(o => o.Status === 'Aktif').length;
      
      let totalShiftPeriod = 0;
      let totalHonorariumPeriod = 0;
      let opsDenganAlpa = 0;
      const recentUpdates = [];

      // Filter absensi di periode berjalan
      const currentAbsensi = abs.filter(a => a.Periode === activePeriodCode);
      
      currentAbsensi.forEach(a => {
        totalShiftPeriod += a.Total_Shift;
        totalHonorariumPeriod += a.Total_Honorarium;
        if (a.Jml_Alpa > 0) {
          opsDenganAlpa++;
        }
      });

      // Ambil 10 update terakhir dari absensi
      const sortedAbs = [...abs].sort((a, b) => new Date(b.Tgl_Update) - new Date(a.Tgl_Update));
      const recentList = sortedAbs.slice(0, 10);
      recentList.forEach(item => {
        recentUpdates.push({
          Nama: item.Nama_Operator,
          Unit: item.Unit,
          Periode: item.Periode,
          Tgl_Update: item.Tgl_Update,
          Total_Shift: item.Total_Shift
        });
      });

      // Data grafik bar: perbandingan shift per unit
      const shiftPerUnit = { 'BTG': 0, 'C&AHS': 0, 'Power Distribution': 0 };
      currentAbsensi.forEach(a => {
        if (shiftPerUnit[a.Unit] !== undefined) {
          shiftPerUnit[a.Unit] += a.Total_Shift;
        }
      });

      // Data grafik donut: sebaran kode absensi
      let total1 = 0, total2 = 0, total3 = 0, totalH = 0, totalS = 0, totalI = 0, totalA = 0;
      currentAbsensi.forEach(a => {
        total1 += a.Jml_Shift1;
        total2 += a.Jml_Shift2;
        total3 += a.Jml_Shift3;
        totalH += a.Jml_Holiday;
        totalS += a.Jml_Sakit;
        totalI += a.Jml_Izin;
        totalA += a.Jml_Alpa;
      });

      return {
        ActivePeriod: activePeriod,
        TotalOperatorAktif: totalOpsAktif,
        TotalShift: totalShiftPeriod,
        TotalHonorarium: totalHonorariumPeriod,
        OperatorAlpa: opsDenganAlpa,
        RecentUpdates: recentUpdates,
        ChartUnitData: [shiftPerUnit['BTG'], shiftPerUnit['C&AHS'], shiftPerUnit['Power Distribution']],
        ChartCodeData: [total1, total2, total3, totalH, totalS, totalI, totalA]
      };
    },

    // === 2.2 Master Operator ===
    getOperators: function () {
      return JSON.parse(localStorage.getItem('db_master_operator') || '[]');
    },

    saveOperator: function (opData) {
      const ops = JSON.parse(localStorage.getItem('db_master_operator') || '[]');
      
      // Validasi duplikasi nama dalam unit yang sama
      const duplicate = ops.find(o => 
        o.Nama.toLowerCase() === opData.Nama.toLowerCase() && 
        o.Unit === opData.Unit && 
        o.ID_Operator !== opData.ID_Operator
      );
      if (duplicate) {
        throw new Error(`Operator dengan nama "${opData.Nama}" di unit "${opData.Unit}" sudah terdaftar.`);
      }

      if (opData.ID_Operator) {
        // Edit Mode
        const idx = ops.findIndex(o => o.ID_Operator === opData.ID_Operator);
        if (idx !== -1) {
          ops[idx] = { ...ops[idx], ...opData };
        }
      } else {
        // Add Mode - auto ID
        const maxId = ops.reduce((max, o) => {
          const num = parseInt(o.ID_Operator.substring(2));
          return num > max ? num : max;
        }, 0);
        opData.ID_Operator = 'OP' + String(maxId + 1).padStart(3, '0');
        ops.push(opData);
      }

      localStorage.setItem('db_master_operator', JSON.stringify(ops));
      return { success: true, operator: opData };
    },

    deleteOperator: function (id) {
      // Sesuai PRD: Nonaktifkan (soft delete), tidak hapus fisik untuk jaga historis
      const ops = JSON.parse(localStorage.getItem('db_master_operator') || '[]');
      const idx = ops.findIndex(o => o.ID_Operator === id);
      if (idx !== -1) {
        ops[idx].Status = 'Nonaktif';
        localStorage.setItem('db_master_operator', JSON.stringify(ops));
        return { success: true, message: 'Operator berhasil dinonaktifkan.' };
      }
      throw new Error('Operator tidak ditemukan.');
    },

    // === 2.3 Absensi ===
    getAbsensi: function (periodeCode) {
      const abs = JSON.parse(localStorage.getItem('db_absensi') || '[]');
      const kets = JSON.parse(localStorage.getItem('db_keterangan') || '[]');
      
      const filteredAbs = abs.filter(a => a.Periode === periodeCode);
      
      // Hubungkan keterangan per record
      filteredAbs.forEach(record => {
        record.Keterangan_Detail = kets.filter(k => k.ID_Record === record.ID_Record);
      });

      return filteredAbs;
    },

    saveAbsensi: function (absData, keteranganList) {
      const abs = JSON.parse(localStorage.getItem('db_absensi') || '[]');
      let kets = JSON.parse(localStorage.getItem('db_keterangan') || '[]');

      const idRecord = absData.ID_Record;
      
      // Update / insert absensi record
      const idx = abs.findIndex(a => a.ID_Record === idRecord);
      absData.Tgl_Update = new Date().toISOString();
      
      if (idx !== -1) {
        abs[idx] = { ...abs[idx], ...absData };
      } else {
        abs.push(absData);
      }

      // Hapus keterangan lama untuk record ini, lalu simpan yang baru
      kets = kets.filter(k => k.ID_Record !== idRecord);
      
      if (keteranganList && keteranganList.length > 0) {
        keteranganList.forEach((k, i) => {
          if (!k.ID) {
            k.ID = `KET_${idRecord}_${k.Tanggal}_${i}`;
          }
          kets.push(k);
        });
      }

      localStorage.setItem('db_absensi', JSON.stringify(abs));
      localStorage.setItem('db_keterangan', JSON.stringify(kets));

      return { success: true };
    },

    saveAbsensiBulk: function (records, periodeCode) {
      const ops = JSON.parse(localStorage.getItem('db_master_operator') || '[]');
      const abs = JSON.parse(localStorage.getItem('db_absensi') || '[]');
      let kets = JSON.parse(localStorage.getItem('db_keterangan') || '[]');
      const periods = JSON.parse(localStorage.getItem('db_periode') || '[]');
      
      const period = periods.find(p => p.Kode_Periode === periodeCode);
      if (!period) throw new Error('Periode tidak ditemukan');

      let importCount = 0;

      records.forEach(row => {
        // Cari operator di master
        const op = ops.find(o => o.Nama.toLowerCase() === row.Nama_Operator.toLowerCase() && o.Unit === row.Unit);
        if (!op) return; // skip atau error handlenya di frontend sudah divalidasi

        const idRecord = `${periodeCode}_${op.ID_Operator}`;
        const codes = row.Kode_String.split('');
        
        let jml1 = 0, jml2 = 0, jml3 = 0, jmlH = 0, jmlS = 0, jmlI = 0, jmlA = 0;
        codes.forEach(c => {
          if (c === '1') jml1++;
          else if (c === '2') jml2++;
          else if (c === '3') jml3++;
          else if (c === 'H') jmlH++;
          else if (c === 'S') jmlS++;
          else if (c === 'I') jmlI++;
          else if (c === 'A') jmlA++;
        });

        const totalShift = jml1 + jml2 + jml3;
        const totalHonorarium = totalShift * 8000;

        const record = {
          ID_Record: idRecord,
          Periode: periodeCode,
          Tgl_Mulai: period.Tgl_Mulai,
          Tgl_Selesai: period.Tgl_Selesai,
          ID_Operator: op.ID_Operator,
          Nama_Operator: op.Nama,
          Unit: op.Unit,
          Grup_Shift: op.Grup_Shift,
          Kode_String: row.Kode_String,
          Jml_Shift1: jml1,
          Jml_Shift2: jml2,
          Jml_Shift3: jml3,
          Jml_Holiday: jmlH,
          Jml_Sakit: jmlS,
          Jml_Izin: jmlI,
          Jml_Alpa: jmlA,
          Total_Shift: totalShift,
          Total_Honorarium: totalHonorarium,
          Keterangan: row.Keterangan || '',
          Tgl_Update: new Date().toISOString()
        };

        // Update di database lokal
        const idx = abs.findIndex(a => a.ID_Record === idRecord);
        if (idx !== -1) {
          abs[idx] = record;
        } else {
          abs.push(record);
        }

        // Hapus keterangan lama untuk record ini
        kets = kets.filter(k => k.ID_Record !== idRecord);
        
        // Buat alasan dummy jika ada S/I/A tapi tidak ada keterangan tertulis
        if (jmlS > 0 || jmlI > 0 || jmlA > 0) {
          codes.forEach((c, i) => {
            if (c === 'S' || c === 'I' || c === 'A') {
              kets.push({
                ID: `KET_${idRecord}_${i}`,
                ID_Record: idRecord,
                Tanggal: getTanggalForDayIndex(period.Tgl_Mulai, i),
                Kode: c,
                Alasan: row.Keterangan || (c === 'S' ? 'Sakit' : (c === 'I' ? 'Izin' : 'Alpa'))
              });
            }
          });
        }

        importCount++;
      });

      localStorage.setItem('db_absensi', JSON.stringify(abs));
      localStorage.setItem('db_keterangan', JSON.stringify(kets));

      return { success: true, count: importCount };
    },

    // === 2.4 Periode ===
    getPeriode: function () {
      return JSON.parse(localStorage.getItem('db_periode') || '[]');
    },

    savePeriode: function (periodData) {
      const periods = JSON.parse(localStorage.getItem('db_periode') || '[]');
      
      // Jika statusnya Aktif, nonaktifkan periode aktif lainnya
      if (periodData.Status === 'Aktif') {
        periods.forEach(p => {
          if (p.Status === 'Aktif') p.Status = 'Selesai';
        });
      }

      const idx = periods.findIndex(p => p.Kode_Periode === periodData.Kode_Periode);
      if (idx !== -1) {
        periods[idx] = periodData;
      } else {
        periods.push(periodData);
      }

      localStorage.setItem('db_periode', JSON.stringify(periods));
      return { success: true, periode: periodData };
    }
  };

  // --- 3. PROXY HANDLER (Membuat google.script.run Bekerja) ---
  const runMock = {
    _successCallback: null,
    _failureCallback: null,
    
    withSuccessHandler: function (handler) {
      this._successCallback = handler;
      return this;
    },
    
    withFailureHandler: function (handler) {
      this._failureCallback = handler;
      return this;
    }
  };

  window.google = {
    script: {
      run: new Proxy(runMock, {
        get: function (target, prop) {
          // Jika memanggil chaining helper
          if (prop === 'withSuccessHandler' || prop === 'withFailureHandler') {
            return target[prop].bind(target);
          }

          // Kembalikan fungsi yang disimulasikan
          return function (...args) {
            const success = target._successCallback;
            const failure = target._failureCallback;

            // Bersihkan callback untuk pemanggilan berikutnya
            target._successCallback = null;
            target._failureCallback = null;

            // Simulasikan latency server Google (250ms)
            setTimeout(() => {
              try {
                if (typeof mockBackend[prop] === 'function') {
                  const result = mockBackend[prop](...args);
                  if (success) {
                    success(result);
                  }
                } else {
                  throw new Error(`Mock fungsi backend "${prop}" belum diimplementasi.`);
                }
              } catch (err) {
                console.error('GAS Mock Error:', err);
                if (failure) {
                  failure(err);
                }
              }
            }, 250);
          };
        }
      })
    }
  };

  console.log('Mock API Google Apps Script berhasil dimuat dan aktif.');
})();
