/**
 * APP.JS - Logika Utama Frontend Aplikasi Rekapitulasi Absensi Operator
 */

// === 1. GLOBAL STATE & CONFIG ===
const state = {
  activePage: 'dashboard',
  currentPeriod: null,    // Objek Periode aktif berjalan
  selectedPeriodCode: '', // Kode periode yang sedang dipilih di filter/input
  periods: [],            // Daftar semua periode
  operators: [],          // Daftar semua operator
  absensiData: [],        // Data absensi untuk periode terpilih
  
  // State untuk Entry Absensi
  activeEntryOperator: null, // Operator yang sedang diedit absensinya
  activeEntryAbsensi: null,  // Record absensi operator terpilih
  activeKeteranganList: [],  // Keterangan S/I/A operator terpilih
  
  // Debounce saving
  saveTimeout: null
};

// Konfigurasi Tarif
const TARIF_SHIFT = 8000;

// === 2. INISIALISASI APLIKASI ===
document.addEventListener('DOMContentLoaded', () => {
  initApp();
});

async function initApp() {
  setupSidebar();
  setupGlobalEvents();
  setupTheme();
  
  // Load data awal dari backend (GAS / Mock)
  await loadBaseData();
  
  // Daftarkan routing hash
  window.addEventListener('hashchange', handleRouting);
  handleRouting(); // trigger rute pertama kali
}

// Setup Sidebar Toggle Mobile
function setupSidebar() {
  const toggleBtn = document.getElementById('toggle-sidebar');
  const closeBtn = document.getElementById('close-sidebar');
  const sidebar = document.querySelector('.sidebar');
  const overlay = document.getElementById('sidebar-overlay');
  
  if (toggleBtn && sidebar && overlay) {
    toggleBtn.addEventListener('click', () => {
      sidebar.classList.add('show');
      overlay.classList.add('show');
    });
  }
  
  if (closeBtn && sidebar && overlay) {
    closeBtn.addEventListener('click', () => {
      sidebar.classList.remove('show');
      overlay.classList.remove('show');
    });
    overlay.addEventListener('click', () => {
      sidebar.classList.remove('show');
      overlay.classList.remove('show');
    });
  }

  // Handle item click to close sidebar on mobile
  const navItems = document.querySelectorAll('.nav-item');
  navItems.forEach(item => {
    item.addEventListener('click', () => {
      sidebar.classList.remove('show');
      overlay.classList.remove('show');
    });
  });
}

// Setup Event Global lainnya
function setupGlobalEvents() {
  // Hari Ini di Topbar
  const todayDisplay = document.getElementById('today-date-display');
  if (todayDisplay) {
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    todayDisplay.textContent = new Date().toLocaleDateString('id-ID', options);
  }
}

// Setup Theme Switch (Light/Dark Mode)
function setupTheme() {
  const themeSwitch = document.getElementById('theme-switch');
  const body = document.body;
  const themeIconLight = document.getElementById('theme-icon-light');
  const themeIconDark = document.getElementById('theme-icon-dark');
  
  // Load preferensi tersimpan
  const savedTheme = localStorage.getItem('theme') || 'light-theme';
  body.className = savedTheme;
  
  if (themeSwitch) {
    themeSwitch.checked = savedTheme === 'dark-theme';
    updateThemeUI(savedTheme === 'dark-theme');
    
    themeSwitch.addEventListener('change', (e) => {
      const isDark = e.target.checked;
      const newTheme = isDark ? 'dark-theme' : 'light-theme';
      body.className = newTheme;
      localStorage.setItem('theme', newTheme);
      updateThemeUI(isDark);
      
      // Re-render dashboard chart jika sedang aktif untuk sesuaikan warna grid chart
      if (state.activePage === 'dashboard') {
        renderDashboardCharts();
      }
    });
  }

  function updateThemeUI(isDark) {
    if (isDark) {
      themeIconLight?.classList.add('text-muted');
      themeIconDark?.classList.remove('text-muted');
      themeIconDark?.classList.add('text-warning');
    } else {
      themeIconLight?.classList.remove('text-muted');
      themeIconDark?.classList.add('text-muted');
      themeIconDark?.classList.remove('text-warning');
    }
  }
}

// Memuat data master & periode
async function loadBaseData() {
  return new Promise((resolve) => {
    google.script.run
      .withSuccessHandler((data) => {
        state.periods = data.periods;
        state.operators = data.operators;
        
        // Cari periode aktif
        state.currentPeriod = state.periods.find(p => p.Status === 'Aktif') || state.periods[state.periods.length - 1];
        state.selectedPeriodCode = state.currentPeriod ? state.currentPeriod.Kode_Periode : '';
        
        // Update topbar badge
        const periodDisplay = document.getElementById('current-period-display');
        if (periodDisplay && state.currentPeriod) {
          periodDisplay.textContent = state.currentPeriod.Label;
        }
        
        resolve();
      })
      .withFailureHandler((err) => {
        showToast('Gagal memuat data dasar: ' + err.message, 'danger');
        resolve();
      })
      .getBaseDataMock(); // Kita definisikan fungsi aggregasi ini di backend
  });
}

// Rute Halaman (Routing)
function handleRouting() {
  const hash = window.location.hash.substring(1) || 'dashboard';
  state.activePage = hash;
  
  // Set navigasi aktif di sidebar
  const navItems = document.querySelectorAll('.nav-item');
  navItems.forEach(item => {
    if (item.getAttribute('data-page') === hash) {
      item.classList.add('active');
    } else {
      item.classList.remove('active');
    }
  });

  // Tampilkan title dan content
  const pageTitle = document.getElementById('page-title');
  const pageSubtitle = document.getElementById('page-subtitle');
  
  switch (hash) {
    case 'dashboard':
      pageTitle.textContent = 'Dashboard';
      pageSubtitle.textContent = 'Ringkasan kondisi absensi operator periode ini';
      renderDashboardPage();
      break;
    case 'operator':
      pageTitle.textContent = 'Master Operator';
      pageSubtitle.textContent = 'Kelola data karyawan & grup rotasi shift';
      renderOperatorPage();
      break;
    case 'entry':
      pageTitle.textContent = 'Entry Absensi';
      pageSubtitle.textContent = 'Input absensi manual per operator atau string kode';
      renderEntryPage();
      break;
    case 'rekap':
      pageTitle.textContent = 'Rekap & Laporan';
      pageSubtitle.textContent = 'Rekapitulasi absensi, perhitungan shift, honorarium & export';
      renderRekapPage();
      break;
    case 'periode':
      pageTitle.textContent = 'Kelola Periode';
      pageSubtitle.textContent = 'Konfigurasi siklus periode absensi bulanan';
      renderPeriodePage();
      break;
    default:
      pageTitle.textContent = 'Not Found';
      pageSubtitle.textContent = 'Halaman tidak ditemukan';
      document.getElementById('app-content').innerHTML = `
        <div class="text-center py-5">
          <i class="bi bi-exclamation-triangle text-danger display-1"></i>
          <h3 class="mt-3">Halaman tidak ditemukan</h3>
          <a href="#dashboard" class="btn btn-primary mt-2">Kembali ke Dashboard</a>
        </div>
      `;
  }
}

// === 3. ROUTE CONTROLLERS & VIEW GENERATORS ===

// ==========================================
// 3.1 DASHBOARD VIEW
// ==========================================
let unitChart = null;
let codeChart = null;

async function renderDashboardPage() {
  const appContent = document.getElementById('app-content');
  
  // Tampilkan Loading Skeleton
  appContent.innerHTML = `
    <div class="row g-4 mb-4">
      ${Array(4).fill(0).map(() => `
        <div class="col-12 col-sm-6 col-xl-3">
          <div class="card-premium card-stat">
            <div>
              <div class="skeleton skeleton-text" style="width: 80px;"></div>
              <div class="skeleton skeleton-card-value mt-2"></div>
            </div>
            <div class="skeleton" style="width: 48px; height: 48px; border-radius: 10px;"></div>
          </div>
        </div>
      `).join('')}
    </div>
    <div class="row g-4">
      <div class="col-12 col-lg-8">
        <div class="card-premium" style="height: 350px;">
          <div class="card-header-premium"><div class="skeleton skeleton-title" style="width: 150px;"></div></div>
          <div class="card-body-premium d-flex align-items-center justify-content-center h-75">
            <div class="spinner-border text-primary" role="status"></div>
          </div>
        </div>
      </div>
      <div class="col-12 col-lg-4">
        <div class="card-premium" style="height: 350px;">
          <div class="card-header-premium"><div class="skeleton skeleton-title" style="width: 100px;"></div></div>
          <div class="card-body-premium d-flex align-items-center justify-content-center h-75">
            <div class="spinner-border text-primary" role="status"></div>
          </div>
        </div>
      </div>
    </div>
  `;

  google.script.run
    .withSuccessHandler((dashData) => {
      // Render Content Asli
      appContent.innerHTML = `
        <!-- Stat Cards -->
        <div class="row g-4 mb-4">
          <div class="col-12 col-sm-6 col-xl-3">
            <div class="card-premium card-stat">
              <div>
                <span class="text-muted text-xs d-block mb-1">Operator Aktif</span>
                <span class="stat-value" id="stat-ops-aktif">${dashData.TotalOperatorAktif}</span>
              </div>
              <div class="card-stat-icon">
                <i class="bi bi-people"></i>
              </div>
            </div>
          </div>
          <div class="col-12 col-sm-6 col-xl-3">
            <div class="card-premium card-stat">
              <div>
                <span class="text-muted text-xs d-block mb-1">Total Shift Bekerja</span>
                <span class="stat-value text-primary" id="stat-total-shift">${dashData.TotalShift}</span>
              </div>
              <div class="card-stat-icon">
                <i class="bi bi-calendar-check"></i>
              </div>
            </div>
          </div>
          <div class="col-12 col-sm-6 col-xl-3">
            <div class="card-premium card-stat stat-success">
              <div>
                <span class="text-muted text-xs d-block mb-1">Total Honorarium</span>
                <span class="stat-value text-success" id="stat-total-honor">Rp ${dashData.TotalHonorarium.toLocaleString('id-ID')}</span>
              </div>
              <div class="card-stat-icon">
                <i class="bi bi-cash-coin"></i>
              </div>
            </div>
          </div>
          <div class="col-12 col-sm-6 col-xl-3">
            <div class="card-premium card-stat ${dashData.OperatorAlpa > 0 ? 'stat-danger' : 'stat-warning'}">
              <div>
                <span class="text-muted text-xs d-block mb-1">Operator Alpa (A)</span>
                <span class="stat-value ${dashData.OperatorAlpa > 0 ? 'text-danger' : 'text-warning'}" id="stat-ops-alpa">${dashData.OperatorAlpa}</span>
              </div>
              <div class="card-stat-icon">
                <i class="bi bi-exclamation-triangle"></i>
              </div>
            </div>
          </div>
        </div>

        <!-- Charts Section -->
        <div class="row g-4 mb-4">
          <div class="col-12 col-lg-7">
            <div class="card-premium">
              <div class="card-header-premium">
                <h5 class="m-0 fs-6"><i class="bi bi-bar-chart-fill text-primary me-2"></i>Total Shift per Unit Kerja</h5>
                <span class="badge bg-light text-dark text-xs">Periode Ini</span>
              </div>
              <div class="card-body-premium">
                <div style="height: 250px; position: relative;">
                  <canvas id="chart-shift-unit"></canvas>
                </div>
              </div>
            </div>
          </div>
          
          <div class="col-12 col-lg-5">
            <div class="card-premium">
              <div class="card-header-premium">
                <h5 class="m-0 fs-6"><i class="bi bi-pie-chart-fill text-primary me-2"></i>Distribusi Kode Absensi</h5>
                <span class="badge bg-light text-dark text-xs">Seluruh Operator</span>
              </div>
              <div class="card-body-premium">
                <div style="height: 250px; position: relative;">
                  <canvas id="chart-code-dist"></canvas>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Recent Updates & Quick Actions -->
        <div class="row g-4">
          <!-- Recent Updates -->
          <div class="col-12 col-xl-8">
            <div class="card-premium">
              <div class="card-header-premium">
                <h5 class="m-0 fs-6"><i class="bi bi-clock-history text-primary me-2"></i>Aktivitas Update Absensi Terkini</h5>
              </div>
              <div class="card-body-premium p-0">
                <div class="table-responsive">
                  <table class="table table-hover text-xs mb-0 align-middle">
                    <thead class="table-light">
                      <tr>
                        <th>Operator</th>
                        <th>Unit</th>
                        <th>Periode</th>
                        <th>Total Shift</th>
                        <th>Tgl Update</th>
                      </tr>
                    </thead>
                    <tbody>
                      ${dashData.RecentUpdates.length === 0 ? `
                        <tr>
                          <td colspan="5" class="text-center py-4 text-muted">Belum ada riwayat aktivitas.</td>
                        </tr>
                      ` : dashData.RecentUpdates.map(item => `
                        <tr>
                          <td><strong>${item.Nama}</strong></td>
                          <td><span class="badge bg-secondary-subtle text-secondary">${item.Unit}</span></td>
                          <td>${item.Periode}</td>
                          <td><strong>${item.Total_Shift}</strong> Shift</td>
                          <td>${formatRelativeTime(item.Tgl_Update)}</td>
                        </tr>
                      `).join('')}
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>

          <!-- Quick Actions -->
          <div class="col-12 col-xl-4">
            <div class="card-premium">
              <div class="card-header-premium">
                <h5 class="m-0 fs-6"><i class="bi bi-lightning-charge-fill text-warning me-2"></i>Akses Cepat</h5>
              </div>
              <div class="card-body-premium d-flex flex-column gap-3">
                <a href="#entry" class="btn btn-primary text-start py-3 d-flex align-items-center justify-content-between">
                  <div>
                    <i class="bi bi-calendar-plus-fill me-2 fs-5"></i>
                    <strong>Entry Absensi Operator</strong>
                    <div class="text-xs text-white-50 mt-1">Input shift harian & alasan S/I/A</div>
                  </div>
                  <i class="bi bi-chevron-right"></i>
                </a>
                
                <button class="btn btn-outline-primary text-start py-3 d-flex align-items-center justify-content-between" data-bs-toggle="modal" data-bs-target="#modalUpload">
                  <div>
                    <i class="bi bi-cloud-arrow-up-fill me-2 fs-5 text-success"></i>
                    <strong class="text-dark-sm">Upload Excel / CSV</strong>
                    <div class="text-xs text-muted mt-1">Impor data absensi massal sekaligus</div>
                  </div>
                  <i class="bi bi-chevron-right text-muted"></i>
                </button>
                
                <a href="#rekap" class="btn btn-light text-start py-3 d-flex align-items-center justify-content-between">
                  <div>
                    <i class="bi bi-file-earmark-bar-graph-fill me-2 fs-5 text-info"></i>
                    <strong>Lihat Rekap Absensi</strong>
                    <div class="text-xs text-muted mt-1">Ekspor Excel, PDF, atau Cetak Laporan</div>
                  </div>
                  <i class="bi bi-chevron-right text-muted"></i>
                </a>
              </div>
            </div>
          </div>
        </div>
      `;
      
      // Render Grafik
      renderDashboardCharts(dashData);
    })
    .withFailureHandler((err) => {
      appContent.innerHTML = `
        <div class="alert alert-danger py-3">
          <i class="bi bi-exclamation-octagon-fill me-2"></i> Gagal memuat data dashboard: ${err.message}
        </div>
      `;
    })
    .getDashboardData();
}

function renderDashboardCharts(data) {
  // Jika dipanggil ulang karena ganti theme dan data kosong, ambil data lama di memory
  if (!data) return;

  const isDark = document.body.classList.contains('dark-theme');
  const textColor = isDark ? '#94a3b8' : '#64748b';
  const gridColor = isDark ? '#1e293b' : '#e2e8f0';

  // 1. Bar Chart Shift per Unit
  const ctxUnit = document.getElementById('chart-shift-unit');
  if (ctxUnit) {
    if (unitChart) unitChart.destroy();
    unitChart = new Chart(ctxUnit, {
      type: 'bar',
      data: {
        labels: ['BTG', 'C&AHS', 'Power Distribution'],
        datasets: [{
          label: 'Total Shift',
          data: data.ChartUnitData,
          backgroundColor: ['#0060AF', '#F5A623', '#10b981'],
          borderRadius: 8,
          barThickness: 35
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false }
        },
        scales: {
          x: {
            grid: { display: false },
            ticks: { color: textColor }
          },
          y: {
            grid: { color: gridColor },
            ticks: { color: textColor }
          }
        }
      }
    });
  }

  // 2. Donut Chart Code Distribution
  const ctxCode = document.getElementById('chart-code-dist');
  if (ctxCode) {
    if (codeChart) codeChart.destroy();
    codeChart = new Chart(ctxCode, {
      type: 'doughnut',
      data: {
        labels: ['Shift 1', 'Shift 2', 'Shift 3', 'Holiday', 'Sakit', 'Izin', 'Alpa'],
        datasets: [{
          data: data.ChartCodeData,
          backgroundColor: [
            '#DBEAFE', // Shift 1 (light blue)
            '#BFDBFE', // Shift 2 (mid blue)
            '#93C5FD', // Shift 3 (dark blue)
            '#E5E7EB', // Holiday (grey)
            '#FEF9C3', // Sakit (yellow)
            '#FFEDD5', // Izin (orange)
            '#FEE2E2'  // Alpa (red)
          ],
          borderColor: isDark ? '#141c2f' : '#ffffff',
          borderWidth: 2
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'right',
            labels: {
              color: textColor,
              boxWidth: 12,
              font: { size: 11 }
            }
          }
        },
        cutout: '60%'
      }
    });
  }
}

// ==========================================
// 3.2 MASTER OPERATOR VIEW (CRUD)
// ==========================================
function renderOperatorPage() {
  const appContent = document.getElementById('app-content');
  
  appContent.innerHTML = `
    <div class="card-premium">
      <div class="card-header-premium flex-column flex-md-row align-items-stretch align-items-md-center gap-3">
        <!-- Pencarian & Filter -->
        <div class="d-flex flex-wrap gap-2 flex-grow-1">
          <div class="input-group" style="max-width: 250px;">
            <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
            <input type="text" class="form-control border-start-0 bg-light" id="filter-search" placeholder="Cari nama...">
          </div>
          
          <select class="form-select bg-light" id="filter-unit" style="max-width: 150px;">
            <option value="">Semua Unit</option>
            <option value="BTG">BTG</option>
            <option value="C&AHS">C&AHS</option>
            <option value="Power Distribution">Power Distribution</option>
          </select>
          
          <select class="form-select bg-light" id="filter-grup" style="max-width: 140px;">
            <option value="">Semua Grup</option>
            <option value="A">Grup A</option>
            <option value="B">Grup B</option>
            <option value="C">Grup C</option>
            <option value="D">Grup D</option>
          </select>

          <select class="form-select bg-light" id="filter-status" style="max-width: 130px;">
            <option value="Aktif">Status: Aktif</option>
            <option value="Nonaktif">Status: Nonaktif</option>
            <option value="Semua">Semua Status</option>
          </select>
        </div>
        
        <!-- Action Button -->
        <button class="btn btn-primary" id="btn-add-op">
          <i class="bi bi-person-plus-fill me-1"></i> Tambah Operator
        </button>
      </div>
      
      <div class="card-body-premium p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 text-xs">
            <thead class="table-light">
              <tr>
                <th style="width: 80px;">ID</th>
                <th>Nama Operator</th>
                <th>Unit Kerja</th>
                <th>Grup Shift</th>
                <th>Tanggal Masuk</th>
                <th>Status</th>
                <th>Keterangan</th>
                <th style="width: 120px;" class="text-center">Aksi</th>
              </tr>
            </thead>
            <tbody id="operator-table-body">
              <!-- Skeletons -->
              ${Array(5).fill(0).map(() => `
                <tr>
                  <td><div class="skeleton skeleton-text" style="width: 40px;"></div></td>
                  <td><div class="skeleton skeleton-text" style="width: 140px;"></div></td>
                  <td><div class="skeleton skeleton-text" style="width: 80px;"></div></td>
                  <td><div class="skeleton skeleton-text" style="width: 30px;"></div></td>
                  <td><div class="skeleton skeleton-text" style="width: 90px;"></div></td>
                  <td><div class="skeleton skeleton-text" style="width: 50px;"></div></td>
                  <td><div class="skeleton skeleton-text" style="width: 100px;"></div></td>
                  <td><div class="skeleton skeleton-text" style="width: 85px;"></div></td>
                </tr>
              `).join('')}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  `;

  // Fetch Operators
  fetchOperators();

  // Wire up filter events
  document.getElementById('filter-search').addEventListener('input', filterOperatorsTable);
  document.getElementById('filter-unit').addEventListener('change', filterOperatorsTable);
  document.getElementById('filter-grup').addEventListener('change', filterOperatorsTable);
  document.getElementById('filter-status').addEventListener('change', filterOperatorsTable);

  // Wire up add operator modal
  document.getElementById('btn-add-op').addEventListener('click', () => {
    openOperatorModal();
  });

  // Wire up form submit
  document.getElementById('form-operator').onsubmit = handleOperatorFormSubmit;
}

function fetchOperators() {
  google.script.run
    .withSuccessHandler((operators) => {
      state.operators = operators;
      renderOperatorsTable(operators);
    })
    .withFailureHandler((err) => {
      showToast('Gagal memuat daftar operator: ' + err.message, 'danger');
    })
    .getOperators();
}

function renderOperatorsTable(list) {
  const tbody = document.getElementById('operator-table-body');
  if (!tbody) return;

  if (list.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="8" class="text-center py-4 text-muted">Tidak ada data operator yang cocok.</td>
      </tr>
    `;
    return;
  }

  // Filter default: Aktif
  const activeFilter = document.getElementById('filter-status').value;
  const filtered = list.filter(op => {
    if (activeFilter === 'Aktif') return op.Status === 'Aktif';
    if (activeFilter === 'Nonaktif') return op.Status === 'Nonaktif';
    return true; // Semua
  });

  tbody.innerHTML = filtered.map(op => `
    <tr>
      <td><strong>${op.ID_Operator}</strong></td>
      <td>
        <div class="d-flex align-items-center gap-2">
          <div class="avatar-circle">${op.Nama.substring(0, 2).toUpperCase()}</div>
          <strong>${op.Nama}</strong>
        </div>
      </td>
      <td><span class="badge bg-primary-subtle text-primary border border-primary-subtle">${op.Unit}</span></td>
      <td><span class="badge bg-warning-subtle text-warning border border-warning-subtle font-heading fw-bold">${op.Grup_Shift}</span></td>
      <td>${op.Tanggal_Masuk ? formatDateIndo(op.Tanggal_Masuk) : '-'}</td>
      <td>
        <span class="badge bg-${op.Status === 'Aktif' ? 'success' : 'danger'}-subtle text-${op.Status === 'Aktif' ? 'success' : 'danger'} rounded-pill">
          ${op.Status}
        </span>
      </td>
      <td class="text-truncate" style="max-width: 150px;" title="${op.Keterangan || ''}">${op.Keterangan || '-'}</td>
      <td>
        <div class="d-flex justify-content-center gap-1">
          <button class="btn btn-sm btn-outline-primary px-2" onclick="openOperatorModal('${op.ID_Operator}')" title="Edit Operator">
            <i class="bi bi-pencil-square"></i>
          </button>
          ${op.Status === 'Aktif' ? `
            <button class="btn btn-sm btn-outline-danger px-2" onclick="confirmDeactivateOperator('${op.ID_Operator}')" title="Nonaktifkan Operator">
              <i class="bi bi-person-x"></i>
            </button>
          ` : ''}
        </div>
      </td>
    </tr>
  `).join('');
}

function filterOperatorsTable() {
  const search = document.getElementById('filter-search').value.toLowerCase();
  const unit = document.getElementById('filter-unit').value;
  const grup = document.getElementById('filter-grup').value;
  const status = document.getElementById('filter-status').value;

  const filtered = state.operators.filter(op => {
    const matchSearch = op.Nama.toLowerCase().includes(search) || op.ID_Operator.toLowerCase().includes(search);
    const matchUnit = unit === '' || op.Unit === unit;
    const matchGrup = grup === '' || op.Grup_Shift === grup;
    const matchStatus = status === 'Semua' || op.Status === status;
    return matchSearch && matchUnit && matchGrup && matchStatus;
  });

  renderOperatorsTable(filtered);
}

// Modal open helper (Add/Edit)
function openOperatorModal(id = null) {
  const form = document.getElementById('form-operator');
  form.reset();
  form.classList.remove('was-validated');

  const modalEl = document.getElementById('modalOperator');
  const modal = new bootstrap.Modal(modalEl);
  const title = document.getElementById('modalOperatorLabel');
  
  if (id) {
    // Edit Mode
    title.textContent = 'Edit Operator';
    const op = state.operators.find(o => o.ID_Operator === id);
    if (op) {
      document.getElementById('op-id').value = op.ID_Operator;
      document.getElementById('op-nama').value = op.Nama;
      document.getElementById('op-unit').value = op.Unit;
      document.getElementById('op-grup').value = op.Grup_Shift;
      document.getElementById('op-status').value = op.Status;
      document.getElementById('op-tgl-masuk').value = op.Tanggal_Masuk || '';
      document.getElementById('op-ket').value = op.Keterangan || '';
    }
  } else {
    // Add Mode
    title.textContent = 'Tambah Operator';
    document.getElementById('op-id').value = '';
    document.getElementById('op-status').value = 'Aktif';
    document.getElementById('op-tgl-masuk').value = new Date().toISOString().split('T')[0]; // hari ini
  }

  modal.show();
}

// Handler Submit Form CRUD Operator
function handleOperatorFormSubmit(e) {
  e.preventDefault();
  const form = e.target;

  if (!form.checkValidity()) {
    form.classList.add('was-validated');
    return;
  }

  const fd = new FormData(form);
  const opData = Object.fromEntries(fd.entries());
  
  // Set button state
  const btn = document.getElementById('btn-save-operator');
  const origText = btn.innerHTML;
  btn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Menyimpan...`;
  btn.disabled = true;

  google.script.run
    .withSuccessHandler((res) => {
      btn.innerHTML = origText;
      btn.disabled = false;
      
      const modal = bootstrap.Modal.getInstance(document.getElementById('modalOperator'));
      modal.hide();
      
      showToast(opData.ID_Operator ? 'Operator berhasil diperbarui.' : 'Operator berhasil ditambahkan.', 'success');
      
      // Reload operators
      fetchOperators();
    })
    .withFailureHandler((err) => {
      btn.innerHTML = origText;
      btn.disabled = false;
      showToast('Gagal menyimpan operator: ' + err.message, 'danger');
    })
    .saveOperator(opData);
}

// Konfirmasi Soft-Delete / Deaktivasi
function confirmDeactivateOperator(id) {
  const op = state.operators.find(o => o.ID_Operator === id);
  if (!op) return;

  if (confirm(`Apakah Anda yakin ingin menonaktifkan operator "${op.Nama}"?\nData historis absensi tidak akan terpengaruh.`)) {
    google.script.run
      .withSuccessHandler((res) => {
        showToast('Operator berhasil dinonaktifkan.', 'success');
        fetchOperators();
      })
      .withFailureHandler((err) => {
        showToast('Gagal menonaktifkan operator: ' + err.message, 'danger');
      })
      .deleteOperator(id);
  }
}

// ==========================================
// 3.3 ENTRY ABSENSI VIEW
// ==========================================
function renderEntryPage() {
  const appContent = document.getElementById('app-content');
  
  appContent.innerHTML = `
    <div class="row g-4">
      <!-- Panel Input & Autocomplete -->
      <div class="col-12 col-xl-4">
        <div class="card-premium mb-4">
          <div class="card-header-premium">
            <h5 class="m-0 fs-6"><i class="bi bi-person-search text-primary me-2"></i>Pilih Operator & Periode</h5>
          </div>
          <div class="card-body-premium">
            <div class="mb-3">
              <label for="entry-periode-select" class="form-label font-heading fw-semibold text-xs">Periode Absensi</label>
              <select class="form-select" id="entry-periode-select">
                ${state.periods.map(p => `
                  <option value="${p.Kode_Periode}" ${p.Kode_Periode === state.selectedPeriodCode ? 'selected' : ''}>
                    ${p.Label} (${p.Status})
                  </option>
                `).join('')}
              </select>
            </div>
            
            <div class="mb-3 autocomplete-wrapper">
              <label for="entry-search-operator" class="form-label font-heading fw-semibold text-xs">Nama Operator</label>
              <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" class="form-control border-start-0" id="entry-search-operator" placeholder="Ketik nama operator..." autocomplete="off">
              </div>
              <div id="autocomplete-list" class="autocomplete-items"></div>
            </div>

            <!-- Operator Info Card (Initially Hidden) -->
            <div id="entry-operator-card" class="p-3 bg-light rounded border d-none">
              <div class="d-flex align-items-center gap-2 mb-2">
                <div class="avatar-circle" id="entry-op-avatar">OP</div>
                <div>
                  <h6 class="m-0 fw-bold" id="entry-op-name">-</h6>
                  <span class="text-xs text-muted" id="entry-op-id">-</span>
                </div>
              </div>
              <div class="d-flex flex-wrap gap-2 pt-2 border-top">
                <span class="badge bg-primary-subtle text-primary" id="entry-op-unit">-</span>
                <span class="badge bg-warning-subtle text-warning" id="entry-op-grup">Grup -</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Preview Statistik Real-Time -->
        <div id="entry-stats-card" class="card-premium d-none">
          <div class="card-header-premium d-flex align-items-center justify-content-between">
            <h5 class="m-0 fs-6"><i class="bi bi-calculator text-primary me-2"></i>Kalkulasi Shift & Honor</h5>
            <span class="badge bg-success text-white text-xs" id="autosave-status">Tersimpan</span>
          </div>
          <div class="card-body-premium">
            <div class="row g-2 text-center text-xs mb-3">
              <div class="col-4">
                <div class="p-2 border rounded" style="background-color: var(--bg-code-1);">
                  <strong class="d-block" id="count-shift1">0</strong>
                  <span class="text-muted-xs text-code-1">Shift 1</span>
                </div>
              </div>
              <div class="col-4">
                <div class="p-2 border rounded" style="background-color: var(--bg-code-2);">
                  <strong class="d-block" id="count-shift2">0</strong>
                  <span class="text-muted-xs text-code-2">Shift 2</span>
                </div>
              </div>
              <div class="col-4">
                <div class="p-2 border rounded" style="background-color: var(--bg-code-3);">
                  <strong class="d-block" id="count-shift3">0</strong>
                  <span class="text-muted-xs text-code-3">Shift 3</span>
                </div>
              </div>
              <div class="col-3 mt-2">
                <div class="p-2 border rounded" style="background-color: var(--bg-code-h);">
                  <strong class="d-block" id="count-holiday">0</strong>
                  <span class="text-muted-xs text-code-h">Holiday (H)</span>
                </div>
              </div>
              <div class="col-3 mt-2">
                <div class="p-2 border rounded" style="background-color: var(--bg-code-s);">
                  <strong class="d-block" id="count-sakit">0</strong>
                  <span class="text-muted-xs text-code-s">Sakit (S)</span>
                </div>
              </div>
              <div class="col-3 mt-2">
                <div class="p-2 border rounded" style="background-color: var(--bg-code-i);">
                  <strong class="d-block" id="count-izin">0</strong>
                  <span class="text-muted-xs text-code-i">Izin (I)</span>
                </div>
              </div>
              <div class="col-3 mt-2">
                <div class="p-2 border rounded" style="background-color: var(--bg-code-a);">
                  <strong class="d-block" id="count-alpa">0</strong>
                  <span class="text-muted-xs text-code-a">Alpa (A)</span>
                </div>
              </div>
            </div>
            
            <div class="p-3 bg-light rounded border mb-3">
              <div class="d-flex justify-content-between mb-1">
                <span class="text-muted text-xs">Total Shift Bekerja (1+2+3):</span>
                <strong class="text-dark" id="calc-total-shift">0 Shift</strong>
              </div>
              <div class="d-flex justify-content-between align-items-center border-top pt-2">
                <span class="text-muted text-xs">Total Honorarium:</span>
                <strong class="text-success fs-5" id="calc-total-honor">Rp 0</strong>
              </div>
            </div>

            <!-- String Kode Input Section -->
            <div class="mb-3">
              <label for="entry-string-input" class="form-label font-heading fw-semibold text-xs">Input String Kode Langsung</label>
              <input type="text" class="form-control text-xs font-monospace" id="entry-string-input" placeholder="Masukkan 30/31 karakter absensi..." style="letter-spacing: 1px;">
              <div class="invalid-feedback text-xs" id="string-feedback">Hanya boleh karakter 1,2,3,H,S,I,A</div>
            </div>

            <div class="d-grid gap-2">
              <button class="btn btn-primary" id="btn-save-absensi-manual">
                <i class="bi bi-save-fill me-1"></i> Simpan Permanen
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Grid Kalender Hari -->
      <div class="col-12 col-xl-8">
        <div id="calendar-workspace" class="h-100">
          <div class="card-premium h-100 d-flex flex-column align-items-center justify-content-center py-5 text-muted" id="calendar-placeholder">
            <i class="bi bi-calendar-range display-3 text-muted-light mb-3"></i>
            <h5>Pilih operator dan periode absensi terlebih dahulu.</h5>
            <p class="text-xs">Gunakan kotak pencarian di sebelah kiri untuk menemukan operator.</p>
          </div>
          
          <div class="card-premium d-none" id="calendar-card">
            <div class="card-header-premium">
              <h5 class="m-0 fs-6"><i class="bi bi-calendar3-event text-primary me-2"></i>Kalender Siklus Kerja</h5>
              <div class="d-flex align-items-center gap-2">
                <span class="text-xs text-muted" id="calendar-period-label">-</span>
              </div>
            </div>
            <div class="card-body-premium">
              <!-- Calendar Grid Header -->
              <div class="calendar-header">
                <div>Min</div><div>Sen</div><div>Sel</div><div>Rab</div><div>Kam</div><div>Jum</div><div>Sab</div>
              </div>
              <!-- Calendar Grid Body -->
              <div class="calendar-body" id="calendar-grid-body">
                <!-- Cells will be generated here -->
              </div>

              <!-- Petunjuk Kode Warna -->
              <div class="d-flex flex-wrap gap-2 mt-4 justify-content-center text-xs">
                <span class="d-flex align-items-center gap-1"><span class="badge-code badge-code-xs" data-code="1">1</span> Shift 1 (Pagi)</span>
                <span class="d-flex align-items-center gap-1"><span class="badge-code badge-code-xs" data-code="2">2</span> Shift 2 (Siang)</span>
                <span class="d-flex align-items-center gap-1"><span class="badge-code badge-code-xs" data-code="3">3</span> Shift 3 (Malam)</span>
                <span class="d-flex align-items-center gap-1"><span class="badge-code badge-code-xs" data-code="H">H</span> Holiday</span>
                <span class="d-flex align-items-center gap-1"><span class="badge-code badge-code-xs" data-code="S">S</span> Sakit</span>
                <span class="d-flex align-items-center gap-1"><span class="badge-code badge-code-xs" data-code="I">I</span> Izin</span>
                <span class="d-flex align-items-center gap-1"><span class="badge-code badge-code-xs" data-code="A">A</span> Alpa</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  `;

  // Autocomplete Setup
  setupOperatorSearch();

  // Period change handler
  document.getElementById('entry-periode-select').addEventListener('change', (e) => {
    state.selectedPeriodCode = e.target.value;
    if (state.activeEntryOperator) {
      loadAbsensiForActiveOperator();
    }
  });

  // String input edit handler
  document.getElementById('entry-string-input').addEventListener('input', handleStringInputEdit);

  // Manual save click
  document.getElementById('btn-save-absensi-manual').addEventListener('click', () => {
    saveCurrentAbsensi(true);
  });
}

// Setup Autocomplete pencarian Operator
function setupOperatorSearch() {
  const searchInput = document.getElementById('entry-search-operator');
  const listContainer = document.getElementById('autocomplete-list');
  
  if (!searchInput) return;

  searchInput.addEventListener('input', (e) => {
    const val = e.target.value;
    listContainer.innerHTML = '';
    
    if (!val) return;
    
    // Tampilkan operator aktif saja untuk entry data baru
    const match = state.operators.filter(o => 
      o.Status === 'Aktif' && 
      (o.Nama.toLowerCase().includes(val.toLowerCase()) || o.ID_Operator.toLowerCase().includes(val.toLowerCase()))
    );

    match.forEach(op => {
      const div = document.createElement('div');
      div.className = 'autocomplete-item text-xs d-flex justify-content-between align-items-center';
      div.innerHTML = `
        <strong>${op.Nama}</strong>
        <span class="badge bg-secondary-subtle text-secondary">${op.Unit} - ${op.ID_Operator}</span>
      `;
      div.addEventListener('click', () => {
        selectOperatorForEntry(op);
        searchInput.value = '';
        listContainer.innerHTML = '';
      });
      listContainer.appendChild(div);
    });
  });

  // Tutup autocomplete saat klik luar
  document.addEventListener('click', (e) => {
    if (e.target !== searchInput && e.target !== listContainer) {
      listContainer.innerHTML = '';
    }
  });
}

// Memilih operator untuk diedit
function selectOperatorForEntry(op) {
  state.activeEntryOperator = op;
  
  // Update UI Info Operator
  document.getElementById('entry-op-avatar').textContent = op.Nama.substring(0, 2).toUpperCase();
  document.getElementById('entry-op-name').textContent = op.Nama;
  document.getElementById('entry-op-id').textContent = op.ID_Operator;
  document.getElementById('entry-op-unit').textContent = op.Unit;
  document.getElementById('entry-op-grup').textContent = 'Grup ' + op.Grup_Shift;
  
  document.getElementById('entry-operator-card').classList.remove('d-none');
  document.getElementById('entry-stats-card').classList.remove('d-none');
  
  // Tampilkan Workspace Kalender
  document.getElementById('calendar-placeholder').classList.add('d-none');
  document.getElementById('calendar-card').classList.remove('d-none');

  loadAbsensiForActiveOperator();
}

// Memuat data absensi dari operator terpilih
function loadAbsensiForActiveOperator() {
  const pCode = state.selectedPeriodCode;
  const op = state.activeEntryOperator;
  
  if (!op || !pCode) return;

  // Render loading skeleton di grid
  const grid = document.getElementById('calendar-grid-body');
  grid.innerHTML = Array(28).fill(0).map(() => `<div class="calendar-cell"><div class="skeleton skeleton-text" style="width:20px;"></div></div>`).join('');

  google.script.run
    .withSuccessHandler((absensiList) => {
      // Cari data absensi dari database
      const idRecord = `${pCode}_${op.ID_Operator}`;
      const record = absensiList.find(a => a.ID_Record === idRecord);
      
      const period = state.periods.find(p => p.Kode_Periode === pCode);
      
      state.activeEntryAbsensi = record || createDefaultAbsensiRecord(op, period);
      state.activeKeteranganList = record && record.Keterangan_Detail ? record.Keterangan_Detail : [];
      
      renderCalendarGrid(period);
      updateEntryStats();
    })
    .withFailureHandler((err) => {
      showToast('Gagal memuat data absensi: ' + err.message, 'danger');
    })
    .getAbsensi(pCode);
}

// Buat record absensi default jika belum ada di database
function createDefaultAbsensiRecord(op, period) {
  const defaultCodes = Array(period.Total_Hari).fill('H').join(''); // default holiday semua
  return {
    ID_Record: `${period.Kode_Periode}_${op.ID_Operator}`,
    Periode: period.Kode_Periode,
    Tgl_Mulai: period.Tgl_Mulai,
    Tgl_Selesai: period.Tgl_Selesai,
    ID_Operator: op.ID_Operator,
    Nama_Operator: op.Nama,
    Unit: op.Unit,
    Grup_Shift: op.Grup_Shift,
    Kode_String: defaultCodes,
    Jml_Shift1: 0,
    Jml_Shift2: 0,
    Jml_Shift3: 0,
    Jml_Holiday: period.Total_Hari,
    Jml_Sakit: 0,
    Jml_Izin: 0,
    Jml_Alpa: 0,
    Total_Shift: 0,
    Total_Honorarium: 0,
    Keterangan: ''
  };
}

// Menghasilkan Grid Kalender 21 s/d 20 bulan berikutnya
function renderCalendarGrid(period) {
  const grid = document.getElementById('calendar-grid-body');
  grid.innerHTML = '';
  
  document.getElementById('calendar-period-label').textContent = `${formatDateIndo(period.Tgl_Mulai)} s/d ${formatDateIndo(period.Tgl_Selesai)}`;

  const startDate = new Date(period.Tgl_Mulai);
  const totalDays = period.Total_Hari;
  const currentCodes = state.activeEntryAbsensi.Kode_String.split('');

  // Sinkronkan string input box
  const stringInput = document.getElementById('entry-string-input');
  if (stringInput) {
    stringInput.value = state.activeEntryAbsensi.Kode_String;
    stringInput.classList.remove('is-invalid');
  }

  // Cari day index hari pertama untuk membuat offset kosong di grid (agar hari di kolom sejajar)
  // Kolom: Min (0), Sen (1), Sel (2), Rab (3), Kam (4), Jum (5), Sab (6)
  const firstDay = startDate.getDay();
  for (let i = 0; i < firstDay; i++) {
    const empty = document.createElement('div');
    empty.className = 'calendar-cell-empty';
    grid.appendChild(empty);
  }

  // Buat sel hari
  for (let idx = 0; idx < totalDays; idx++) {
    const currentDate = new Date(startDate);
    currentDate.setDate(startDate.getDate() + idx);
    
    const dayNameIndex = currentDate.getDay();
    const isWeekend = dayNameIndex === 0 || dayNameIndex === 6;
    
    const cell = document.createElement('div');
    cell.className = `calendar-cell ${isWeekend ? 'weekend' : ''}`;
    
    const dateStr = currentDate.toISOString().split('T')[0];
    
    // Check if there is an active reason
    const hasReason = state.activeKeteranganList.some(k => k.Tanggal === dateStr);
    if (hasReason) cell.classList.add('has-reason');

    const code = currentCodes[idx] || 'H';

    cell.innerHTML = `
      <div class="d-flex justify-content-between align-items-center">
        <span class="calendar-cell-num">${currentDate.getDate()}</span>
        <span class="calendar-cell-date">${currentDate.toLocaleDateString('id-ID', { month: 'short' })}</span>
      </div>
      <div class="reason-indicator" title="Ada keterangan tertulis"></div>
      <select class="calendar-cell-select text-xs font-heading" data-idx="${idx}" data-date="${dateStr}" data-code="${code}">
        <option value="1" ${code === '1' ? 'selected' : ''}>1</option>
        <option value="2" ${code === '2' ? 'selected' : ''}>2</option>
        <option value="3" ${code === '3' ? 'selected' : ''}>3</option>
        <option value="H" ${code === 'H' ? 'selected' : ''}>H</option>
        <option value="S" ${code === 'S' ? 'selected' : ''}>S</option>
        <option value="I" ${code === 'I' ? 'selected' : ''}>I</option>
        <option value="A" ${code === 'A' ? 'selected' : ''}>A</option>
      </select>
    `;

    // Dropdown Change Event
    const select = cell.querySelector('.calendar-cell-select');
    select.addEventListener('change', handleGridCodeChange);

    grid.appendChild(cell);
  }
}

// Aksi ketika kode di grid kalender diganti
function handleGridCodeChange(e) {
  const select = e.target;
  const idx = parseInt(select.getAttribute('data-idx'));
  const dateStr = select.getAttribute('data-date');
  const code = select.value;
  
  select.setAttribute('data-code', code);
  
  // Update Kode String dalam state
  const codes = state.activeEntryAbsensi.Kode_String.split('');
  codes[idx] = code;
  state.activeEntryAbsensi.Kode_String = codes.join('');
  
  // Sinkronkan string input box
  document.getElementById('entry-string-input').value = state.activeEntryAbsensi.Kode_String;

  // Jika S, I, atau A, minta user input alasan
  if (code === 'S' || code === 'I' || code === 'A') {
    promptReasonForCode(dateStr, code, select.parentElement);
  } else {
    // Hapus alasan jika ada dari state
    state.activeKeteranganList = state.activeKeteranganList.filter(k => k.Tanggal !== dateStr);
    select.parentElement.classList.remove('has-reason');
  }

  updateEntryStats();
  triggerAutoSave();
}

// Input Alasan untuk S/I/A
function promptReasonForCode(dateStr, code, cellElement) {
  const codeName = code === 'S' ? 'Sakit' : (code === 'I' ? 'Izin' : 'Alpa');
  
  // Cari apakah sudah ada alasan sebelumnya
  const existing = state.activeKeteranganList.find(k => k.Tanggal === dateStr);
  const defaultReason = existing ? existing.Alasan : '';

  const reason = prompt(`Masukkan alasan ${codeName} untuk tanggal ${formatDateIndo(dateStr)}:`, defaultReason);
  
  if (reason !== null && reason.trim() !== '') {
    const updatedKet = {
      ID_Record: state.activeEntryAbsensi.ID_Record,
      Tanggal: dateStr,
      Kode: code,
      Alasan: reason.trim()
    };
    
    const idx = state.activeKeteranganList.findIndex(k => k.Tanggal === dateStr);
    if (idx !== -1) {
      state.activeKeteranganList[idx] = updatedKet;
    } else {
      state.activeKeteranganList.push(updatedKet);
    }
    cellElement.classList.add('has-reason');
  } else {
    // Jika cancel atau kosong, default isi simple
    const fallbackKet = {
      ID_Record: state.activeEntryAbsensi.ID_Record,
      Tanggal: dateStr,
      Kode: code,
      Alasan: `${codeName} (Tanpa detail tambahan)`
    };
    const idx = state.activeKeteranganList.findIndex(k => k.Tanggal === dateStr);
    if (idx !== -1) {
      state.activeKeteranganList[idx] = fallbackKet;
    } else {
      state.activeKeteranganList.push(fallbackKet);
    }
    cellElement.classList.add('has-reason');
  }
}

// Edit via text string langsung
function handleStringInputEdit(e) {
  const val = e.target.value.toUpperCase();
  e.target.value = val;

  const totalHari = state.activeEntryAbsensi.Jml_Holiday + 
                    state.activeEntryAbsensi.Jml_Shift1 + 
                    state.activeEntryAbsensi.Jml_Shift2 + 
                    state.activeEntryAbsensi.Jml_Shift3 + 
                    state.activeEntryAbsensi.Jml_Sakit + 
                    state.activeEntryAbsensi.Jml_Izin + 
                    state.activeEntryAbsensi.Jml_Alpa; 
  // Wait, total hari didapat dari period. totalDays
  const period = state.periods.find(p => p.Kode_Periode === state.selectedPeriodCode);
  const expectedLen = period.Total_Hari;

  // Validasi regex: hanya karakter 1,2,3,H,S,I,A dan panjang sesuai periode
  const regex = new RegExp(`^[123HSIA]{${expectedLen}}$`);
  
  if (!regex.test(val)) {
    e.target.classList.add('is-invalid');
    document.getElementById('string-feedback').textContent = `Format salah. Harus ${expectedLen} karakter dan berisi hanya kode valid [1,2,3,H,S,I,A].`;
    return;
  }
  
  e.target.classList.remove('is-invalid');
  state.activeEntryAbsensi.Kode_String = val;

  // Re-sync Grid Kalender
  const selects = document.querySelectorAll('.calendar-cell-select');
  const codes = val.split('');
  
  selects.forEach((sel, i) => {
    const code = codes[i];
    sel.value = code;
    sel.setAttribute('data-code', code);
    
    // bersihkan has-reason jika diganti ke non S/I/A
    if (code !== 'S' && code !== 'I' && code !== 'A') {
      const dateStr = sel.getAttribute('data-date');
      state.activeKeteranganList = state.activeKeteranganList.filter(k => k.Tanggal !== dateStr);
      sel.parentElement.classList.remove('has-reason');
    }
  });

  updateEntryStats();
  triggerAutoSave();
}

// Menghitung statistik berdasarkan perubahan grid secara real-time
function updateEntryStats() {
  const codes = state.activeEntryAbsensi.Kode_String.split('');
  
  let s1 = 0, s2 = 0, s3 = 0, h = 0, s = 0, izin = 0, a = 0;
  codes.forEach(c => {
    if (c === '1') s1++;
    else if (c === '2') s2++;
    else if (c === '3') s3++;
    else if (c === 'H') h++;
    else if (c === 'S') s++;
    else if (c === 'I') izin++;
    else if (c === 'A') a++;
  });

  state.activeEntryAbsensi.Jml_Shift1 = s1;
  state.activeEntryAbsensi.Jml_Shift2 = s2;
  state.activeEntryAbsensi.Jml_Shift3 = s3;
  state.activeEntryAbsensi.Jml_Holiday = h;
  state.activeEntryAbsensi.Jml_Sakit = s;
  state.activeEntryAbsensi.Jml_Izin = izin;
  state.activeEntryAbsensi.Jml_Alpa = a;

  const totalShift = s1 + s2 + s3;
  const totalHonor = totalShift * TARIF_SHIFT;

  state.activeEntryAbsensi.Total_Shift = totalShift;
  state.activeEntryAbsensi.Total_Honorarium = totalHonor;

  // Update DOM
  document.getElementById('count-shift1').textContent = s1;
  document.getElementById('count-shift2').textContent = s2;
  document.getElementById('count-shift3').textContent = s3;
  document.getElementById('count-holiday').textContent = h;
  document.getElementById('count-sakit').textContent = s;
  document.getElementById('count-izin').textContent = izin;
  document.getElementById('count-alpa').textContent = a;
  
  document.getElementById('calc-total-shift').textContent = totalShift + ' Shift';
  document.getElementById('calc-total-honor').textContent = 'Rp ' + totalHonor.toLocaleString('id-ID');
}

// Debounce Trigger Auto-Save
function triggerAutoSave() {
  const statusBadge = document.getElementById('autosave-status');
  if (statusBadge) {
    statusBadge.textContent = 'Mengetik...';
    statusBadge.className = 'badge bg-warning text-dark text-xs';
  }

  clearTimeout(state.saveTimeout);
  state.saveTimeout = setTimeout(() => {
    saveCurrentAbsensi(false);
  }, 2000); // 2 detik debounce
}

// Menyimpan absensi ke database
function saveCurrentAbsensi(isManual = false) {
  const statusBadge = document.getElementById('autosave-status');
  if (statusBadge) {
    statusBadge.textContent = 'Menyimpan...';
    statusBadge.className = 'badge bg-info text-white text-xs';
  }

  // Hitung ulang keterangan string global
  const op = state.activeEntryOperator;
  let summaryKeterangan = '';
  if (state.activeEntryAbsensi.Jml_Sakit > 0) summaryKeterangan += `Sakit ${state.activeEntryAbsensi.Jml_Sakit} hari. `;
  if (state.activeEntryAbsensi.Jml_Izin > 0) summaryKeterangan += `Izin ${state.activeEntryAbsensi.Jml_Izin} hari. `;
  if (state.activeEntryAbsensi.Jml_Alpa > 0) summaryKeterangan += `Alpa ${state.activeEntryAbsensi.Jml_Alpa} hari. `;
  state.activeEntryAbsensi.Keterangan = summaryKeterangan.trim();

  google.script.run
    .withSuccessHandler((res) => {
      if (statusBadge) {
        statusBadge.textContent = 'Tersimpan';
        statusBadge.className = 'badge bg-success text-white text-xs';
      }
      if (isManual) {
        showToast(`Absensi operator "${op.Nama}" berhasil disimpan!`, 'success');
      }
    })
    .withFailureHandler((err) => {
      if (statusBadge) {
        statusBadge.textContent = 'Gagal menyimpan';
        statusBadge.className = 'badge bg-danger text-white text-xs';
      }
      showToast('Gagal menyimpan otomatis: ' + err.message, 'danger');
    })
    .saveAbsensi(state.activeEntryAbsensi, state.activeKeteranganList);
}

// ==========================================
// 3.4 REKAP & LAPORAN VIEW
// ==========================================
function renderRekapPage() {
  const appContent = document.getElementById('app-content');
  
  appContent.innerHTML = `
    <!-- Top Filter Panel -->
    <div class="card-premium mb-4">
      <div class="card-header-premium">
        <h5 class="m-0 fs-6"><i class="bi bi-funnel-fill text-primary me-2"></i>Filter Rekapitulasi</h5>
        <div class="d-flex align-items-center gap-2">
          <!-- Button Export Excel -->
          <button class="btn btn-sm btn-outline-success" id="btn-export-excel">
            <i class="bi bi-file-earmark-excel-fill me-1"></i> Excel
          </button>
          <!-- Button Export PDF -->
          <button class="btn btn-sm btn-outline-danger" id="btn-export-pdf">
            <i class="bi bi-file-earmark-pdf-fill me-1"></i> PDF
          </button>
          <!-- Button Print -->
          <button class="btn btn-sm btn-outline-secondary" id="btn-print-view">
            <i class="bi bi-printer-fill me-1"></i> Cetak / Print
          </button>
        </div>
      </div>
      <div class="card-body-premium">
        <div class="row g-3">
          <div class="col-12 col-md-3">
            <label for="rekap-periode-select" class="form-label text-xs fw-semibold">Periode</label>
            <select class="form-select" id="rekap-periode-select">
              ${state.periods.map(p => `
                <option value="${p.Kode_Periode}" ${p.Kode_Periode === state.selectedPeriodCode ? 'selected' : ''}>
                  ${p.Label} (${p.Status})
                </option>
              `).join('')}
            </select>
          </div>
          
          <div class="col-12 col-md-3">
            <label for="rekap-unit-select" class="form-label text-xs fw-semibold">Unit Kerja</label>
            <select class="form-select" id="rekap-unit-select">
              <option value="">Semua Unit</option>
              <option value="BTG">BTG</option>
              <option value="C&AHS">C&AHS</option>
              <option value="Power Distribution">Power Distribution</option>
            </select>
          </div>
          
          <div class="col-12 col-md-2">
            <label for="rekap-grup-select" class="form-label text-xs fw-semibold">Grup Shift</label>
            <select class="form-select" id="rekap-grup-select">
              <option value="">Semua Grup</option>
              <option value="A">Grup A</option>
              <option value="B">Grup B</option>
              <option value="C">Grup C</option>
              <option value="D">Grup D</option>
            </select>
          </div>
          
          <div class="col-12 col-md-2">
            <label for="rekap-search" class="form-label text-xs fw-semibold">Cari Operator</label>
            <input type="text" class="form-control" id="rekap-search" placeholder="Cari nama...">
          </div>

          <div class="col-12 col-md-2 d-flex align-items-end">
            <div class="form-check mb-2">
              <input class="form-check-input" type="checkbox" id="filter-sia-only">
              <label class="form-check-label text-xs fw-semibold" for="filter-sia-only">
                Hanya S/I/A
              </label>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Rekap Table Card -->
    <div class="card-premium mb-4">
      <div class="card-body-premium p-0">
        <div class="table-responsive">
          <table class="table align-middle mb-0 text-xs table-hover table-custom" id="rekap-main-table">
            <thead class="table-light">
              <tr>
                <th style="width: 40px;" class="text-center">No</th>
                <th style="min-width: 150px;">Nama Lengkap</th>
                <th>Unit</th>
                <th class="text-center">Grup</th>
                <th style="min-width: 250px;">Kode String Absensi</th>
                <th class="text-center" style="background-color: rgba(30,64,175,0.02)">S1</th>
                <th class="text-center" style="background-color: rgba(30,64,175,0.02)">S2</th>
                <th class="text-center" style="background-color: rgba(30,64,175,0.02)">S3</th>
                <th class="text-center">H</th>
                <th class="text-center" style="background-color: rgba(245,158,11,0.02)">S</th>
                <th class="text-center" style="background-color: rgba(245,158,11,0.02)">I</th>
                <th class="text-center" style="background-color: rgba(239,68,68,0.02)">A</th>
                <th class="text-center fw-bold">T. Shift</th>
                <th class="text-end fw-bold">Total Honor</th>
                <th style="width: 80px;" class="text-center no-print">Aksi</th>
              </tr>
            </thead>
            <tbody id="rekap-table-body">
              <!-- Skeletons -->
              ${Array(8).fill(0).map(() => `
                <tr>
                  <td><div class="skeleton skeleton-text" style="width: 20px;"></div></td>
                  <td><div class="skeleton skeleton-text" style="width: 130px;"></div></td>
                  <td><div class="skeleton skeleton-text" style="width: 60px;"></div></td>
                  <td><div class="skeleton skeleton-text" style="width: 20px;"></div></td>
                  <td><div class="skeleton skeleton-text" style="width: 240px;"></div></td>
                  <td><div class="skeleton skeleton-text" style="width: 20px;"></div></td>
                  <td><div class="skeleton skeleton-text" style="width: 20px;"></div></td>
                  <td><div class="skeleton skeleton-text" style="width: 20px;"></div></td>
                  <td><div class="skeleton skeleton-text" style="width: 20px;"></div></td>
                  <td><div class="skeleton skeleton-text" style="width: 20px;"></div></td>
                  <td><div class="skeleton skeleton-text" style="width: 20px;"></div></td>
                  <td><div class="skeleton skeleton-text" style="width: 20px;"></div></td>
                  <td><div class="skeleton skeleton-text" style="width: 30px;"></div></td>
                  <td><div class="skeleton skeleton-text" style="width: 60px;"></div></td>
                  <td><div class="skeleton skeleton-text" style="width: 50px;"></div></td>
                </tr>
              `).join('')}
            </tbody>
            <tfoot class="table-light fw-bold" id="rekap-table-footer">
              <!-- Footer summaries -->
            </tfoot>
          </table>
        </div>
      </div>
    </div>

    <!-- Hidden elements for Print View Footer -->
    <div class="print-footer container-fluid mt-5">
      <div class="row justify-content-between text-center">
        <div class="col-4">
          <p>Disetujui oleh,</p>
          <div style="height: 70px;"></div>
          <strong>Superintendent HRD</strong>
        </div>
        <div class="col-4">
          <p>Dibuat oleh,</p>
          <div style="height: 70px;"></div>
          <strong>Admin HR/K3</strong>
        </div>
      </div>
    </div>
  `;

  // Fetch Rekap Data
  fetchRekapData();

  // Wire up filter events
  document.getElementById('rekap-periode-select').addEventListener('change', (e) => {
    state.selectedPeriodCode = e.target.value;
    fetchRekapData();
  });
  
  const filterInputs = ['rekap-unit-select', 'rekap-grup-select', 'rekap-search', 'filter-sia-only'];
  filterInputs.forEach(id => {
    document.getElementById(id).addEventListener('input', filterRekapTable);
    document.getElementById(id).addEventListener('change', filterRekapTable);
  });

  // Action Buttons Wiring
  document.getElementById('btn-export-excel').addEventListener('click', handleExportExcel);
  document.getElementById('btn-export-pdf').addEventListener('click', handleExportPDF);
  document.getElementById('btn-print-view').addEventListener('click', () => {
    window.print();
  });
}

function fetchRekapData() {
  google.script.run
    .withSuccessHandler((absData) => {
      state.absensiData = absData;
      filterRekapTable();
    })
    .withFailureHandler((err) => {
      showToast('Gagal memuat rekap absensi: ' + err.message, 'danger');
    })
    .getAbsensi(state.selectedPeriodCode);
}

function filterRekapTable() {
  const unit = document.getElementById('rekap-unit-select').value;
  const grup = document.getElementById('rekap-grup-select').value;
  const search = document.getElementById('rekap-search').value.toLowerCase();
  const siaOnly = document.getElementById('filter-sia-only').checked;

  const filtered = state.absensiData.filter(a => {
    const matchUnit = unit === '' || a.Unit === unit;
    const matchGrup = grup === '' || a.Grup_Shift === grup;
    const matchSearch = a.Nama_Operator.toLowerCase().includes(search) || a.ID_Operator.toLowerCase().includes(search);
    const matchSia = !siaOnly || (a.Jml_Sakit > 0 || a.Jml_Izin > 0 || a.Jml_Alpa > 0);
    return matchUnit && matchGrup && matchSearch && matchSia;
  });

  renderRekapTable(filtered);
}

function renderRekapTable(list) {
  const tbody = document.getElementById('rekap-table-body');
  const tfoot = document.getElementById('rekap-table-footer');
  
  if (!tbody || !tfoot) return;

  if (list.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="15" class="text-center py-4 text-muted">Tidak ada rekap absensi untuk kriteria terpilih.</td>
      </tr>
    `;
    tfoot.innerHTML = '';
    return;
  }

  // Render Rows
  tbody.innerHTML = list.map((a, idx) => {
    // Generate split characters for Kode String
    const chars = a.Kode_String.split('').map(c => `
      <span class="string-kode-char" data-code="${c}">${c}</span>
    `).join('');

    return `
      <!-- Expandable Row -->
      <tr class="expandable-row" id="row-${a.ID_Record}" onclick="toggleRowDetail('${a.ID_Record}')">
        <td class="text-center text-muted">${idx + 1}</td>
        <td>
          <div class="d-flex align-items-center gap-2">
            <i class="bi bi-chevron-right text-muted-light" id="icon-${a.ID_Record}"></i>
            <strong>${a.Nama_Operator}</strong>
          </div>
        </td>
        <td><span class="badge bg-light text-dark">${a.Unit}</span></td>
        <td class="text-center font-heading fw-semibold">${a.Grup_Shift}</td>
        <td>
          <div class="string-kode-container">${chars}</div>
        </td>
        <td class="text-center text-code-1">${a.Jml_Shift1}</td>
        <td class="text-center text-code-2">${a.Jml_Shift2}</td>
        <td class="text-center text-code-3">${a.Jml_Shift3}</td>
        <td class="text-center text-code-h">${a.Jml_Holiday}</td>
        <td class="text-center text-code-s fw-semibold">${a.Jml_Sakit}</td>
        <td class="text-center text-code-i fw-semibold">${a.Jml_Izin}</td>
        <td class="text-center text-code-a fw-semibold text-danger">${a.Jml_Alpa}</td>
        <td class="text-center fw-bold text-primary">${a.Total_Shift}</td>
        <td class="text-end fw-bold text-success">Rp ${a.Total_Honorarium.toLocaleString('id-ID')}</td>
        <td class="text-center no-print" onclick="event.stopPropagation();">
          <button class="btn btn-xs btn-outline-primary py-1 px-2 text-xs" onclick="editAbsensiDirect('${a.ID_Operator}')">
            <i class="bi bi-pencil"></i>
          </button>
        </td>
      </tr>
      <!-- Collapsible Detail Row -->
      <tr class="detail-row d-none" id="detail-${a.ID_Record}">
        <td colspan="15">
          <div class="detail-container">
            <h6 class="fw-bold mb-2">Detail Absensi & Catatan Kehadiran</h6>
            <div id="detail-content-${a.ID_Record}">
              <!-- Diisi via JS saat di-expand -->
              <div class="spinner-border spinner-border-sm text-primary" role="status"></div> Loading detail...
            </div>
          </div>
        </td>
      </tr>
    `;
  }).join('');

  // Calculate Totals for Footer
  let grandS1 = 0, grandS2 = 0, grandS3 = 0, grandH = 0, grandS = 0, grandI = 0, grandA = 0, grandTotalShift = 0, grandTotalHonor = 0;
  
  list.forEach(a => {
    grandS1 += a.Jml_Shift1;
    grandS2 += a.Jml_Shift2;
    grandS3 += a.Jml_Shift3;
    grandH += a.Jml_Holiday;
    grandS += a.Jml_Sakit;
    grandI += a.Jml_Izin;
    grandA += a.Jml_Alpa;
    grandTotalShift += a.Total_Shift;
    grandTotalHonor += a.Total_Honorarium;
  });

  tfoot.innerHTML = `
    <tr>
      <td colspan="4" class="text-end">GRAND TOTAL:</td>
      <td><strong>${list.length} Operator</strong></td>
      <td class="text-center">${grandS1}</td>
      <td class="text-center">${grandS2}</td>
      <td class="text-center">${grandS3}</td>
      <td class="text-center">${grandH}</td>
      <td class="text-center">${grandS}</td>
      <td class="text-center">${grandI}</td>
      <td class="text-center text-danger">${grandA}</td>
      <td class="text-center text-primary fs-6">${grandTotalShift}</td>
      <td class="text-end text-success fs-6">Rp ${grandTotalHonor.toLocaleString('id-ID')}</td>
      <td class="no-print"></td>
    </tr>
  `;
}

// Redirect ke Entry page untuk edit
function editAbsensiDirect(opId) {
  const op = state.operators.find(o => o.ID_Operator === opId);
  if (!op) return;
  
  window.location.hash = '#entry';
  
  // Set delay agar UI ter-render lebih dulu
  setTimeout(() => {
    const el = document.getElementById('entry-periode-select');
    if (el) {
      el.value = state.selectedPeriodCode;
      selectOperatorForEntry(op);
    }
  }, 100);
}

// Expandable Detail Hari Operator
function toggleRowDetail(recordId) {
  const row = document.getElementById(`row-${recordId}`);
  const detailRow = document.getElementById(`detail-${recordId}`);
  const icon = document.getElementById(`icon-${recordId}`);
  
  if (!detailRow) return;

  if (detailRow.classList.contains('d-none')) {
    // Open
    detailRow.classList.remove('d-none');
    row.classList.add('table-primary-subtle');
    icon.className = 'bi bi-chevron-down text-primary';
    
    // Load detail
    renderOperatorDetails(recordId);
  } else {
    // Close
    detailRow.classList.add('d-none');
    row.classList.remove('table-primary-subtle');
    icon.className = 'bi bi-chevron-right text-muted-light';
  }
}

function renderOperatorDetails(recordId) {
  const container = document.getElementById(`detail-content-${recordId}`);
  const record = state.absensiData.find(a => a.ID_Record === recordId);
  
  if (!container || !record) return;

  const kets = record.Keterangan_Detail || [];
  
  let reasonHtml = '';
  if (kets.length === 0) {
    reasonHtml = `<p class="text-muted text-xs mb-0">Tidak ada catatan khusus (S/I/A) di periode ini.</p>`;
  } else {
    reasonHtml = `
      <div class="row g-2">
        ${kets.map(k => `
          <div class="col-12 col-md-6 col-lg-4">
            <div class="p-2 border rounded bg-white">
              <div class="d-flex justify-content-between mb-1">
                <span class="badge bg-${k.Kode === 'S' ? 'warning' : (k.Kode === 'I' ? 'orange' : 'danger')}-subtle text-${k.Kode === 'S' ? 'warning' : (k.Kode === 'I' ? 'orange' : 'danger')} font-heading">
                  ${k.Kode === 'S' ? 'Sakit' : (k.Kode === 'I' ? 'Izin' : 'Alpa')}
                </span>
                <span class="text-muted-xs font-monospace">${formatDateIndo(k.Tanggal)}</span>
              </div>
              <p class="text-xs mb-0 text-dark-sm"><strong>Alasan:</strong> "${k.Alasan}"</p>
            </div>
          </div>
        `).join('')}
      </div>
    `;
  }

  container.innerHTML = `
    <div class="p-3 rounded border" style="background-color: var(--bg-primary);">
      <div class="row g-3">
        <div class="col-12 col-md-6">
          <strong class="d-block mb-1 text-xs text-muted">Statistik Harian:</strong>
          <span class="badge bg-light text-dark me-1 border">Shift 1: ${record.Jml_Shift1}</span>
          <span class="badge bg-light text-dark me-1 border">Shift 2: ${record.Jml_Shift2}</span>
          <span class="badge bg-light text-dark me-1 border">Shift 3: ${record.Jml_Shift3}</span>
          <span class="badge bg-light text-dark me-1 border">Holiday: ${record.Jml_Holiday}</span>
        </div>
        <div class="col-12">
          <strong class="d-block mb-2 text-xs text-muted">Catatan Kehadiran Keterangan (S/I/A):</strong>
          ${reasonHtml}
        </div>
      </div>
    </div>
  `;
}

// === 3.5 EXPORT INTEGRATIONS ===

// Export Excel Client-side via SheetJS
function handleExportExcel() {
  const period = state.periods.find(p => p.Kode_Periode === state.selectedPeriodCode);
  const pName = period ? period.Label : state.selectedPeriodCode;
  
  const wb = XLSX.utils.book_new();

  // Helper formatting JSON to Sheet
  function formatSheetData(unitFilter = null) {
    let list = state.absensiData;
    if (unitFilter) {
      list = list.filter(a => a.Unit === unitFilter);
    }
    
    return list.map((a, i) => ({
      'No': i + 1,
      'Nama Operator': a.Nama_Operator,
      'Unit': a.Unit,
      'Grup Shift': a.Grup_Shift,
      'Kode String Absensi': a.Kode_String,
      'Shift 1': a.Jml_Shift1,
      'Shift 2': a.Jml_Shift2,
      'Shift 3': a.Jml_Shift3,
      'Holiday': a.Jml_Holiday,
      'Sakit': a.Jml_Sakit,
      'Izin': a.Jml_Izin,
      'Alpa': a.Jml_Alpa,
      'Total Shift': a.Total_Shift,
      'Honorarium (Rp)': a.Total_Honorarium
    }));
  }

  // 1. Rekap Semua
  const dataAll = formatSheetData();
  const wsAll = XLSX.utils.json_to_sheet(dataAll);
  XLSX.utils.book_append_sheet(wb, wsAll, "Rekap Semua");

  // 2. BTG
  const dataBtg = formatSheetData("BTG");
  const wsBtg = XLSX.utils.json_to_sheet(dataBtg);
  XLSX.utils.book_append_sheet(wb, wsBtg, "Unit BTG");

  // 3. C&AHS
  const dataCahs = formatSheetData("C&AHS");
  const wsCahs = XLSX.utils.json_to_sheet(dataCahs);
  XLSX.utils.book_append_sheet(wb, wsCahs, "Unit CAHS");

  // 4. Power Distribution
  const dataPd = formatSheetData("Power Distribution");
  const wsPd = XLSX.utils.json_to_sheet(dataPd);
  XLSX.utils.book_append_sheet(wb, wsPd, "Unit Power Distribution");

  // Unduh File
  XLSX.writeFile(wb, `Rekap_Absensi_Operator_${pName.replace(' ', '_')}.xlsx`);
  showToast('Laporan Excel berhasil di-export.', 'success');
}

// Export PDF Integration
function handleExportPDF() {
  const period = state.periods.find(p => p.Kode_Periode === state.selectedPeriodCode);
  const pName = period ? period.Label : state.selectedPeriodCode;

  // Jika lingkungan mock lokal, lakukan client print (karena tidak ada server PDF generator)
  if (typeof google === 'undefined' || !google.script || !google.script.run || !google.script.run.generatePDFServer) {
    showToast('Lingkungan lokal: Buka dialog cetak browser & pilih "Save as PDF".', 'info');
    window.print();
    return;
  }

  // Lingkungan GAS Asli
  showToast('Membuat PDF di server GAS, mohon tunggu...', 'info');
  google.script.run
    .withSuccessHandler((pdfUrl) => {
      if (pdfUrl) {
        window.open(pdfUrl, '_blank');
        showToast('PDF berhasil dibuat. Mengunduh...', 'success');
      } else {
        showToast('Gagal membuat PDF', 'danger');
      }
    })
    .withFailureHandler((err) => {
      showToast('Gagal export PDF: ' + err.message, 'danger');
    })
    .generatePDFServer(state.selectedPeriodCode);
}

// ==========================================
// 3.6 PERIODE VIEW
// ==========================================
function renderPeriodePage() {
  const appContent = document.getElementById('app-content');
  
  appContent.innerHTML = `
    <div class="row g-4">
      <!-- Daftar Periode -->
      <div class="col-12 col-lg-8">
        <div class="card-premium">
          <div class="card-header-premium">
            <h5 class="m-0 fs-6"><i class="bi bi-calendar-event text-primary me-2"></i>Daftar Periode Absensi</h5>
          </div>
          <div class="card-body-premium p-0">
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0 text-xs">
                <thead class="table-light">
                  <tr>
                    <th>Kode</th>
                    <th>Nama Periode</th>
                    <th>Mulai</th>
                    <th>Selesai</th>
                    <th class="text-center">Total Hari</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Aksi</th>
                  </tr>
                </thead>
                <tbody id="period-table-body">
                  ${state.periods.map(p => `
                    <tr>
                      <td><strong>${p.Kode_Periode}</strong></td>
                      <td><strong>${p.Label}</strong></td>
                      <td>${formatDateIndo(p.Tgl_Mulai)}</td>
                      <td>${formatDateIndo(p.Tgl_Selesai)}</td>
                      <td class="text-center">${p.Total_Hari} Hari</td>
                      <td class="text-center">
                        <span class="badge bg-${p.Status === 'Aktif' ? 'success' : 'secondary'}-subtle text-${p.Status === 'Aktif' ? 'success' : 'secondary'} rounded-pill">
                          ${p.Status}
                        </span>
                      </td>
                      <td class="text-center">
                        ${p.Status !== 'Aktif' ? `
                          <button class="btn btn-xs btn-outline-success px-2 text-xs py-1" onclick="setActivePeriodDirect('${p.Kode_Periode}')">
                            Aktifkan
                          </button>
                        ` : '<span class="text-muted text-xs">Sedang Aktif</span>'}
                      </td>
                    </tr>
                  `).join('')}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- Tambah Periode Baru -->
      <div class="col-12 col-lg-4">
        <div class="card-premium">
          <div class="card-header-premium">
            <h5 class="m-0 fs-6"><i class="bi bi-calendar-plus text-primary me-2"></i>Tambah Periode Baru</h5>
          </div>
          <div class="card-body-premium">
            <form id="form-periode">
              <div class="mb-3">
                <label for="p-year" class="form-label text-xs fw-semibold">Tahun</label>
                <select class="form-select" id="p-year" required>
                  <option value="2025">2025</option>
                  <option value="2026" selected>2026</option>
                  <option value="2027">2027</option>
                </select>
              </div>

              <div class="mb-3">
                <label for="p-month" class="form-label text-xs fw-semibold">Bulan</label>
                <select class="form-select" id="p-month" required>
                  <option value="01">Januari</option>
                  <option value="02">Februari</option>
                  <option value="03">Maret</option>
                  <option value="04">April</option>
                  <option value="05">Mei</option>
                  <option value="06" selected>Juni</option>
                  <option value="07">Juli</option>
                  <option value="08">Agustus</option>
                  <option value="09">September</option>
                  <option value="10">Oktober</option>
                  <option value="11">November</option>
                  <option value="12">Desember</option>
                </select>
              </div>

              <div class="p-3 bg-light rounded border mb-3 text-xs">
                <div class="d-flex justify-content-between mb-1">
                  <span class="text-muted">Tanggal Mulai (21 bln lalu):</span>
                  <strong class="text-dark" id="calc-p-mulai">-</strong>
                </div>
                <div class="d-flex justify-content-between mb-1">
                  <span class="text-muted">Tanggal Selesai (20 bln ini):</span>
                  <strong class="text-dark" id="calc-p-selesai">-</strong>
                </div>
                <div class="d-flex justify-content-between">
                  <span class="text-muted">Jumlah Hari:</span>
                  <strong class="text-primary" id="calc-p-hari">-</strong>
                </div>
              </div>

              <div class="d-grid">
                <button type="submit" class="btn btn-primary">
                  <i class="bi bi-check-circle me-1"></i> Buat Periode
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  `;

  // Auto calculate date cycle preview
  const ySel = document.getElementById('p-year');
  const mSel = document.getElementById('p-month');
  
  function updatePeriodCalcPreview() {
    const year = parseInt(ySel.value);
    const month = parseInt(mSel.value);
    
    // Perhitungan: Mulai 21 bulan lalu, Selesai 20 bulan ini
    // Contoh: Juni 2026 -> Mulai 21 Mei 2026, Selesai 20 Juni 2026
    const dateMulai = new Date(year, month - 2, 21); // Month - 2 karena 0-indexed dan bulan lalu
    const dateSelesai = new Date(year, month - 1, 20);

    const formatISO = (d) => d.toISOString().split('T')[0];
    
    const diffTime = Math.abs(dateSelesai - dateMulai);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1; // plus 1 inclusive

    document.getElementById('calc-p-mulai').textContent = formatDateIndo(formatISO(dateMulai));
    document.getElementById('calc-p-selesai').textContent = formatDateIndo(formatISO(dateSelesai));
    document.getElementById('calc-p-hari').textContent = diffDays + ' Hari';
  }

  ySel.addEventListener('change', updatePeriodCalcPreview);
  mSel.addEventListener('change', updatePeriodCalcPreview);
  updatePeriodCalcPreview(); // first preview

  // Form submit
  document.getElementById('form-periode').onsubmit = handlePeriodeFormSubmit;
}

function handlePeriodeFormSubmit(e) {
  e.preventDefault();
  
  const year = document.getElementById('p-year').value;
  const month = document.getElementById('p-month').value;
  const monthsIndo = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
  ];
  
  const label = `${monthsIndo[parseInt(month) - 1]} ${year}`;
  
  // Hitung tanggal
  const yNum = parseInt(year);
  const mNum = parseInt(month);
  const dateMulai = new Date(yNum, mNum - 2, 21);
  const dateSelesai = new Date(yNum, mNum - 1, 20);
  
  const formatISO = (d) => d.toISOString().split('T')[0];
  const diffTime = Math.abs(dateSelesai - dateMulai);
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;

  const code = getMonthShortCode(parseInt(month)) + year; // e.g. JUN2026

  // Cek jika sudah terdaftar
  const duplicate = state.periods.find(p => p.Kode_Periode === code);
  if (duplicate) {
    showToast(`Periode ${label} (${code}) sudah terdaftar.`, 'warning');
    return;
  }

  const pData = {
    Kode_Periode: code,
    Label: label,
    Tgl_Mulai: formatISO(dateMulai),
    Tgl_Selesai: formatISO(dateSelesai),
    Total_Hari: diffDays,
    Status: 'Aktif' // default jadikan aktif langsung
  };

  google.script.run
    .withSuccessHandler((res) => {
      showToast(`Periode ${label} berhasil dibuat dan diaktifkan.`, 'success');
      loadBaseData().then(() => renderPeriodePage());
    })
    .withFailureHandler((err) => {
      showToast('Gagal membuat periode: ' + err.message, 'danger');
    })
    .savePeriode(pData);
}

function setActivePeriodDirect(pCode) {
  const p = state.periods.find(per => per.Kode_Periode === pCode);
  if (!p) return;

  p.Status = 'Aktif';
  google.script.run
    .withSuccessHandler((res) => {
      showToast(`Periode ${p.Label} diaktifkan.`, 'success');
      loadBaseData().then(() => renderPeriodePage());
    })
    .withFailureHandler((err) => {
      showToast('Gagal mengaktifkan periode: ' + err.message, 'danger');
    })
    .savePeriode(p);
}

function getMonthShortCode(m) {
  const codes = ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'];
  return codes[m - 1];
}

// ==========================================
// 3.7 BULK UPLOAD HANDLERS (MODAL)
// ==========================================
// Kita letakkan di scope global/DOMContentLoaded agar bisa diakses dari modal mana saja.
document.addEventListener('DOMContentLoaded', () => {
  setupBulkUploadEvents();
});

let parsedUploadRecords = [];

function setupBulkUploadEvents() {
  const fileInput = document.getElementById('upload-file-input');
  const btnDownload = document.getElementById('btn-download-template');
  const btnSubmit = document.getElementById('btn-upload-submit');
  const btnCancel = document.getElementById('btn-upload-cancel');
  
  if (btnDownload) {
    btnDownload.addEventListener('click', downloadExcelTemplate);
  }

  if (fileInput) {
    fileInput.addEventListener('change', handleExcelUpload);
  }

  if (btnSubmit) {
    btnSubmit.addEventListener('click', submitUploadData);
  }
}

// Download Excel template buatan
function downloadExcelTemplate() {
  const period = state.periods.find(p => p.Kode_Periode === state.selectedPeriodCode) || state.currentPeriod;
  const expectedLen = period ? period.Total_Hari : 31;
  const periodLabel = period ? period.Label : 'Periode';

  const headers = ['Nama_Operator', 'Unit', 'Kode_String', 'Keterangan'];
  // Isi sampel
  const sampleData = [
    {
      'Nama_Operator': 'Ahmad Subarjo',
      'Unit': 'BTG',
      'Kode_String': '1'.repeat(expectedLen),
      'Keterangan': 'Contoh: Kerja shift 1 penuh'
    },
    {
      'Nama_Operator': 'Budi Santoso',
      'Unit': 'C&AHS',
      'Kode_String': '2'.repeat(expectedLen - 2) + 'SA',
      'Keterangan': 'Sakit dan Alpa di akhir periode'
    }
  ];

  const wb = XLSX.utils.book_new();
  const ws = XLSX.utils.json_to_sheet(sampleData, { header: headers });
  XLSX.utils.book_append_sheet(wb, ws, "Template Absensi");
  
  XLSX.writeFile(wb, `Template_Absensi_Operator_${periodLabel.replace(' ', '_')}.xlsx`);
  showToast('Template berhasil diunduh.', 'success');
}

// Baca Excel yang diupload
function handleExcelUpload(e) {
  const file = e.target.files[0];
  if (!file) return;

  const reader = new FileReader();
  reader.onload = function (evt) {
    const data = evt.target.result;
    const workbook = XLSX.read(data, { type: 'binary' });
    const sheetName = workbook.SheetNames[0];
    const sheet = workbook.Sheets[sheetName];
    const json = XLSX.utils.sheet_to_json(sheet);
    
    validateUploadedData(json);
  };
  
  reader.readAsBinaryString(file);
}

// Validasi baris demi baris data dari Excel
function validateUploadedData(rows) {
  const period = state.periods.find(p => p.Kode_Periode === state.selectedPeriodCode) || state.currentPeriod;
  const expectedLen = period.Total_Hari;
  
  const previewBody = document.getElementById('upload-preview-body');
  const previewCount = document.getElementById('preview-count');
  const step1 = document.getElementById('upload-step-1');
  const step2 = document.getElementById('upload-step-2');
  const btnSubmit = document.getElementById('btn-upload-submit');
  const btnCancel = document.getElementById('btn-upload-cancel');

  previewBody.innerHTML = '';
  parsedUploadRecords = [];

  let validCount = 0;
  let errorCount = 0;
  let summaryHtml = '';

  rows.forEach((row, i) => {
    const lineNum = i + 2; // Baris excel (mulai dari 2 karena header)
    const name = row.Nama_Operator || row.nama_operator || '';
    const unit = row.Unit || row.unit || '';
    const kodeString = row.Kode_String || row.kode_string || '';
    const keterangan = row.Keterangan || row.keterangan || '';
    
    let isRowValid = true;
    let errMsg = '';

    // 1. Validasi operator di master
    const op = state.operators.find(o => o.Nama.toLowerCase() === name.toLowerCase() && o.Unit === unit);
    if (!op) {
      isRowValid = false;
      errMsg = 'Operator tidak ditemukan di data master.';
    } 
    // 2. Validasi panjang kode string
    else if (kodeString.length !== expectedLen) {
      isRowValid = false;
      errMsg = `Panjang kode (${kodeString.length} char) tidak sesuai periode (${expectedLen} hari).`;
    }
    // 3. Validasi karakter kode string
    else {
      const invalidChars = kodeString.replace(/[123HSIA]/g, '');
      if (invalidChars.length > 0) {
        isRowValid = false;
        errMsg = `Mengandung karakter tidak valid: [${invalidChars.split('').join(',')}]`;
      }
    }

    if (isRowValid) {
      validCount++;
      parsedUploadRecords.push({
        Nama_Operator: op.Nama,
        Unit: op.Unit,
        Kode_String: kodeString,
        Keterangan: keterangan
      });
    } else {
      errorCount++;
    }

    // Append preview row
    previewBody.innerHTML += `
      <tr class="${isRowValid ? '' : 'table-danger'}">
        <td>Baris ${lineNum}</td>
        <td><strong>${name || '-'}</strong></td>
        <td>${unit || '-'}</td>
        <td class="font-monospace">${kodeString || '-'}</td>
        <td>
          <span class="badge bg-${isRowValid ? 'success' : 'danger'}-subtle text-${isRowValid ? 'success' : 'danger'}">
            ${isRowValid ? 'OK' : errMsg}
          </span>
        </td>
      </tr>
    `;
  });

  previewCount.textContent = rows.length;

  summaryHtml = `
    <div class="alert alert-${errorCount > 0 ? 'warning' : 'success'} py-2 text-xs">
      <i class="bi bi-info-circle me-1"></i> 
      <strong>Validasi Selesai:</strong> ${validCount} baris valid, ${errorCount} baris bermasalah.
      ${errorCount > 0 ? '<br><span class="text-danger">*Baris yang bermasalah tidak akan disimpan. Perbaiki file dan upload ulang untuk menyertakan semuanya.</span>' : ''}
    </div>
  `;
  document.getElementById('upload-summary-validation').innerHTML = summaryHtml;

  // Swapping modal view steps
  step1.classList.add('d-none');
  step2.classList.remove('d-none');
  
  btnSubmit.classList.remove('d-none');
  btnSubmit.disabled = parsedUploadRecords.length === 0;
  btnCancel.textContent = 'Batal';
}

// Simpan data upload bulk ke database
function submitUploadData() {
  if (parsedUploadRecords.length === 0) return;

  const btnSubmit = document.getElementById('btn-upload-submit');
  const origText = btnSubmit.innerHTML;
  btnSubmit.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Menyimpan...`;
  btnSubmit.disabled = true;

  google.script.run
    .withSuccessHandler((res) => {
      btnSubmit.innerHTML = origText;
      btnSubmit.disabled = false;
      
      const modal = bootstrap.Modal.getInstance(document.getElementById('modalUpload'));
      modal.hide();
      
      // Reset upload modal views
      document.getElementById('upload-step-1').classList.remove('d-none');
      document.getElementById('upload-step-2').classList.add('d-none');
      document.getElementById('upload-file-input').value = '';
      btnSubmit.classList.add('d-none');
      document.getElementById('btn-upload-cancel').textContent = 'Tutup';

      showToast(`Berhasil mengimpor absensi ${res.count} operator.`, 'success');
      
      // Reload current page
      handleRouting();
    })
    .withFailureHandler((err) => {
      btnSubmit.innerHTML = origText;
      btnSubmit.disabled = false;
      showToast('Gagal upload data bulk: ' + err.message, 'danger');
    })
    .saveAbsensiBulk(parsedUploadRecords, state.selectedPeriodCode);
}

// === 4. HELPER UTILITIES ===

// Format Tanggal Indonesia
function formatDateIndo(dateStr) {
  if (!dateStr) return '-';
  const d = new Date(dateStr);
  const options = { day: 'numeric', month: 'short', year: 'numeric' };
  return d.toLocaleDateString('id-ID', options);
}

// Relatif Waktu (X menit/jam yang lalu)
function formatRelativeTime(dateStr) {
  if (!dateStr) return '-';
  const date = new Date(dateStr);
  const now = new Date();
  const diffMs = now - date;
  const diffSec = Math.floor(diffMs / 1000);
  const diffMin = Math.floor(diffSec / 60);
  const diffHrs = Math.floor(diffMin / 60);

  if (diffSec < 60) return 'Baru saja';
  if (diffMin < 60) return `${diffMin} menit lalu`;
  if (diffHrs < 24) return `${diffHrs} jam lalu`;
  return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
}

// Toast Alert System Helper
function showToast(message, type = 'success') {
  const toastEl = document.getElementById('liveToast');
  const toastMsg = document.getElementById('toast-message');
  const toastIcon = document.getElementById('toast-icon');
  
  if (!toastEl) return;

  toastMsg.textContent = message;
  
  // Set Type colors & icons
  if (type === 'success') {
    toastEl.className = 'toast align-items-center text-white bg-success border-0';
    toastIcon.className = 'bi bi-check-circle-fill';
  } else if (type === 'danger') {
    toastEl.className = 'toast align-items-center text-white bg-danger border-0';
    toastIcon.className = 'bi bi-x-circle-fill';
  } else if (type === 'warning') {
    toastEl.className = 'toast align-items-center text-dark bg-warning border-0';
    toastIcon.className = 'bi bi-exclamation-triangle-fill';
  } else {
    // info
    toastEl.className = 'toast align-items-center text-white bg-info border-0';
    toastIcon.className = 'bi bi-info-circle-fill';
  }

  const toast = new bootstrap.Toast(toastEl);
  toast.show();
}
