<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'CryptoSignal Pro')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        /* ── Reset ───────────────────────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --bg:           #0b0c0f;
            --bg-card:      #13141a;
            --bg-row:       #16171e;
            --bg-input:     rgba(255,255,255,0.05);
            --border:       rgba(255,255,255,0.07);
            --border-hover: rgba(255,255,255,0.14);
            --text:         #e2e4ec;
            --text-muted:   #7b7f93;
            --text-dim:     #44475a;
            --green:        #1fd683;
            --green-dim:    rgba(31,214,131,0.1);
            --red:          #f0455a;
            --red-dim:      rgba(240,69,90,0.1);
            --blue:         #4f8ef7;
            --blue-dim:     rgba(79,142,247,0.1);
            --amber:        #f5a623;
            --amber-dim:    rgba(245,166,35,0.1);
            --purple:       #9b6dff;
            --purple-dim:   rgba(155,109,255,0.12);
            --font-ui:      'Inter', system-ui, sans-serif;
            --font-data:    'IBM Plex Mono', monospace;
            --radius-sm:    6px;
            --radius-md:    10px;
            --radius-lg:    14px;
        }

        html, body {
            background: var(--bg);
            color: var(--text);
            font-family: var(--font-ui);
            font-size: 13px;
            line-height: 1.5;
            min-height: 100vh;
        }
        a { color: inherit; text-decoration: none; }

        /* ── Scrollbar ───────────────────────────────────────────────── */
        ::-webkit-scrollbar { width: 4px; height: 4px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 4px; }

        /* ── Topbar ──────────────────────────────────────────────────── */
        .topbar {
            position: sticky; top: 0; z-index: 200;
            height: 52px;
            background: rgba(11,12,15,0.92);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center; gap: 0;
            padding: 0 1.25rem;
        }
        .topbar-brand {
            display: flex; align-items: center; gap: 9px;
            font-size: 13px; font-weight: 600; letter-spacing: -0.01em;
            margin-right: 1.5rem; white-space: nowrap;
        }
        .brand-mark {
            width: 26px; height: 26px;
            background: linear-gradient(135deg, var(--purple), var(--blue));
            border-radius: var(--radius-sm);
            display: flex; align-items: center; justify-content: center;
            font-size: 13px; flex-shrink: 0;
        }
        .topbar-nav { display: flex; gap: 2px; }
        .nav-item {
            padding: 5px 11px; border-radius: var(--radius-sm);
            font-size: 12px; font-weight: 500; color: var(--text-muted);
            transition: color 0.15s, background 0.15s; white-space: nowrap;
        }
        .nav-item:hover { color: var(--text); background: rgba(255,255,255,0.05); }
        .nav-item.active { color: var(--text); background: rgba(255,255,255,0.07); }
        .topbar-spacer { flex: 1; }
        .topbar-right { display: flex; align-items: center; gap: 10px; }
        .live-badge {
            display: flex; align-items: center; gap: 5px;
            font-size: 11px; color: var(--text-muted);
            font-family: var(--font-data);
        }
        .live-dot {
            width: 6px; height: 6px; border-radius: 50%;
            background: var(--green);
            box-shadow: 0 0 6px var(--green);
            animation: livepulse 2s ease-in-out infinite;
        }
        @keyframes livepulse { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:0.5;transform:scale(0.85)} }

        /* ── Layout ──────────────────────────────────────────────────── */
        .page { max-width: 1380px; margin: 0 auto; padding: 1.25rem 1.25rem 3rem; }

        /* ── Cards ───────────────────────────────────────────────────── */
        .card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
        }
        .card-header {
            padding: 1rem 1.25rem 0.75rem;
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between;
        }
        .card-title { font-size: 12px; font-weight: 600; color: var(--text); text-transform: uppercase; letter-spacing: 0.07em; }
        .card-body  { padding: 1.25rem; }

        /* ── Metric tiles ────────────────────────────────────────────── */
        .metric-tile {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 12px 14px;
        }
        .metric-label {
            font-size: 10px; font-weight: 500; color: var(--text-muted);
            text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 6px;
        }
        .metric-val {
            font-family: var(--font-data); font-size: 1.25rem; font-weight: 500;
            color: var(--text); line-height: 1.2;
        }
        .metric-val.up   { color: var(--green); }
        .metric-val.down { color: var(--red);   }
        .metric-sub { font-size: 10px; color: var(--text-dim); margin-top: 3px; font-family: var(--font-data); }

        /* ── Badges ──────────────────────────────────────────────────── */
        .badge {
            display: inline-flex; align-items: center; gap: 4px;
            padding: 2px 8px; border-radius: 100px;
            font-size: 10px; font-weight: 600; letter-spacing: 0.04em;
            text-transform: uppercase; white-space: nowrap;
        }
        .badge-long    { background: var(--green-dim);  color: var(--green); }
        .badge-short   { background: var(--red-dim);    color: var(--red); }
        .badge-active  { background: var(--blue-dim);   color: var(--blue); }
        .badge-strong  { background: var(--purple-dim); color: var(--purple); }
        .badge-medium  { background: var(--amber-dim);  color: var(--amber); }
        .badge-weak    { background: rgba(255,255,255,0.05); color: var(--text-muted); }
        .badge-tp1_hit, .badge-tp2_hit, .badge-tp3_hit { background: var(--green-dim); color: var(--green); }
        .badge-sl_hit  { background: var(--red-dim); color: var(--red); }
        .badge-expired { background: rgba(255,255,255,0.05); color: var(--text-muted); }

        /* ── Buttons ─────────────────────────────────────────────────── */
        .btn {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 7px 14px; border-radius: var(--radius-sm);
            border: 1px solid var(--border); background: var(--bg-input);
            color: var(--text); font-family: var(--font-ui); font-size: 12px; font-weight: 500;
            cursor: pointer; transition: all 0.15s; white-space: nowrap;
        }
        .btn:hover { background: rgba(255,255,255,0.08); border-color: var(--border-hover); }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; }
        .btn-primary { background: var(--purple); border-color: var(--purple); color: #fff; }
        .btn-primary:hover { background: #8a5dee; }
        .btn-green { background: var(--green-dim); border-color: rgba(31,214,131,0.25); color: var(--green); }
        .btn-green:hover { background: rgba(31,214,131,0.18); }

        /* ── Form controls ───────────────────────────────────────────── */
        .select, select {
            appearance: none;
            background: var(--bg-input); border: 1px solid var(--border);
            border-radius: var(--radius-sm); color: var(--text);
            font-family: var(--font-ui); font-size: 12px; font-weight: 500;
            padding: 7px 28px 7px 11px; cursor: pointer; outline: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M0 0l5 6 5-6z' fill='%237b7f93'/%3E%3C/svg%3E");
            background-repeat: no-repeat; background-position: right 10px center;
            transition: border-color 0.15s;
        }
        .select:focus, select:focus { border-color: var(--purple); outline: none; }

        /* ── Tables ──────────────────────────────────────────────────── */
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th {
            text-align: left; padding: 9px 12px;
            font-size: 10px; font-weight: 600; color: var(--text-muted);
            text-transform: uppercase; letter-spacing: 0.08em;
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
        }
        .data-table td { padding: 10px 12px; border-bottom: 1px solid rgba(255,255,255,0.04); }
        .data-table tbody tr { transition: background 0.1s; }
        .data-table tbody tr:hover { background: rgba(255,255,255,0.02); }
        .data-table tbody tr:last-child td { border-bottom: none; }

        /* ── Utilities ───────────────────────────────────────────────── */
        .mono     { font-family: var(--font-data); }
        .text-up  { color: var(--green); }
        .text-dn  { color: var(--red); }
        .text-muted { color: var(--text-muted); }
        .text-dim   { color: var(--text-dim); }
        .text-purple { color: var(--purple); }
        .fw-500   { font-weight: 500; }
        .fw-600   { font-weight: 600; }
        .flex     { display: flex; }
        .flex-center  { display: flex; align-items: center; }
        .flex-between { display: flex; align-items: center; justify-content: space-between; }
        .gap-1 { gap: 4px; }
        .gap-2 { gap: 8px; }
        .gap-3 { gap: 12px; }
        .gap-4 { gap: 16px; }
        .mt-1  { margin-top: 0.5rem; }
        .mt-2  { margin-top: 1rem; }
        .mt-3  { margin-top: 1.25rem; }
        .grid-auto { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 10px; }
    </style>
    @stack('styles')
</head>
<body>

<nav class="topbar">
    <a href="{{ route('dashboard') }}" class="topbar-brand">
        <div class="brand-mark">⚡</div>
        CryptoSignal Pro
    </a>
    <div class="topbar-nav">
        <a href="{{ route('dashboard') }}"      class="nav-item {{ request()->routeIs('dashboard')   ? 'active' : '' }}">Dashboard</a>
        <a href="{{ route('signals.index') }}"  class="nav-item {{ request()->routeIs('signals.*')   ? 'active' : '' }}">Signals</a>
    </div>
    <div class="topbar-spacer"></div>
    <div class="topbar-right">
        <span id="lastUpdateTime" style="font-size:11px;color:var(--text-dim);font-family:var(--font-data)"></span>
        <div class="live-badge">
            <span class="live-dot"></span>
            Binance · Live
        </div>
    </div>
</nav>

<div class="page">
    @yield('content')
</div>

<script>
// Global last-update ticker
setInterval(() => {
    const el = document.getElementById('lastUpdateTime');
    if (el) el.textContent = new Date().toLocaleTimeString('en-US', { hour12: false });
}, 1000);
</script>
@stack('scripts')
</body>
</html>
