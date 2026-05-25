<?php $config = require 'config.php'; $city = htmlspecialchars($config['weather_api']['location']); ?>
<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stacja Pogodowa <?php echo $city; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { box-sizing: border-box; }

        body {
            background: linear-gradient(135deg, #06080f 0%, #0d1520 45%, #111830 100%);
            min-height: 100vh;
            font-size: 0.9rem;
            font-family: 'Inter', 'Segoe UI', sans-serif;
            color: #c8d8e8;
        }

        /* === GLASSMORPHISM CARDS === */
        .card {
            margin-bottom: 15px;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.09);
            border-radius: 14px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
            transition: box-shadow 0.2s;
        }

        .card:hover {
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
        }

        .card-header {
            background: rgba(255, 255, 255, 0.04);
            border-bottom: 1px solid rgba(255, 255, 255, 0.07);
            font-weight: 600;
            font-size: 0.85rem;
            color: #8aaac8;
            border-radius: 14px 14px 0 0 !important;
            padding: 0.7rem 1rem;
            letter-spacing: 0.2px;
        }

        .card-body { color: #c8d8e8; }

        /* === NAVBAR === */
        .navbar {
            background: rgba(6, 8, 15, 0.88) !important;
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.07);
            padding: 0.6rem 0;
        }

        .navbar-brand {
            font-size: 1.15rem;
            font-weight: 700;
            letter-spacing: -0.3px;
            color: #e0eeff !important;
        }

        /* === STAT VALUES === */
        .stat-val {
            font-size: 1.65rem;
            font-weight: 700;
            letter-spacing: -0.8px;
        }

        .stat-label {
            font-size: 0.68rem;
            color: #5a7890;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 3px;
        }

        /* === CHARTS === */
        .chart-wrapper {
            position: relative;
            height: 180px;
            width: 100%;
        }

        .year-chart-wrapper {
            position: relative;
            height: 250px;
            width: 100%;
        }

        /* === TABLE === */
        .table-month td,
        .table-month th {
            padding: 0.3rem 0.4rem;
            font-size: 0.72rem;
        }

        .table { color: #b0c8d8; }

        .table-month tbody tr:nth-child(odd) td {
            background-color: rgba(255, 255, 255, 0.05) !important;
        }

        .table-month tbody tr:nth-child(even) td {
            background-color: transparent !important;
        }

        .table-hover > tbody > tr:hover > * {
            background-color: rgba(255, 255, 255, 0.07);
            color: #e0eeff;
        }

        .thead-glass th {
            background: rgba(0, 5, 15, 0.75) !important;
            color: #5a7890 !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            position: sticky;
            top: 0;
            z-index: 1;
        }

        /* === RECORD TILES === */
        .record-tile {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.07);
            border-radius: 10px;
            padding: 10px;
            height: 100%;
            transition: background 0.2s;
        }

        .record-tile:hover { background: rgba(255, 255, 255, 0.08); }

        .record-title {
            font-size: 0.68rem;
            font-weight: 600;
            color: #5a7890;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
            text-align: center;
        }

        .record-val {
            font-weight: 700;
            font-size: 1.05rem;
            color: #ddeeff !important;
        }

        .record-date {
            font-size: 0.68rem;
            color: #f0c040;
            display: block;
            line-height: 1.2;
            margin-top: 4px;
            font-style: italic;
        }

        /* === NAVBAR LIVE DATA === */
        .live-data span {
            margin-left: 15px;
            font-weight: 500;
            font-size: 0.95rem;
        }

        .live-data strong {
            font-weight: 700;
            font-size: 1.1rem;
        }

        .live-time {
            font-size: 0.72rem;
            opacity: 0.5;
            display: block;
            text-align: right;
            margin-top: -2px;
        }

        /* === FORM CONTROLS === */
        .form-control {
            background: rgba(255, 255, 255, 0.07);
            border: 1px solid rgba(255, 255, 255, 0.13);
            color: #d0e0f0;
            border-radius: 8px;
        }

        .form-control:focus {
            background: rgba(255, 255, 255, 0.11);
            border-color: rgba(80, 140, 220, 0.6);
            color: #fff;
            box-shadow: 0 0 0 3px rgba(80, 140, 220, 0.15);
        }

        input[type="date"]::-webkit-calendar-picker-indicator { filter: invert(0.7); }

        .form-label {
            color: #5a7890;
            font-size: 0.72rem;
            letter-spacing: 0.7px;
            text-transform: uppercase;
        }

        /* === LIST GROUP === */
        .list-group-item {
            background: transparent;
            border-color: rgba(255, 255, 255, 0.06);
            color: #a0b8c8;
            padding: 0.6rem 0;
        }

        .list-group-item strong { color: #ddeeff; }
        .list-group-item .text-muted { color: #3d5868 !important; }

        /* === BORDERS === */
        .border-end { border-color: rgba(255, 255, 255, 0.08) !important; }
        .border-bottom { border-color: rgba(255, 255, 255, 0.08) !important; }

        /* === ACCENT BORDERS === */
        .card.border-primary { border-left: 3px solid rgba(60, 120, 220, 0.7) !important; }
        .card.border-warning  { border-left: 3px solid rgba(240, 180, 40, 0.7) !important; }

        /* === HISTORICAL BANNER === */
        .alert-info {
            background: rgba(30, 80, 160, 0.18);
            border: 1px solid rgba(60, 120, 220, 0.28);
            color: #80b8f0;
            border-radius: 10px;
        }

        /* === NAV PILLS === */
        .nav-pills .nav-link {
            color: #5a7890;
            font-size: 0.78rem;
            border-radius: 6px;
        }

        .nav-pills .nav-link.active {
            background: rgba(60, 120, 220, 0.45);
            color: #ddeeff;
        }

        .nav-pills .nav-link:hover:not(.active) {
            background: rgba(255, 255, 255, 0.07);
            color: #b0c8d8;
        }

        /* === BUTTON === */
        .btn-primary {
            background: rgba(60, 120, 220, 0.55);
            border: 1px solid rgba(60, 120, 220, 0.4);
            font-weight: 600;
        }

        .btn-primary:hover {
            background: rgba(60, 120, 220, 0.75);
            border-color: rgba(60, 120, 220, 0.6);
        }

        /* === SCROLLBAR === */
        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: rgba(255,255,255,0.02); }
        ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.12); border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.22); }

        .table-responsive { scrollbar-width: thin; }
    </style>
</head>

<body>

    <nav class="navbar navbar-dark mb-3 sticky-top">
        <div class="container-fluid d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <span class="navbar-brand mb-0 h1">🌤️ <?php echo $city; ?></span>
            </div>

            <div id="dstInfo" class="text-white text-center d-none d-lg-block" style="font-size: 0.9rem;">
                <span id="dstMessage" class="fw-bold">--</span>
            </div>

            <div class="text-white text-end live-data d-none d-md-block">
                <span>🌡️ <span id="navTemp">--</span></span>
                <span>💧 <span id="navHum">--</span></span>
                <span>⏲️ <span id="navPress">--</span></span>
                <small class="live-time" id="navTime">--:--</small>
            </div>

            <div class="text-white text-end live-data d-block d-md-none" style="font-size: 0.8rem;">
                <div><span id="navTempMobile">--</span> | <span id="navHumMobile">--</span></div>
                <div style="font-size: 0.7rem; opacity: 0.6" id="navTimeMobile">--:--</div>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4 pb-5">

        <!-- Baner z porównaniem historycznym -->
        <div id="historicalInfo" class="alert alert-info d-none mb-3 py-2 text-center"
            style="font-size: 0.9rem;" role="alert"></div>

        <div class="row">

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <label class="form-label">WYBIERZ DZIEŃ</label>
                        <div class="input-group">
                            <input type="date" id="datePicker" class="form-control">
                            <button class="btn btn-primary" onclick="loadData()">Pokaż</button>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">Wybrany Dzień</div>
                    <div class="card-body">
                        <div class="row text-center mb-3">
                            <div class="col-6 border-end">
                                <div class="text-danger stat-val" id="dayMax">--</div>
                                <div class="stat-label">Max Temp</div>
                            </div>
                            <div class="col-6">
                                <div class="stat-val" style="color:#4da6ff" id="dayMin">--</div>
                                <div class="stat-label">Min Temp</div>
                            </div>
                        </div>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between">
                                <span class="text-muted">Ciśnienie</span> <strong id="detPressure">--</strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span class="text-muted">Wiatr</span> <strong id="detWind">--</strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span class="text-muted">Opady</span> <strong id="detRain">--</strong>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header" id="tempHeader">🌡️ Temperatura</div>
                    <div class="card-body">
                        <div class="chart-wrapper">
                            <canvas id="tempChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header" id="pressHeader">⏲️ Ciśnienie</div>
                    <div class="card-body">
                        <div class="chart-wrapper">
                            <canvas id="pressChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">

                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Rekordy</span>
                        <ul class="nav nav-pills card-header-pills" id="recordsTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active py-0 px-2" id="curr-year-tab" data-bs-toggle="tab"
                                    data-bs-target="#curr-year-pane" type="button" role="tab"
                                    style="font-size: 0.78rem;">
                                    <span id="labelCurrYear">Current</span>
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link py-0 px-2" id="prev-year-tab" data-bs-toggle="tab"
                                    data-bs-target="#prev-year-pane" type="button" role="tab"
                                    style="font-size: 0.78rem;">
                                    <span id="labelPrevYear">Prev</span>
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link py-0 px-2" id="past-year-tab" data-bs-toggle="tab"
                                    data-bs-target="#past-year-pane" type="button" role="tab"
                                    style="font-size: 0.78rem;">
                                    <span id="labelPastYear">Past</span>
                                </button>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content" id="recordsTabContent">

                            <div class="tab-pane fade show active" id="curr-year-pane" role="tabpanel">
                                <div class="row g-2">
                                    <div class="col-6">
                                        <div class="record-tile">
                                            <div class="record-title">🌡️ Temp (<span id="titleCurrYear">--</span>)</div>
                                            <div class="d-flex justify-content-between align-items-center mb-2 border-bottom pb-1">
                                                <span class="badge bg-danger p-1" style="font-size: 0.6rem;">MAX</span>
                                                <div class="text-end lh-1">
                                                    <div class="record-val" id="currYearMax">--</div>
                                                    <span class="record-date" id="currYearMaxDate">--</span>
                                                </div>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="badge bg-primary p-1" style="font-size: 0.6rem;">MIN</span>
                                                <div class="text-end lh-1">
                                                    <div class="record-val" id="currYearMin">--</div>
                                                    <span class="record-date" id="currYearMinDate">--</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="record-tile">
                                            <div class="record-title">⏲️ Ciśnienie</div>
                                            <div class="d-flex justify-content-between align-items-center mb-2 border-bottom pb-1">
                                                <span class="badge bg-secondary p-1" style="font-size: 0.6rem;">MAX</span>
                                                <div class="text-end lh-1">
                                                    <div class="record-val" id="currYearMaxPress">--</div>
                                                    <span class="record-date" id="currYearMaxPressDate">--</span>
                                                </div>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="badge bg-secondary p-1" style="font-size: 0.6rem; opacity: 0.7">MIN</span>
                                                <div class="text-end lh-1">
                                                    <div class="record-val" id="currYearMinPress">--</div>
                                                    <span class="record-date" id="currYearMinPressDate">--</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="prev-year-pane" role="tabpanel">
                                <div class="row g-2">
                                    <div class="col-6">
                                        <div class="record-tile">
                                            <div class="record-title">🌡️ Temp (<span id="titlePrevYear">--</span>)</div>
                                            <div class="d-flex justify-content-between align-items-center mb-2 border-bottom pb-1">
                                                <span class="badge bg-danger p-1" style="font-size: 0.6rem;">MAX</span>
                                                <div class="text-end lh-1">
                                                    <div class="record-val" id="prevYearMax">--</div>
                                                    <span class="record-date" id="prevYearMaxDate">--</span>
                                                </div>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="badge bg-primary p-1" style="font-size: 0.6rem;">MIN</span>
                                                <div class="text-end lh-1">
                                                    <div class="record-val" id="prevYearMin">--</div>
                                                    <span class="record-date" id="prevYearMinDate">--</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="record-tile">
                                            <div class="record-title">⏲️ Ciśnienie</div>
                                            <div class="d-flex justify-content-between align-items-center mb-2 border-bottom pb-1">
                                                <span class="badge bg-secondary p-1" style="font-size: 0.6rem;">MAX</span>
                                                <div class="text-end lh-1">
                                                    <div class="record-val" id="prevYearMaxPress">--</div>
                                                    <span class="record-date" id="prevYearMaxPressDate">--</span>
                                                </div>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="badge bg-secondary p-1" style="font-size: 0.6rem; opacity: 0.7">MIN</span>
                                                <div class="text-end lh-1">
                                                    <div class="record-val" id="prevYearMinPress">--</div>
                                                    <span class="record-date" id="prevYearMinPressDate">--</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="past-year-pane" role="tabpanel">
                                <div class="row g-2">
                                    <div class="col-6">
                                        <div class="record-tile">
                                            <div class="record-title">🌡️ Temp (<span id="titlePastYear">--</span>)</div>
                                            <div class="d-flex justify-content-between align-items-center mb-2 border-bottom pb-1">
                                                <span class="badge bg-danger p-1" style="font-size: 0.6rem;">MAX</span>
                                                <div class="text-end lh-1">
                                                    <div class="record-val" id="pastYearMax">--</div>
                                                    <span class="record-date" id="pastYearMaxDate">--</span>
                                                </div>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="badge bg-primary p-1" style="font-size: 0.6rem;">MIN</span>
                                                <div class="text-end lh-1">
                                                    <div class="record-val" id="pastYearMin">--</div>
                                                    <span class="record-date" id="pastYearMinDate">--</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="record-tile">
                                            <div class="record-title">⏲️ Ciśnienie</div>
                                            <div class="d-flex justify-content-between align-items-center mb-2 border-bottom pb-1">
                                                <span class="badge bg-secondary p-1" style="font-size: 0.6rem;">MAX</span>
                                                <div class="text-end lh-1">
                                                    <div class="record-val" id="pastYearMaxPress">--</div>
                                                    <span class="record-date" id="pastYearMaxPressDate">--</span>
                                                </div>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="badge bg-secondary p-1" style="font-size: 0.6rem; opacity: 0.7">MIN</span>
                                                <div class="text-end lh-1">
                                                    <div class="record-val" id="pastYearMinPress">--</div>
                                                    <span class="record-date" id="pastYearMinPressDate">--</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">📅 Przegląd Miesięcy</div>
                    <div class="card-body p-0">
                        <div class="row g-0">
                            <div class="col-6" style="border-right: 1px solid rgba(255,255,255,0.06)">
                                <table class="table table-hover table-month mb-0 text-center">
                                    <thead class="thead-glass">
                                        <tr><th>Miesiąc</th><th>Max</th><th>Min</th></tr>
                                    </thead>
                                    <tbody id="monthlyTableLeft"></tbody>
                                </table>
                            </div>
                            <div class="col-6">
                                <table class="table table-hover table-month mb-0 text-center">
                                    <thead class="thead-glass">
                                        <tr><th>Miesiąc</th><th>Max</th><th>Min</th></tr>
                                    </thead>
                                    <tbody id="monthlyTableRight"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-2">
            <div class="col-lg-6 mb-3">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>📈 Trend Roczny (Min / Max)</span>
                        <small style="color:#3d5868">Ostatnie 12 miesięcy</small>
                    </div>
                    <div class="card-body">
                        <div class="year-chart-wrapper" style="height: 300px;">
                            <canvas id="yearTrendChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 mb-3">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                        <span>🌡️ Średnia Temperatura</span>
                        <ul class="nav nav-pills card-header-pills mt-2 mt-md-0" id="avgTabs" role="tablist">
                            <li class="nav-item">
                                <button class="nav-link py-0 px-2 active" onclick="loadAvgStats('today', this)" data-range="today">Dziś</button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link py-0 px-2" onclick="loadAvgStats('30days', this)" data-range="30days">30 Dni</button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link py-0 px-2" onclick="loadAvgStats('7days', this)" data-range="7days">7 Dni</button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link py-0 px-2" onclick="loadAvgStats('month', this)" data-range="month">Ten msc</button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link py-0 px-2" onclick="loadAvgStats('year', this)" data-range="year">Ten rok</button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link py-0 px-2" onclick="loadAvgStats('2025', this)" data-range="2025">2025</button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link py-0 px-2" onclick="loadAvgStats('2024', this)" data-range="2024">2024</button>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="chart-wrapper" style="height: 300px;">
                            <canvas id="avgTempChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Chart.js dark defaults
        Chart.defaults.color = '#5a7890';
        Chart.defaults.borderColor = 'rgba(255,255,255,0.06)';

        let tempChartInstance = null;
        let pressChartInstance = null;
        let yearChartInstance = null;
        let avgChartInstance = null;

        document.getElementById('datePicker').valueAsDate = new Date();

        function formatPolishDate(dateStr) {
            if (!dateStr) return '-';
            const date = new Date(dateStr);
            return date.toLocaleDateString('pl-PL', { day: 'numeric', month: 'long' });
        }

        async function loadCurrentStatus() {
            try {
                const response = await fetch('api.php?action=current');
                const data = await response.json();

                if (data) {
                    document.getElementById('navTemp').innerText = parseFloat(data.temperature).toFixed(1) + "°C";
                    document.getElementById('navHum').innerText = data.humidity + "%";
                    document.getElementById('navPress').innerText = data.pressure + " hPa";
                    document.getElementById('navTime').innerText = "Ost. pomiar: " + data.measurement_datetime.substring(11, 16);

                    document.getElementById('navTempMobile').innerText = parseFloat(data.temperature).toFixed(1) + "°C";
                    document.getElementById('navHumMobile').innerText = data.humidity + "%";
                    document.getElementById('navTimeMobile').innerText = data.measurement_datetime.substring(11, 16);

                    const histInfo = document.getElementById('historicalInfo');
                    if (data.historical_comparison && data.historical_comparison.available) {
                        const diff = data.historical_comparison.diff;
                        const diffAbs = Math.abs(diff).toFixed(1);
                        const pastTemp = data.historical_comparison.past_temp.toFixed(1);

                        let msg = '';
                        if (Math.abs(diff) < 0.1) {
                            msg = `Rok temu o tej godzinie temperatura była <strong>taka sama</strong> (${pastTemp}°C).`;
                        } else if (diff > 0) {
                            msg = `Rok temu o tej godzinie było <strong>chłodniej o ${diffAbs}°C</strong> (temp: ${pastTemp}°C).`;
                        } else {
                            msg = `Rok temu o tej godzinie było <strong>cieplej o ${diffAbs}°C</strong> (temp: ${pastTemp}°C).`;
                        }

                        histInfo.innerHTML = msg;
                        histInfo.classList.remove('d-none');
                    } else {
                        histInfo.classList.add('d-none');
                    }
                }
            } catch (e) { console.error("Błąd pobierania current status:", e); }
        }

        async function loadData() {
            const date = document.getElementById('datePicker').value;

            document.getElementById('tempHeader').innerText = `🌡️ Temperatura (${date})`;
            document.getElementById('pressHeader').innerText = `⏲️ Ciśnienie (${date})`;

            loadYearlyStats();
            loadYearlyTrend();
            loadMonthlyStats();
            loadCurrentStatus();

            const response = await fetch(`api.php?action=chart_data&date=${date}`);
            const data = await response.json();

            if (!data || data.length === 0) {
                resetDayView();
                return;
            }

            const labels = data.map(entry => entry.measurement_datetime.substring(11, 16));
            const temps = data.map(entry => parseFloat(entry.temperature));
            const presses = data.map(entry => parseInt(entry.pressure));

            const dayMin = Math.min(...temps);
            const dayMax = Math.max(...temps);

            document.getElementById('dayMin').innerText = dayMin.toFixed(1) + "°C";
            document.getElementById('dayMax').innerText = dayMax.toFixed(1) + "°C";

            const last = data[data.length - 1];
            document.getElementById('detPressure').innerText = last.pressure + " hPa";
            document.getElementById('detWind').innerText = last.wind_speed + " m/s";
            document.getElementById('detRain').innerText = (parseFloat(last.rainfall) || 0) + " mm";

            renderChart('tempChart', labels, temps, 'Temperatura', 'rgb(220, 53, 69)', tempChartInstance, (i) => tempChartInstance = i);
            renderChart('pressChart', labels, presses, 'Ciśnienie', 'rgb(25, 135, 84)', pressChartInstance, (i) => pressChartInstance = i);
        }

        async function loadYearlyTrend() {
            try {
                const response = await fetch('api.php?action=yearly_trend');
                const data = await response.json();
                if (!data || data.length === 0) return;

                const labels = data.map(e => e.date);
                const maxTemps = data.map(e => parseFloat(e.max_temp));
                const minTemps = data.map(e => parseFloat(e.min_temp));

                const ctx = document.getElementById('yearTrendChart').getContext('2d');
                if (yearChartInstance) yearChartInstance.destroy();

                yearChartInstance = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [
                            { label: 'Max Temp (°C)', data: maxTemps, borderColor: 'rgb(220, 53, 69)', backgroundColor: 'rgba(220, 53, 69, 0.1)', borderWidth: 2, pointRadius: 0, pointHoverRadius: 4, tension: 0.4 },
                            { label: 'Min Temp (°C)', data: minTemps, borderColor: 'rgb(13, 110, 253)', backgroundColor: 'rgba(13, 110, 253, 0.1)', borderWidth: 2, pointRadius: 0, pointHoverRadius: 4, tension: 0.4 }
                        ]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
                        plugins: { legend: { display: true, position: 'top' } },
                        scales: {
                            x: { display: true, grid: { display: false }, ticks: { autoSkip: true, maxTicksLimit: 12, maxRotation: 0, callback: function (val) { const d = new Date(this.getLabelForValue(val)); return d.toLocaleDateString('pl-PL', { month: 'short' }); } } },
                            y: { display: true }
                        }
                    }
                });
            } catch (e) { }
        }

        async function loadMonthlyStats() {
            try {
                const response = await fetch('api.php?action=monthly_stats');
                const data = await response.json();
                const leftBody  = document.getElementById('monthlyTableLeft');
                const rightBody = document.getElementById('monthlyTableRight');
                leftBody.innerHTML = '';
                rightBody.innerHTML = '';

                const half = Math.ceil(data.length / 2);
                data.forEach((row, idx) => {
                    const dateObj = new Date(row.month_id + "-01");
                    const monthName = dateObj.toLocaleDateString('pl-PL', { month: 'short', year: 'numeric' });
                    const formattedName = monthName.charAt(0).toUpperCase() + monthName.slice(1);
                    const tr = `<tr><td class="text-start fw-bold" style="color:#5a7890">${formattedName}</td><td class="text-danger fw-bold">${parseFloat(row.max_temp).toFixed(1)}°</td><td style="color:#4da6ff" class="fw-bold">${parseFloat(row.min_temp).toFixed(1)}°</td></tr>`;
                    (idx < half ? leftBody : rightBody).innerHTML += tr;
                });
            } catch (e) { }
        }

        async function loadYearlyStats() {
            const currentYear = new Date().getFullYear();
            const prevYear = currentYear - 1;
            const pastYear = currentYear - 2;

            document.getElementById('labelCurrYear').innerText = currentYear;
            document.getElementById('titleCurrYear').innerText = currentYear;
            document.getElementById('labelPrevYear').innerText = prevYear;
            document.getElementById('titlePrevYear').innerText = prevYear;
            document.getElementById('labelPastYear').innerText = pastYear;
            document.getElementById('titlePastYear').innerText = pastYear;

            const fetchAndFill = async (year, prefix) => {
                try {
                    const response = await fetch(`api.php?action=year_stats&year=${year}`);
                    const stats = await response.json();
                    if (!stats) return;

                    document.getElementById(prefix + 'Max').innerText = stats.max_temp ? stats.max_temp + "°" : '--';
                    document.getElementById(prefix + 'Min').innerText = stats.min_temp ? stats.min_temp + "°" : '--';
                    document.getElementById(prefix + 'MaxDate').innerText = formatPolishDate(stats.max_temp_date);
                    document.getElementById(prefix + 'MinDate').innerText = formatPolishDate(stats.min_temp_date);
                    document.getElementById(prefix + 'MaxPress').innerText = stats.max_press ? stats.max_press : '--';
                    document.getElementById(prefix + 'MinPress').innerText = stats.min_press ? stats.min_press : '--';
                    document.getElementById(prefix + 'MaxPressDate').innerText = formatPolishDate(stats.max_press_date);
                    document.getElementById(prefix + 'MinPressDate').innerText = formatPolishDate(stats.min_press_date);
                } catch (e) { console.error(`Błąd pobierania statystyk dla roku ${year}:`, e); }
            };

            await fetchAndFill(currentYear, 'currYear');
            await fetchAndFill(prevYear, 'prevYear');
            await fetchAndFill(pastYear, 'pastYear');
        }

        function renderChart(canvasId, labels, data, label, color, oldInstance, setInstance) {
            if (oldInstance) oldInstance.destroy();
            const ctx = document.getElementById(canvasId).getContext('2d');
            setInstance(new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{ label: label, data: data, borderColor: color, backgroundColor: color.replace('rgb', 'rgba').replace(')', ', 0.1)'), borderWidth: 2, pointRadius: 0, pointHoverRadius: 4, fill: true, tension: 0.4 }]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { grid: { display: false } } } }
            }));
        }

        function resetDayView() {
            document.getElementById('dayMin').innerText = "--";
            document.getElementById('dayMax').innerText = "--";
            if (tempChartInstance) tempChartInstance.destroy();
            if (pressChartInstance) pressChartInstance.destroy();
        }

        async function loadAvgStats(range, btnElement) {
            if (btnElement) {
                document.querySelectorAll('#avgTabs .nav-link').forEach(btn => btn.classList.remove('active'));
                btnElement.classList.add('active');
            }

            try {
                const response = await fetch(`api.php?action=avg_stats&range=${range}`);
                const data = await response.json();
                if (!data) return;

                const labels = data.map(e => {
                    if (range === 'today') return e.date_label.substring(11, 16);
                    if (range.includes('year') || range === '2025' || range === '2024') {
                        const d = new Date(e.date_label + "-01");
                        return d.toLocaleDateString('pl-PL', { month: 'short' });
                    }
                    const d = new Date(e.date_label);
                    return d.toLocaleDateString('pl-PL', { day: 'numeric', month: 'short' });
                });
                const values = data.map(e => parseFloat(e.avg_temp));

                const ctx = document.getElementById('avgTempChart').getContext('2d');
                if (avgChartInstance) avgChartInstance.destroy();

                avgChartInstance = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Średnia Temp (°C)',
                            data: values,
                            borderColor: 'rgb(255, 193, 7)',
                            backgroundColor: 'rgba(255, 193, 7, 0.1)',
                            borderWidth: 2,
                            pointRadius: 2,
                            pointHoverRadius: 5,
                            fill: true,
                            tension: 0.3
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            x: { grid: { display: false }, ticks: { autoSkip: true, maxTicksLimit: 10 } },
                            y: { display: true }
                        }
                    }
                });
            } catch (e) { console.error('Błąd pobierania średnich temperatur:', e); }
        }

        loadCurrentStatus();
        loadData();
        loadAvgStats('today', document.querySelector('[data-range="today"]'));

        // --- ZMIANA CZASU (DST) ---
        function getNextDSTChange() {
            const now = new Date();
            let currentYear = now.getFullYear();

            for (let year = currentYear; year <= currentYear + 1; year++) {
                let summerDate = new Date(year, 2, 31);
                let day = summerDate.getDay();
                summerDate.setDate(31 - day);
                summerDate.setHours(2, 0, 0, 0);

                let winterDate = new Date(year, 9, 31);
                day = winterDate.getDay();
                winterDate.setDate(31 - day);
                winterDate.setHours(3, 0, 0, 0);

                if (summerDate > now) return { date: summerDate, type: 'letni' };
                if (winterDate > now) return { date: winterDate, type: 'zimowy' };
            }
            return null;
        }

        function updateDSTCountdown() {
            const nextChange = getNextDSTChange();
            if (!nextChange) return;

            const now = new Date();
            const diffMs = nextChange.date - now;

            if (diffMs <= 0) {
                setTimeout(updateDSTCountdown, 1000);
                return;
            }

            const days = Math.floor(diffMs / (1000 * 60 * 60 * 24));
            const hours = Math.floor((diffMs % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));

            const typeLabel = nextChange.type === 'letni' ? 'letni' : 'zimowy';
            const html = `Do zmiany na czas ${typeLabel}: <span style="font-weight: 800; color: #ffeb3b;">${days} dni, ${hours} godz, ${minutes} min</span>`;

            const el = document.getElementById('dstMessage');
            if (el) el.innerHTML = html;
        }

        setInterval(updateDSTCountdown, 1000);
        updateDSTCountdown();
    </script>

</body>
</html>
