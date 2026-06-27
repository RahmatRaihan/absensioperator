# PRD: Aplikasi Rekapitulasi Absensi Operator
**Product Requirements Document — v1.0**
**Tanggal Dibuat:** 3 Juni 2026
**Platform:** Google Apps Script (GAS) + Google Spreadsheet
**Status:** Draft

---

## 1. Ringkasan Produk

Aplikasi web berbasis Google Apps Script untuk mengelola, merekap, dan melaporkan data absensi operator secara digital. Aplikasi ini menggantikan proses pencatatan manual dan mempermudah HR/Admin dalam melakukan entry data, melihat rekap per periode, serta mengekspor laporan ke berbagai format.

### 1.1 Tujuan
- Digitalisasi rekap absensi operator yang sebelumnya dilakukan manual
- Mempercepat proses perhitungan total shift dan honorarium
- Menyediakan laporan yang dapat diekspor ke Excel, PDF, dan Print

### 1.2 Pengguna Aplikasi
| Role | Akses |
|------|-------|
| Admin / HR | Full access: entry, edit, hapus, lihat semua data, export |

> Tidak memerlukan sistem autentikasi/login. Aplikasi diakses langsung via URL Google Apps Script.

---

## 2. Ruang Lingkup (Scope)

### ✅ In Scope
- Manajemen master data operator
- Entry absensi harian (manual & upload file)
- Rekap absensi per periode
- Perhitungan otomatis total shift dan honorarium
- Export ke Excel (.xlsx), PDF, dan Print View
- Filter dan pencarian data
- Dashboard ringkasan statistik

### ❌ Out of Scope
- Sistem login / autentikasi
- Notifikasi email/WhatsApp
- Multi-role / multi-user dengan permission berbeda
- Kunci/lock periode
- Integrasi dengan sistem penggajian lain

---

## 3. Aturan Bisnis

### 3.1 Periode Absensi
- Periode berjalan: **tanggal 21 bulan berjalan s/d tanggal 20 bulan berikutnya**
- Contoh: Periode November → 21 Nov s/d 20 Des
- Total hari dalam 1 periode: 30 atau 31 hari (menyesuaikan kalender)

### 3.2 Kode Absensi Harian
Setiap hari direpresentasikan dengan satu karakter kode:

| Kode | Keterangan | Termasuk Shift? |
|------|------------|----------------|
| `1` | Shift 1 (Pagi) | ✅ Ya |
| `2` | Shift 2 (Siang) | ✅ Ya |
| `3` | Shift 3 (Malam) | ✅ Ya |
| `H` | Holiday / Libur | ❌ Tidak |
| `S` | Sakit | ❌ Tidak |
| `I` | Izin | ❌ Tidak |
| `A` | Alpa (tidak hadir tanpa keterangan) | ❌ Tidak |

### 3.3 Grup Shift Rotasi
Terdapat **4 grup rotasi shift** yang berbeda pola jadwalnya:
- Grup A, B, C, D — masing-masing memiliki urutan shift yang berputar
- Pola rotasi mengikuti jadwal yang ditetapkan secara periodik

### 3.4 Unit Kerja
Operator dibagi menjadi 3 unit:
- **BTG** (Boiler Turbine Generator)
- **C&AHS** (Chemical & Ash Handling System)
- **Power Distribution**

### 3.5 Perhitungan Honorarium
```
Total Shift Bekerja = Jumlah hari dengan kode 1 + 2 + 3
Total Honorarium    = Total Shift Bekerja × Rp 8.000
```
- **Tidak ada potongan** untuk S (Sakit), I (Izin), maupun A (Alpa)
- S, I, A tetap dicatat untuk keperluan statistik dan keterangan

---

## 4. Struktur Database (Google Spreadsheet)

### 4.1 Sheet: `MasterOperator`
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| ID_Operator | String | Auto-generate (OP001, OP002, ...) |
| Nama | String | Nama lengkap operator |
| Unit | Enum | BTG / C&AHS / Power Distribution |
| Grup_Shift | Enum | A / B / C / D |
| Status | Enum | Aktif / Nonaktif |
| Tanggal_Masuk | Date | Tanggal bergabung |
| Keterangan | String | Catatan tambahan (opsional) |

### 4.2 Sheet: `Absensi`
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| ID_Record | String | Auto-generate |
| Periode | String | Format: `NOV2025` |
| Tgl_Mulai | Date | Tanggal mulai periode |
| Tgl_Selesai | Date | Tanggal selesai periode |
| ID_Operator | String | FK ke MasterOperator |
| Nama_Operator | String | Denormalized untuk query cepat |
| Unit | String | Denormalized |
| Grup_Shift | String | A / B / C / D |
| Kode_String | String | String 30 karakter (1 char/hari) |
| Jml_Shift1 | Integer | Total hari shift 1 |
| Jml_Shift2 | Integer | Total hari shift 2 |
| Jml_Shift3 | Integer | Total hari shift 3 |
| Jml_Holiday | Integer | Total hari libur |
| Jml_Sakit | Integer | Total hari sakit |
| Jml_Izin | Integer | Total hari izin |
| Jml_Alpa | Integer | Total hari alpa |
| Total_Shift | Integer | Total shift bekerja |
| Total_Honorarium | Integer | Total Shift × 8000 |
| Keterangan | String | Catatan/alasan S, I, A |
| Tgl_Update | Datetime | Timestamp update terakhir |

### 4.3 Sheet: `Periode`
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| Kode_Periode | String | Format: `NOV2025` |
| Label | String | Contoh: "November 2025" |
| Tgl_Mulai | Date | 21 bulan berjalan |
| Tgl_Selesai | Date | 20 bulan berikutnya |
| Total_Hari | Integer | Jumlah hari dalam periode |
| Status | Enum | Aktif / Selesai |

### 4.4 Sheet: `KeteranganAbsensi`
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| ID | String | Auto-generate |
| ID_Record | String | FK ke Absensi |
| Tanggal | Date | Tanggal kejadian |
| Kode | Enum | S / I / A |
| Alasan | String | Contoh: "Keluarga meninggal", "Sakit" |

---

## 5. Fitur Aplikasi

### 5.1 Dashboard
**Tujuan:** Memberikan ringkasan cepat kondisi absensi periode berjalan.

**Komponen UI:**
- Header: Nama aplikasi, periode aktif, tanggal hari ini
- **Kartu Statistik:**
  - Total Operator Aktif
  - Total Shift Tercatat (periode berjalan)
  - Total Honorarium Periode Ini
  - Jumlah Operator dengan Alpa
- **Grafik Bar:** Perbandingan total shift per unit (BTG / C&AHS / Power Distribution)
- **Grafik Donut:** Distribusi kode absensi (Shift 1/2/3, H, S, I, A)
- **Tabel Ringkas:** 10 operator terakhir yang di-update
- **Quick Action Button:** Tambah Absensi, Upload File, Lihat Rekap

### 5.2 Manajemen Master Operator
**Tujuan:** CRUD data operator.

**Sub-fitur:**
- **Daftar Operator:** Tabel dengan filter unit, grup shift, status, pencarian nama
- **Tambah Operator:** Form input data operator baru
- **Edit Operator:** Ubah data operator yang ada
- **Nonaktifkan Operator:** Soft-delete (tidak menghapus data historis)
- **Detail Operator:** Modal/halaman detail dengan riwayat absensi

**Validasi:**
- Nama tidak boleh kosong
- Unit dan Grup Shift wajib diisi
- Tidak boleh ada nama duplikat dalam unit yang sama

### 5.3 Entry Absensi

#### 5.3.1 Input Manual (Per Operator)
**Alur:**
1. Pilih periode dari dropdown
2. Cari/pilih operator (dengan fitur autocomplete)
3. Tampil form kalender periode dengan toggle/dropdown per hari
4. Setiap sel hari memiliki dropdown: `1 | 2 | 3 | H | S | I | A`
5. Jika ada S/I/A, muncul field keterangan/alasan
6. Preview otomatis: ringkasan shift, total honorarium di sisi kanan
7. Tombol Simpan

**UX Details:**
- Highlight hari Sabtu/Minggu dengan warna berbeda
- Kode yang sudah terisi ditampilkan dengan warna-warna khas:
  - `1` → Biru muda
  - `2` → Biru
  - `3` → Biru tua
  - `H` → Abu-abu
  - `S` → Kuning
  - `I` → Oranye
  - `A` → Merah
- Auto-save setiap perubahan (debounce 2 detik)

#### 5.3.2 Input String Manual
- Field input string langsung (untuk pengguna yang sudah familiar)
- Contoh: `3H111222HH333H111222HH3`
- Validasi: hanya karakter `1`,`2`,`3`,`H`,`S`,`I`,`A`, panjang sesuai jumlah hari periode
- Preview otomatis ke tampilan kalender setelah input

#### 5.3.3 Upload File (Bulk)
**Format template yang didukung:**
- Excel (.xlsx) — template disediakan di aplikasi
- CSV

**Struktur template:**
| Kolom | Keterangan |
|-------|------------|
| Nama_Operator | Harus sesuai dengan data master |
| Unit | BTG / C&AHS / Power Distribution |
| Kode_String | String kode absensi |
| Keterangan | Alasan S/I/A (opsional) |

**Alur Upload:**
1. Download template dari aplikasi
2. Isi template
3. Upload file
4. Preview data sebelum disimpan (tampilkan error jika ada)
5. Konfirmasi dan simpan

**Error Handling:**
- Nama operator tidak ditemukan di master → warning, data dilewati atau dibuat baru
- Kode tidak valid → highlight baris bermasalah
- Panjang kode tidak sesuai periode → error

### 5.4 Rekap & Laporan

**Tampilan utama:** Tabel rekap menyerupai format PDF referensi

**Filter yang tersedia:**
- Periode (dropdown semua periode tersimpan)
- Unit (BTG / C&AHS / Power Distribution / Semua)
- Grup Shift (A/B/C/D / Semua)
- Nama operator (search)
- Tampilkan hanya yang ada S/I/A

**Kolom tabel rekap:**
| No | Nama | Unit | Grup | Kode String | Shift 1 | Shift 2 | Shift 3 | H | S | I | A | Total Shift | Honorarium | Aksi |
|----|------|------|------|-------------|---------|---------|---------|---|---|---|---|-------------|------------|------|

**Sub-fitur:**
- Klik baris → expandable detail per hari (kalender view)
- Edit langsung dari tabel (inline edit)
- Ringkasan footer: total shift semua operator, total honorarium keseluruhan
- Rekap per unit: subtotal per unit

### 5.5 Export Data

#### 5.5.1 Export Excel (.xlsx)
- Format mengikuti tabel rekap di aplikasi
- Multi-sheet: Sheet 1 = Rekap Semua, Sheet 2 = BTG, Sheet 3 = C&AHS, Sheet 4 = Power Distribution
- Baris header warna, alternating row color
- Footer total per unit dan grand total

#### 5.5.2 Export PDF
- Layout portrait / landscape (dapat dipilih)
- Header: nama perusahaan, judul laporan, periode
- Tabel rekap dengan format rapi
- Footer: total, tanda tangan (placeholder)
- Dibuat via HTML-to-PDF di GAS

#### 5.5.3 Print View
- Tampilan halaman khusus yang dioptimalkan untuk print (`@media print`)
- Tombol Print Browser
- Sembunyikan elemen navigasi, tombol, sidebar saat print

### 5.6 Manajemen Periode
**Tujuan:** Buat dan kelola periode absensi.

**Fitur:**
- Daftar semua periode yang pernah dibuat
- Tambah periode baru (otomatis hitung tanggal 21 s/d 20)
- Set periode aktif
- Ringkasan data per periode (jumlah operator terentri, completeness %)

---

## 6. Arsitektur Teknis

### 6.1 Stack Teknologi
| Layer | Teknologi | Alasan |
|-------|-----------|--------|
| Backend | Google Apps Script (GAS) | Terintegrasi native dengan Google Spreadsheet |
| Database | Google Spreadsheet | Gratis, mudah diakses, familiar |
| Frontend | HTML5 + CSS3 + Vanilla JS | Ringan, compatible dengan GAS HtmlService |
| UI Framework | Bootstrap 5 | Responsif, komponen lengkap |
| Charts | Chart.js | Ringan, tidak perlu CDN eksternal |
| Icons | Bootstrap Icons / Feather Icons | Ringan |
| Export Excel | SheetJS (xlsx.js) atau GAS SpreadsheetApp | Native support |
| Export PDF | GAS UrlFetchApp + HTML template | Via server-side rendering |

### 6.2 Struktur Folder GAS Project
```
📁 Project GAS
├── Code.gs              # Main router (doGet, routing)
├── DataService.gs       # CRUD operations ke Spreadsheet
├── ExportService.gs     # Logic export Excel, PDF
├── UtilService.gs       # Helper functions (validasi, kalkulasi)
├── 📁 HTML
│   ├── Index.html       # Main shell (layout, sidebar, navbar)
│   ├── Dashboard.html   # Dashboard page
│   ├── Operator.html    # Manajemen operator
│   ├── Entry.html       # Form entry absensi
│   ├── Rekap.html       # Halaman rekap & laporan
│   ├── Print.html       # Print view template
│   ├── _Styles.html     # CSS include
│   └── _Scripts.html    # JS include
└── appsscript.json      # Manifest
```

### 6.3 Pola Komunikasi Frontend–Backend
```
Frontend (JS)  ──→  google.script.run  ──→  GAS Function  ──→  Spreadsheet
                ←── successHandler     ←──  return JSON   ←──  return data
```

### 6.4 Deployment
- Deploy sebagai **Web App** di Google Apps Script
- Execute as: **Me** (pemilik spreadsheet)
- Who has access: **Anyone** (dalam organisasi, atau sesuai kebutuhan)
- URL dibagikan ke Admin/HR

---

## 7. Desain UI/UX

### 7.1 Prinsip Desain
- **Bersih & Modern:** Gunakan whitespace, tipografi jelas
- **Responsif:** Mobile-first, berfungsi di smartphone dan desktop
- **Konsisten:** Warna dan komponen konsisten di seluruh halaman
- **Cepat:** Minimalisasi loading, gunakan loading skeleton
- **Bahasa:** Indonesia

### 7.2 Palet Warna
| Elemen | Warna | Hex |
|--------|-------|-----|
| Primary | Biru PLN | `#0060AF` |
| Secondary | Biru Gelap | `#004080` |
| Accent | Kuning Emas | `#F5A623` |
| Success | Hijau | `#28A745` |
| Warning | Kuning | `#FFC107` |
| Danger | Merah | `#DC3545` |
| Background | Abu-abu Muda | `#F4F6F9` |
| Card | Putih | `#FFFFFF` |

### 7.3 Warna Kode Absensi
| Kode | Background | Teks |
|------|-----------|------|
| `1` | `#DBEAFE` | `#1E40AF` |
| `2` | `#BFDBFE` | `#1D4ED8` |
| `3` | `#93C5FD` | `#1E3A8A` |
| `H` | `#E5E7EB` | `#6B7280` |
| `S` | `#FEF9C3` | `#854D0E` |
| `I` | `#FFEDD5` | `#9A3412` |
| `A` | `#FEE2E2` | `#991B1B` |

### 7.4 Layout
- **Sidebar kiri:** Navigasi utama (collapse di mobile)
- **Top bar:** Nama aplikasi, periode aktif, tombol profile/info
- **Main content:** Area konten utama
- **Footer:** Versi aplikasi, nama perusahaan

---

## 8. Alur Pengguna (User Flow)

### 8.1 Alur Entry Absensi Baru
```
Dashboard
  └─→ [+ Tambah Absensi]
        └─→ Pilih Periode
              └─→ Cari Nama Operator
                    └─→ Form Kalender (isi per hari)
                          └─→ Preview Ringkasan
                                └─→ Simpan ✅
```

### 8.2 Alur Upload Bulk
```
Dashboard / Rekap
  └─→ [Upload File]
        └─→ Download Template
              └─→ Isi & Upload File
                    └─→ Preview & Validasi
                          ├─→ Ada Error → Perbaiki & Upload Ulang
                          └─→ OK → Konfirmasi Simpan ✅
```

### 8.3 Alur Export Laporan
```
Rekap
  └─→ Set Filter (Periode, Unit)
        └─→ [Export ▼]
              ├─→ Excel → Download .xlsx
              ├─→ PDF   → Download .pdf
              └─→ Print → Buka Print View → Print
```

---

## 9. Validasi & Error Handling

| Skenario | Handling |
|----------|----------|
| Kode absensi tidak valid | Toast error, highlight field |
| Panjang string ≠ jumlah hari periode | Error dengan pesan jelas |
| Operator tidak ditemukan saat upload | Warning, skip baris atau buat baru |
| Data ganda (operator + periode sama) | Konfirmasi: update atau buat baru |
| Spreadsheet quota/error | Tampilkan pesan "Coba lagi" + log error |
| Export gagal | Tampilkan pesan error + opsi retry |

---

## 10. Kriteria Penerimaan (Acceptance Criteria)

### Minimum Viable Product (MVP)
- [ ] Halaman dashboard dengan statistik ringkasan
- [ ] CRUD master operator
- [ ] Entry absensi manual (form kalender per hari)
- [ ] Input string kode langsung
- [ ] Upload bulk via Excel template
- [ ] Rekap tabel dengan filter periode & unit
- [ ] Export ke Excel
- [ ] Export ke PDF
- [ ] Print view
- [ ] Kalkulasi otomatis shift & honorarium
- [ ] Responsif di mobile dan desktop

### Nice to Have (Post-MVP)
- [ ] Dark mode
- [ ] Grafik tren absensi antar periode
- [ ] Copy data dari periode sebelumnya (untuk pola shift tetap)
- [ ] Riwayat perubahan data (audit log)
- [ ] Summary email otomatis (GAS trigger)

---

## 11. Asumsi & Dependensi

| Item | Detail |
|------|--------|
| Google Workspace | Akun Google aktif dengan akses GAS dan Drive |
| Tarif per shift | Rp 8.000 (dapat dikonfigurasi di settings sheet) |
| Jumlah operator | Estimasi ~100 operator berdasarkan data PDF referensi |
| Browser | Chrome modern (GAS web app paling stabil di Chrome) |
| Koneksi internet | Diperlukan (aplikasi online) |

---

## 12. Referensi

- File referensi: `Realisasi_Absensi_Operator_21_Nov_s_d_13_Des.pdf`
- Total operator di file referensi: **97 operator**
- Unit: BTG, C&AHS, Power Distribution
- Grup shift: 4 grup rotasi teridentifikasi
- Dokumentasi GAS: https://developers.google.com/apps-script

---

*Dokumen ini akan diperbarui seiring perkembangan proyek.*