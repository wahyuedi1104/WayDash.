<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) { header("Location: login.php"); exit; }
if (isset($_GET['logout'])) { session_destroy(); header("Location: login.php"); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WayDash Provisioning</title>
    <link rel="icon" type="image/png" href="favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: 'Segoe UI', system-ui, sans-serif; background-color: #f1f5f9; color: #1e293b; overflow-x: hidden; position: relative; }
        
        /* WATERMARK INDUK (LEBIH BESAR) */
        body::before {
            content: ""; position: fixed; top: 50%; left: 55%; transform: translate(-50%, -50%);
            width: 800px; height: 800px; background-image: url('logo_waydash.png');
            background-size: contain; background-position: center; background-repeat: no-repeat;
            opacity: 0.03; pointer-events: none; z-index: 1;
        }
        
        .wrapper { display: flex; width: 100%; min-height: 100vh; position: relative; z-index: 2; }
        
        /* SIDEBAR LIGHT THEME */
        .sidebar { width: 280px; background-color: #ffffff; color: #1e293b; display: flex; flex-direction: column; box-shadow: 4px 0 15px rgba(0,0,0,0.03); z-index: 100; border-right: 1px solid #e2e8f0; }
        .sidebar-brand { padding: 2rem 1.5rem 1rem; text-align: center; border-bottom: 1px solid #f8fafc; }
        .sidebar-brand img { width: 100%; max-width: 240px; margin-bottom: 10px; transition: 0.3s; }
        
        .menu-header { color: #94a3b8; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; padding: 1.5rem 1.5rem 0.5rem; letter-spacing: 1px; }
        .sidebar-menu { list-style: none; padding: 0 1rem; margin: 0; }
        .sidebar-menu a { display: flex; align-items: center; gap: 12px; color: #475569; padding: 0.85rem 1.2rem; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 0.95rem; margin-bottom: 0.3rem; transition: all 0.2s ease; cursor: pointer; }
        .sidebar-menu a:hover { background-color: #f1f5f9; color: #0ea5e9; }
        .sidebar-menu a.active { background-color: #0ea5e9; color: #fff; box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3); }
        
        .main-panel { flex-grow: 1; display: flex; flex-direction: column; min-width: 0; position: relative; z-index: 2; }
        .top-navbar { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(5px); padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 1px 3px rgba(0,0,0,0.05); z-index: 99; position: sticky; top: 0; }
        
        .live-clock { background: #f8fafc; border: 1px solid #e2e8f0; padding: 0.5rem 1.2rem; border-radius: 50px; font-weight: 600; color: #0f172a; font-size: 0.90rem; display: flex; align-items: center; gap: 10px; }
        .user-profile { display: flex; align-items: center; gap: 15px; border-left: 2px solid #e2e8f0; padding-left: 20px; margin-left: 20px; }
        .user-info { display: flex; flex-direction: column; text-align: right; line-height: 1.2; }
        .user-name { font-size: 0.9rem; font-weight: 700; color: #0f172a; }
        .user-role { font-size: 0.75rem; color: #64748b; font-weight: 500; }
        .user-avatar { width: 40px; height: 40px; background: linear-gradient(135deg, #0ea5e9, #0284c7); color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; box-shadow: 0 4px 6px rgba(14,165,233,0.2); }
        
        .content-area { padding: 2rem; flex-grow: 1; }
        
        /* KOTAK TABEL & WATERMARK SETIAP KOTAK */
        .card-custom { position: relative; border: none; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03); background-color: rgba(255, 255, 255, 0.95); margin-bottom: 1.5rem; overflow: hidden; }
        .card-custom::before {
            content: ""; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
            width: 350px; height: 350px; background-image: url('logo_waydash.png');
            background-size: contain; background-position: center; background-repeat: no-repeat;
            opacity: 0.05; pointer-events: none; z-index: 0;
        }
        /* Memastikan isi tabel/kotak ada di atas watermark kotak */
        .card-body, .card-header-custom, .local-filter-bar { position: relative; z-index: 1; }
        
        .card-header-custom { background-color: transparent; border-bottom: 1px solid #f1f5f9; padding: 1.25rem 1.5rem; font-weight: 700; color: #0f172a; display: flex; justify-content: space-between; align-items: center; }
        
        .table-r1 th, .table-r1 td, .table-custom th, .table-custom td { border: 1px solid #cbd5e1 !important; vertical-align: middle; text-align: center; }
        .table-custom thead th { background-color: #f8fafc; font-weight: 700; color: #334155; }
        .local-filter-bar { background-color: rgba(248, 250, 252, 0.8); border-bottom: 1px solid #e2e8f0; padding: 1rem 1.5rem; display: flex; align-items: center; gap: 12px; }
        .wa-box { background-color: #f8fafc; border-left: 4px solid #0ea5e9; padding: 1rem; border-radius: 6px; font-family: monospace; white-space: pre-wrap; font-size: 0.85rem; color: #334155; }
        
        .chart-wrapper { position: relative; height: 500px; width: 100%; }
        
        .hero-section { background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: #fff; padding: 3rem 2rem; border-radius: 16px; margin-bottom: 2rem; position: relative; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .hero-section::after { content: ""; position: absolute; right: -5%; top: -20%; width: 300px; height: 300px; background: rgba(14, 165, 233, 0.2); border-radius: 50%; filter: blur(40px); }
        
        #loadingOverlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255, 255, 255, 0.9); z-index: 9999; flex-direction: column; justify-content: center; align-items: center; backdrop-filter: blur(5px); }
    </style>
</head>
<body>

<div id="loadingOverlay" class="d-none" style="display: none;">
    <div class="spinner-grow text-primary" style="width: 3rem; height: 3rem;" role="status"></div>
    <h4 class="mt-4 fw-bold text-slate-800">Sinkronisasi Server...</h4>
    <span class="text-muted fw-semibold">Menarik data dari memori (RAM) 🚀</span>
</div>

<input type="hidden" id="filterTanggalMulai">
<input type="hidden" id="filterTanggalAkhir">
<input type="hidden" id="filterDatel" value="ALL">

<div class="wrapper">
    <nav class="sidebar">
        <div class="sidebar-brand"><img src="logo_waydash.png" alt="WayDash Logo"></div>
        <div class="menu-header">Dashboard Reports</div>
        <ul class="sidebar-menu">
            <li><a class="nav-link active" data-target="upload-section"><i class="bi bi-cloud-arrow-up-fill"></i> System Control</a></li>
            <li><a class="nav-link" data-target="rep1"><i class="bi bi-clock-history"></i> Hourly Movement</a></li>
            <li><a class="nav-link" data-target="rep2"><i class="bi bi-shield-check"></i> Checkpoint Validasi</a></li>
            <li><a class="nav-link" data-target="rep3"><i class="bi bi-diagram-3-fill"></i> Pivot Breakdown</a></li>
            <li><a class="nav-link" data-target="rep4"><i class="bi bi-whatsapp"></i> Broadcast Manager</a></li>
            <li><a class="nav-link" data-target="rep56"><i class="bi bi-graph-up-arrow"></i> Performance Trends</a></li>
            
            <li class="mt-4 border-top pt-3 px-2">
                <a href="?logout=true" class="text-danger fw-bold"><i class="bi bi-power me-2"></i> Log Out</a>
            </li>
        </ul>
    </nav>

    <div class="main-panel">
        <header class="top-navbar">
            <h5 class="m-0 fw-bold text-slate-800" style="letter-spacing: 0.5px;">WAY DASHBOARD PROVISIONING SOUTHERN AREA</h5>
            <div class="d-flex align-items-center">
                <div class="live-clock shadow-sm"><i class="bi bi-calendar2-check text-primary"></i><span id="liveClockDisplay">00:00:00</span></div>
                <div class="user-profile">
                    <div class="user-info"><span class="user-name">Wahyu Edi Suryanto</span><span class="user-role">System Administrator</span></div>
                    <div class="user-avatar"><i class="bi bi-person-fill"></i></div>
                </div>
            </div>
        </header>

        <main class="content-area">
            <div id="upload-section" class="report-section">
                <div class="hero-section text-center">
                    <h2 class="fw-bold mb-3">WayDash.</h2>
                    <p class="text-white-50 mx-auto" style="max-width: 600px;">Platform monitoring canggih untuk mengawal pergerakan data order teknisi dan visualisasi kendala lapangan.</p>
                </div>

                <div class="card card-custom border-top border-4 border-primary shadow-sm">
                    <div class="card-body p-4">
                        <label class="fw-bold text-muted small mb-3">
                            <i class="bi bi-database me-1"></i> UPDATE DATA MASTER (Opsional) 
                            <span class="fw-normal text-secondary ms-1">| Kosongkan jika hanya ingin melihat data terakhir.</span>
                        </label>
                        <div class="row g-3">
                            <div class="col-md-8">
                                <input type="file" class="form-control form-control-lg bg-light" id="fileInput" accept=".xlsx, .xls, .csv" onchange="resetFileState(this)">
                            </div>
                            <div class="col-md-4">
                                <button type="button" class="btn btn-primary btn-lg fw-bold w-100 h-100" onclick="prosesData(false)">
                                    <i class="bi bi-arrow-repeat me-2"></i> REFRESH & TARIK DATA
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="berandaInsights" class="d-none mt-4">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="card card-custom h-100 border-start border-4 border-primary">
                                <div class="card-body">
                                    <h6 class="fw-bold text-slate-800 mb-3" id="insightTitle"><i class="bi bi-lightning-fill text-warning me-1"></i> INSIGHT PERFORMA SOUTHERN</h6>
                                    <table class="table table-borderless table-sm mb-0">
                                        <tr><td class="text-muted fs-6 py-2 align-middle">Total Achievement</td><td class="fw-bold fs-5 text-primary text-end align-middle" id="insTotalAchiev">-</td></tr>
                                        <tr><td class="text-muted fs-6 py-2 align-middle">Best STO</td><td class="fw-bold fs-5 text-success text-end align-middle" id="insBestSto">-</td></tr>
                                        <tr><td class="text-muted fs-6 py-2 align-middle">Worst STO</td><td class="fw-bold fs-5 text-danger text-end align-middle" id="insWorstSto">-</td></tr>
                                        <tr><td class="text-muted fs-6 py-2 align-middle">Kendala Mayoritas</td><td class="fw-bold fs-5 text-dark text-end align-middle" id="insMostKendala">-</td></tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card card-custom h-100 border-start border-4 border-success">
                                <div class="card-body">
                                    <h6 class="fw-bold text-slate-800 mb-3"><i class="bi bi-trophy-fill text-success me-1"></i> RANKING STO (Target 90%)</h6>
                                    <div class="row">
                                        <div class="col-6 border-end"><div class="text-center small fw-bold text-success mb-2">TOP 5</div><ul class="list-unstyled small mb-0" id="listTopSto"></ul></div>
                                        <div class="col-6"><div class="text-center small fw-bold text-danger mb-2">BOTTOM 5</div><ul class="list-unstyled small mb-0" id="listBottomSto"></ul></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="dashboardWrapper" class="d-none">
                <!-- REPORT 1 -->
                <div id="rep1" class="report-section d-none">
                    <div class="card card-custom border-top border-4 border-dark">
                        <div class="local-filter-bar">
                            <span class="fw-bold text-muted small"><i class="bi bi-funnel-fill"></i> SORTIR:</span>
                            <input type="date" class="form-control form-control-sm w-auto local-start-date"> <span class="text-muted">s/d</span> <input type="date" class="form-control form-control-sm w-auto local-end-date">
                            <select class="form-select form-select-sm w-auto local-datel"><option value="ALL">ALL DATEL</option></select>
                            <button class="btn btn-sm btn-dark fw-bold px-3" onclick="applyLocalFilter(this)">Terapkan</button>
                        </div>
                        <div class="card-body p-0" style="overflow-x: auto;"><table class="table table-bordered table-r1 mb-0"><thead id="headR1"></thead><tbody id="bodyR1"></tbody></table></div>
                    </div>
                </div>

                <!-- REPORT 2 -->
                <div id="rep2" class="report-section d-none">
                    <div class="card card-custom border-top border-4 border-warning">
                        <div class="card-header-custom"><span><i class="bi bi-shield-check me-2 text-warning"></i> Checkpoint & Manja</span></div>
                        <div class="local-filter-bar">
                            <span class="fw-bold text-muted small"><i class="bi bi-funnel-fill"></i> SORTIR:</span>
                            <input type="date" class="form-control form-control-sm w-auto local-start-date"> <span class="text-muted">s/d</span> <input type="date" class="form-control form-control-sm w-auto local-end-date">
                            <select class="form-select form-select-sm w-auto local-datel"><option value="ALL">ALL DATEL</option></select>
                            <button class="btn btn-sm btn-dark fw-bold px-3" onclick="applyLocalFilter(this)">Terapkan</button>
                        </div>
                        <div class="card-body p-0" style="overflow-x: auto;"><table class="table table-sm table-custom table-hover mb-0"><thead class="table-light"><tr><th>STO</th><th>INSTCOMP</th><th>ACTCOMP</th><th>VALSTART</th><th>VALCOMP</th><th>K_TEKNIK</th><th>K_PLG</th><th style="color:red;">MANJA_EXP</th><th>MANJA_HI</th><th>MANJA_H1</th></tr></thead><tbody id="bodyR2"></tbody></table></div>
                    </div>
                </div>

                <!-- REPORT 3 -->
                <div id="rep3" class="report-section d-none">
                    <div class="card card-custom border-top border-4 border-primary">
                        <div class="card-header-custom"><span><i class="bi bi-diagram-3-fill me-2 text-primary"></i> Pivot Detail Kendala</span></div>
                        <div class="local-filter-bar">
                            <span class="fw-bold text-muted small"><i class="bi bi-funnel-fill"></i> SORTIR:</span>
                            <input type="date" class="form-control form-control-sm w-auto local-start-date"> <span class="text-muted">s/d</span> <input type="date" class="form-control form-control-sm w-auto local-end-date">
                            <select class="form-select form-select-sm w-auto local-datel"><option value="ALL">ALL DATEL</option></select>
                            <button class="btn btn-sm btn-dark fw-bold px-3" onclick="applyLocalFilter(this)">Terapkan</button>
                        </div>
                        <div class="card-body"><div class="row g-4"><div class="col-xl-3"><div class="fw-bold text-muted small mb-2">WA SUMMARY</div><div class="wa-box shadow-sm fw-bold" id="waText"></div><button class="btn btn-outline-primary w-100 mt-2 btn-sm fw-bold" onclick="copyText('waText', this)"><i class="bi bi-copy me-1"></i> Copy</button></div><div class="col-xl-9" style="overflow-x: auto;"><table class="table table-sm table-custom table-hover"><thead class="table-light" id="headR3"></thead><tbody id="bodyR3"></tbody></table></div></div></div>
                    </div>
                </div>

                <!-- REPORT 4 -->
                <div id="rep4" class="report-section d-none">
                    <div class="card card-custom mb-4 border-top border-4 border-success">
                        <div class="card-header-custom">
                            <span class="text-success fw-bold"><i class="bi bi-whatsapp me-2"></i> Laporan Manja <span id="titleMjTime" class="text-muted ms-1 fs-6"></span></span>
                            <button type="button" class="btn btn-success btn-sm fw-bold px-3" onclick="copyText('r4TextPreview', this)"><i class="bi bi-clipboard me-1"></i> Copy Format</button>
                        </div>
                        <div class="local-filter-bar">
                            <span class="fw-bold text-muted small"><i class="bi bi-funnel-fill"></i> SORTIR:</span>
                            <input type="date" class="form-control form-control-sm w-auto local-start-date"> <span class="text-muted">s/d</span> <input type="date" class="form-control form-control-sm w-auto local-end-date">
                            <select class="form-select form-select-sm w-auto local-datel"><option value="ALL">ALL DATEL</option></select>
                            <button class="btn btn-sm btn-dark fw-bold px-3" onclick="applyLocalFilter(this)">Terapkan</button>
                        </div>
                        <div class="card-body"><div class="row g-4"><div class="col-xl-4"><div class="wa-box bg-success bg-opacity-10 border-success" id="r4TextPreview" style="max-height: 400px; overflow-y: auto;"></div></div><div class="col-xl-8" style="overflow-x: auto; max-height: 400px;"><table class="table table-sm table-bordered table-hover text-center"><thead class="table-dark"><tr><th>STO</th><th>WONUM</th><th>STATUS</th></tr></thead><tbody id="bodyR4"></tbody></table></div></div></div>
                    </div>
                    
                    <div class="card card-custom border-top border-4 border-danger">
                        <div class="card-header-custom">
                            <span class="text-danger fw-bold"><i class="bi bi-exclamation-triangle-fill me-2"></i> List Pending HI & RNA <span id="titlePendingTime" class="text-muted ms-1 fs-6"></span></span>
                            <button type="button" class="btn btn-danger btn-sm fw-bold px-3" onclick="copyText('r4PendingTextPreview', this)"><i class="bi bi-clipboard me-1"></i> Copy Format</button>
                        </div>
                        <div class="card-body"><div class="row g-4"><div class="col-xl-4"><div class="wa-box bg-danger bg-opacity-10 border-danger" id="r4PendingTextPreview" style="max-height: 400px; overflow-y: auto;"></div></div><div class="col-xl-8" style="overflow-x: auto; max-height: 400px;"><table class="table table-sm table-bordered table-hover text-center" style="font-size: 0.85rem;"><thead class="table-dark"><tr><th>STO</th><th>WONUM</th><th>STATUS</th><th>MEMO FULL</th><th>ACTION</th></tr></thead><tbody id="bodyR4Pending"></tbody></table></div></div></div>
                    </div>
                </div>

                <!-- REPORT 5 & 6 -->
                <div id="rep56" class="report-section d-none">
                    <div class="card card-custom border-top border-4 border-primary">
                        <div class="card-header-custom"><span><i class="bi bi-graph-up me-2 text-primary"></i> Tren Hourly RE vs PS</span><select class="form-select form-select-sm w-auto" id="chartStoSelector" onchange="switchChartTrend()"></select></div>
                        <div class="local-filter-bar">
                            <span class="fw-bold text-muted small"><i class="bi bi-funnel-fill"></i> SORTIR:</span>
                            <input type="date" class="form-control form-control-sm w-auto local-start-date"> <span class="text-muted">s/d</span> <input type="date" class="form-control form-control-sm w-auto local-end-date">
                            <select class="form-select form-select-sm w-auto local-datel"><option value="ALL">ALL DATEL</option></select>
                            <button class="btn btn-sm btn-dark fw-bold px-3" onclick="applyLocalFilter(this)">Terapkan</button>
                        </div>
                        <div class="card-body"><div class="chart-wrapper"><canvas id="trendChart"></canvas></div></div>
                    </div>
                    <div class="card card-custom mt-4 border-top border-4 border-success">
                        <div class="card-header-custom"><span><i class="bi bi-activity me-2 text-success"></i> Kumulatif PS</span><select class="form-select form-select-sm w-auto" id="chartStoSelectorR6" onchange="switchChartR6()"></select></div>
                        <div class="card-body"><div class="chart-wrapper"><canvas id="chartR6"></canvas></div></div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
let myChart = null; let myChartR6 = null; let cachedFileObject = null; let globalChartDataPack = null; 

// Auto-load data instan
window.addEventListener('DOMContentLoaded', () => { prosesData(true); });

function updateClock() { document.getElementById('liveClockDisplay').innerHTML = new Date().toLocaleString('id-ID', { weekday: 'long', day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' }) + ' WIB'; }
setInterval(updateClock, 1000); updateClock();

const navLinks = document.querySelectorAll('.nav-link');
const reportSections = document.querySelectorAll('.report-section');
navLinks.forEach(link => {
    link.addEventListener('click', function(e) {
        e.preventDefault(); navLinks.forEach(nav => nav.classList.remove('active')); this.classList.add('active');
        reportSections.forEach(sec => sec.classList.add('d-none'));
        const targetId = this.getAttribute('data-target'); document.getElementById(targetId).classList.remove('d-none');
        if(targetId === 'rep56' && globalChartDataPack != null) { setTimeout(() => { switchChartTrend(); switchChartR6(); }, 100); }
    });
});

function resetFileState(input) { if(input.files.length > 0) cachedFileObject = input.files[0]; }

function applyLocalFilter(btn) {
    let container = btn.closest('.local-filter-bar');
    document.getElementById('filterTanggalMulai').value = container.querySelector('.local-start-date').value;
    document.getElementById('filterTanggalAkhir').value = container.querySelector('.local-end-date').value;
    document.getElementById('filterDatel').value = container.querySelector('.local-datel').value;
    prosesData(false);
}

function prosesData(isAutoLoadOrManual) {
    let fileToUpload = cachedFileObject; 
    let loadingOverlay = document.getElementById('loadingOverlay');
    loadingOverlay.classList.remove('d-none'); loadingOverlay.style.display = 'flex';
    
    let formData = new FormData(); 
    if (fileToUpload) formData.append("file", fileToUpload);
    
    if (!isAutoLoadOrManual || document.getElementById('filterTanggalMulai').value !== "") { 
        formData.append("start_date", document.getElementById('filterTanggalMulai').value); 
        formData.append("end_date", document.getElementById('filterTanggalAkhir').value); 
        formData.append("selected_datel", document.getElementById('filterDatel').value || "ALL"); 
    }

    fetch('https://degraded-scarring-approach.ngrok-free.dev/process-data', { 
        method: 'POST', body: formData, headers: { "ngrok-skip-browser-warning": "1" }
    })
    .then(res => res.json()).then(res => {
        loadingOverlay.classList.add('d-none'); loadingOverlay.style.display = 'none';
        
        if (res.status !== "success") {
            if(isAutoLoadOrManual) return; 
            return alert("Error: " + res.message);
        }
        
        if (fileToUpload) { document.getElementById('fileInput').value = ""; cachedFileObject = null; }
        document.getElementById('dashboardWrapper').classList.remove('d-none');

        let waktu = res.timestamp ? `*${res.timestamp}*` : '';
        document.getElementById('titleMjTime').innerText = waktu;
        document.getElementById('titlePendingTime').innerText = waktu;

        let syncStart = res.active_start;
        let syncEnd = res.active_end;
        
        document.getElementById('filterTanggalMulai').value = syncStart;
        document.getElementById('filterTanggalAkhir').value = syncEnd;
        document.querySelectorAll('.local-start-date').forEach(el => el.value = syncStart);
        document.querySelectorAll('.local-end-date').forEach(el => el.value = syncEnd);

        let curDatel = document.getElementById('filterDatel').value || 'ALL';
        let datelOptions = '<option value="ALL">ALL DATEL</option>';
        res.available_datels.forEach(dl => { datelOptions += `<option value="${dl}">${dl}</option>`; });
        document.getElementById('filterDatel').innerHTML = datelOptions; document.getElementById('filterDatel').value = curDatel;
        document.querySelectorAll('.local-datel').forEach(el => { el.innerHTML = datelOptions; el.value = curDatel; });

        document.getElementById('berandaInsights').classList.remove('d-none');
        
        let dEnd = new Date(res.active_end);
        let strDD = String(dEnd.getDate()).padStart(2, '0');
        let strMM = String(dEnd.getMonth() + 1).padStart(2, '0');
        let strYYYY = dEnd.getFullYear();
        document.getElementById('insightTitle').innerHTML = `<i class="bi bi-lightning-fill text-warning me-1"></i> INSIGHT PERFORMA SOUTHERN ${strDD}/${strMM}/${strYYYY}`;

        document.getElementById('insTotalAchiev').innerText = res.insights.total_achiev;
        document.getElementById('insBestSto').innerText = res.insights.best_sto;
        document.getElementById('insWorstSto').innerText = res.insights.worst_sto;
        document.getElementById('insMostKendala').innerText = res.insights.most_kendala;

        let topH = ""; res.insights.top_5.forEach(i => topH += `<li class="d-flex justify-content-between border-bottom py-2"><span><b>${i.STO}</b></span><span class="badge bg-success rounded-pill">${i.ACHIEV}</span></li>`);
        document.getElementById('listTopSto').innerHTML = topH || "<li>-</li>";
        let botH = ""; res.insights.bottom_5.forEach(i => botH += `<li class="d-flex justify-content-between border-bottom py-2"><span><b>${i.STO}</b></span><span class="badge bg-danger rounded-pill">${i.ACHIEV}</span></li>`);
        document.getElementById('listBottomSto').innerHTML = botH || "<li>-</li>";

        // RENDER R1
        let h1 = `<tr><th colspan="${4 + (res.jam_kerja.length * 2)}" class="align-middle text-center text-white fs-5 border-bottom-0" style="background-color: #0f172a; padding: 12px;">PERGERAKAN ORDER RE & PS DAILY SOUTHERN</th></tr>`;
        h1 += `<tr><th rowspan="2" class="align-middle text-white" style="background-color: #1e293b;">SERVICE AREA / HSA</th>`;
        let h2 = `<tr>`;
        res.jam_kerja.forEach(j => { h1 += `<th colspan="2" class="align-middle text-white" style="background-color: #334155;">${j}:00</th>`; h2 += `<th style="background-color: #f8d7da; color: #842029;">RE</th><th style="background-color: #d1e7dd; color: #0f5132;">PS</th>`; });
        h1 += `<th colspan="2" class="align-middle text-white" style="background-color: #1e293b;">TOTAL PS/RE</th><th rowspan="2" class="align-middle text-white" style="background-color: #1e293b;">ACHIEV</th></tr>`;
        h2 += `<th style="background-color: #f8d7da; color: #842029;">RE</th><th style="background-color: #d1e7dd; color: #0f5132;">PS</th></tr>`;
        document.getElementById('headR1').innerHTML = h1 + h2;
        
        let b1 = ""; let tot_r1 = { tot_re: 0, tot_ps: 0, jam: {} }; res.jam_kerja.forEach(j => { tot_r1.jam[j] = { re: 0, ps: 0 }; });
        res.r1.forEach(row => {
            let isMerah = row.ACHIEV_NUM < 90.0;
            let txtColor = isMerah ? 'color: #dc3545;' : 'color: #1e293b;'; 
            b1 += `<tr><td class="text-start fw-bold" style="${txtColor}">${row.STO} <span class="float-end text-muted small">${row.HSA}</span></td>`;
            res.jam_kerja.forEach(j => { b1 += `<td>${row[j+"_RE"]||''}</td><td>${row[j+"_PS"]||''}</td>`; tot_r1.jam[j].re += row[j+"_RE"]; tot_r1.jam[j].ps += row[j+"_PS"]; });
            b1 += `<td class="fw-bold">${row.TOT_RE}</td><td class="fw-bold">${row.TOT_PS}</td><td class="fw-bold" style="${txtColor}">${row.ACHIEV}</td></tr>`; 
            tot_r1.tot_re += row.TOT_RE; tot_r1.tot_ps += row.TOT_PS;
        });
        let g_ach = tot_r1.tot_re > 0 ? (tot_r1.tot_ps / tot_r1.tot_re * 100).toFixed(1) : "0.0"; 
        let gStyle = parseFloat(g_ach) >= 90.0 ? 'style="background-color: #0f172a; color: #fff;"' : 'style="background-color: #842029; color: #fff;"';
        b1 += `<tr ${gStyle}><td class="fw-bold text-end">TOTAL SOUTHERN</td>`; res.jam_kerja.forEach(j => { b1 += `<td>${tot_r1.jam[j].re}</td><td>${tot_r1.jam[j].ps}</td>`; }); b1 += `<td>${tot_r1.tot_re}</td><td>${tot_r1.tot_ps}</td><td class="fw-bold fs-6">${g_ach}%</td></tr>`;
        document.getElementById('bodyR1').innerHTML = b1;

        // RENDER R2
        let b2 = ""; let t2 = { inst:0, act:0, vals:0, valc:0, ktek:0, kplg:0, mexp:0, mhi:0, mh1:0 };
        res.r2.forEach(row => {
            b2 += `<tr><td class="fw-bold text-primary text-start px-3">${row.STO}</td><td>${row.INSTCOMP}</td><td>${row.ACTCOMP}</td><td>${row.VALSTART}</td><td>${row.VALCOMP}</td><td class="text-danger fw-bold">${row.K_TEKNIK}</td><td class="text-danger fw-bold">${row.K_PLG}</td><td class="fw-bold" style="color: red !important;">${row.MANJA_EXP}</td><td>${row.MANJA_HI}</td><td>${row.MANJA_H1}</td></tr>`;
            t2.inst += row.INSTCOMP; t2.act += row.ACTCOMP; t2.vals += row.VALSTART; t2.valc += row.VALCOMP; t2.ktek += row.K_TEKNIK; t2.kplg += row.K_PLG; t2.mexp += row.MANJA_EXP; t2.mhi += row.MANJA_HI; t2.mh1 += row.MANJA_H1;
        });
        b2 += `<tr class="table-dark fw-bold"><td class="text-end px-3">TOTAL SOUTHERN</td><td>${t2.inst}</td><td>${t2.act}</td><td>${t2.vals}</td><td>${t2.valc}</td><td class="text-danger">${t2.ktek}</td><td class="text-danger">${t2.kplg}</td><td class="text-danger">${t2.mexp}</td><td>${t2.mhi}</td><td>${t2.mh1}</td></tr>`;
        document.getElementById('bodyR2').innerHTML = b2;

        // RENDER R3
        document.getElementById('waText').innerText = res.r3_wa;
        let h3 = `<tr><th>STATUS</th><th>KENDALA</th><th>SUB KENDALA</th>`; res.r3_stos.forEach(sto => h3 += `<th>${sto}</th>`); h3 += `<th class="bg-light fw-bold">All</th></tr>`; document.getElementById('headR3').innerHTML = h3;
        let b3 = ""; let r3_data = res.r3_pivot;
        if(r3_data.length === 0) { b3 = `<tr><td colspan="${4 + res.r3_stos.length}" class="text-center text-muted py-3">Tidak ada data</td></tr>`; } 
        else {
            let statusSpans = {}; let errSpans = {};
            r3_data.forEach((row, index) => {
                if (statusSpans[row.STATUS] === undefined) { let count = 0; for (let i = index; i < r3_data.length; i++) { if (r3_data[i].STATUS === row.STATUS) count++; else break; } statusSpans[row.STATUS] = { count: count, startIndex: index }; }
                let errKey = row.STATUS + "|" + row.ERROR_KAT;
                if (errSpans[errKey] === undefined) { let count = 0; for (let i = index; i < r3_data.length; i++) { if (r3_data[i].STATUS === row.STATUS && r3_data[i].ERROR_KAT === row.ERROR_KAT) count++; else break; } errSpans[errKey] = { count: count, startIndex: index }; }
            });
            r3_data.forEach((row, index) => {
                let isTotalRow = row.STATUS === 'All' && row.ERROR_KAT === ''; let rowStyle = isTotalRow ? 'style="background-color: #f1f5f9;" class="fw-bold"' : '';
                b3 += `<tr ${rowStyle}>`;
                if (statusSpans[row.STATUS] && statusSpans[row.STATUS].startIndex === index) { b3 += `<td rowspan="${statusSpans[row.STATUS].count}" class="${isTotalRow ? 'text-end' : 'fw-bold text-start'} align-middle" ${isTotalRow ? 'colspan="3"' : ''}>${isTotalRow ? "TOTAL KENDALA" : row.STATUS}</td>`; }
                if(!isTotalRow) {
                    let errKey = row.STATUS + "|" + row.ERROR_KAT;
                    if (errSpans[errKey] && errSpans[errKey].startIndex === index) { b3 += `<td rowspan="${errSpans[errKey].count}" class="text-start align-middle ${row.ERROR_KAT ? 'text-danger fw-bold' : ''}">${row.ERROR_KAT}</td>`; }
                    b3 += `<td class="text-start text-muted" style="font-size:0.8rem;">${row.SUB_ERR}</td>`;
                }
                res.r3_stos.forEach(sto => { let val = row[sto] || 0; b3 += `<td>${val === 0 ? '-' : val}</td>`; });
                let totalVal = row.All || 0; b3 += `<td class="fw-bold bg-light">${totalVal === 0 ? '-' : totalVal}</td></tr>`;
            });
        }
        document.getElementById('bodyR3').innerHTML = b3;

        // RENDER R4
        let b4 = ""; let buildText = res.r4_mj_wa_header;
        if (!res.r4 || res.r4.length === 0) { b4 = `<tr><td colspan="3" class="text-center text-muted py-3">Tidak ada data</td></tr>`; buildText += "(kosong)"; } 
        else { res.r4.forEach(row => { b4 += `<tr><td class="fw-bold text-success align-middle">${row.STO}</td><td class="align-middle">${row.WONUM}</td><td><span class="badge bg-secondary">${row.STATUS}</span></td></tr>`; buildText += row.FORMAT_WA + "\n"; }); }
        document.getElementById('bodyR4').innerHTML = b4; document.getElementById('r4TextPreview').innerText = buildText; 

        let b4p = ""; let buildTextP = res.r4_pending_wa;
        if (!res.r4_pending || res.r4_pending.length === 0) { b4p = `<tr><td colspan="5" class="text-center text-muted py-3">Tidak ada data Pending</td></tr>`; buildTextP += "(Kosong)"; } 
        else { res.r4_pending.forEach(row => { b4p += `<tr><td class="fw-bold align-middle">${row.STO}</td><td class="text-primary align-middle">${row.WONUM}</td><td class="align-middle"><span class="badge bg-secondary">${row.STATUS}</span></td><td class="text-start text-muted text-wrap align-middle" style="max-width: 300px; font-size:0.85rem;">${row.MEMO_ASLI}</td><td class="fw-bold text-danger align-middle">${row.SIMPLIFIED}</td></tr>`; }); }
        document.getElementById('bodyR4Pending').innerHTML = b4p; document.getElementById('r4PendingTextPreview').innerText = buildTextP;

        // RENDER CHART
        globalChartDataPack = res.chart_data; 
        let selR5 = document.getElementById('chartStoSelector'); let selR6 = document.getElementById('chartStoSelectorR6');
        selR5.innerHTML = '<option value="ALL">ALL AREA</option>'; selR6.innerHTML = '<option value="ALL">ALL AREA</option>';
        res.stos.forEach(sto => { selR5.innerHTML += `<option value="${sto}">${sto}</option>`; selR6.innerHTML += `<option value="${sto}">${sto}</option>`; });
        switchChartTrend(); switchChartR6();
        
    }).catch(err => { 
        loadingOverlay.classList.add('d-none'); loadingOverlay.style.display = 'none';
        if (!isAutoLoadOrManual) { alert("Koneksi Error: Pastikan Ngrok & Uvicorn menyala di komputer server!\nDetail: " + err); }
    });
}

function copyText(elementId, btnObj) {
    const textToCopy = document.getElementById(elementId).innerText;
    navigator.clipboard.writeText(textToCopy).then(() => {
        let originalText = btnObj.innerHTML;
        btnObj.innerHTML = '<i class="bi bi-check2-all me-1"></i> Disalin!';
        setTimeout(() => { btnObj.innerHTML = originalText; }, 2000);
    });
}
function switchChartTrend() {
    if(!globalChartDataPack) return; let tArea = document.getElementById('chartStoSelector').value; let tPack = globalChartDataPack.trends[tArea] || globalChartDataPack.trends["ALL"];
    if (myChart) myChart.destroy();
    myChart = new Chart(document.getElementById('trendChart').getContext('2d'), { type: 'line', data: { labels: globalChartDataPack.labels, datasets: [{ label: 'RE', data: tPack.re, borderColor: '#ef4444', borderWidth: 2, tension: 0.3, fill: false }, { label: 'PS', data: tPack.ps, borderColor: '#22c55e', borderWidth: 2, tension: 0.3, fill: false }] }, options: { responsive: true, maintainAspectRatio: false } });
}
function switchChartR6() {
    if(!globalChartDataPack) return; let tArea = document.getElementById('chartStoSelectorR6').value; let tPack = globalChartDataPack.trends[tArea] || globalChartDataPack.trends["ALL"];
    if (myChartR6) myChartR6.destroy();
    myChartR6 = new Chart(document.getElementById('chartR6').getContext('2d'), { type: 'line', data: { labels: globalChartDataPack.labels, datasets: [{ label: 'Akumulasi PS', data: tPack.cum_ps, borderColor: '#0ea5e9', backgroundColor: 'rgba(14, 165, 233, 0.1)', borderWidth: 2, fill: true, tension: 0.4 }] }, options: { responsive: true, maintainAspectRatio: false } });
}
</script>
</body>
</html>