@extends('layouts.app')
@section('title', 'Dashboard — CryptoSignal Pro')

@push('styles')
<style>
.controls-bar { display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-bottom:12px; }
.controls-bar .page-title { font-size:14px; font-weight:600; margin-right:auto; display:flex; align-items:center; gap:8px; }
.coin-search-wrap { position:relative; }
#coinSearchInput {
    width:170px; padding:7px 30px 7px 11px;
    background:var(--bg-input); border:1px solid var(--border);
    border-radius:var(--radius-sm); color:var(--text);
    font-family:var(--font-ui); font-size:12px; outline:none; transition:border-color .15s;
}
#coinSearchInput:focus { border-color:var(--purple); }
.coin-search-arrow { position:absolute; right:10px; top:50%; transform:translateY(-50%); font-size:9px; color:var(--text-dim); pointer-events:none; }
#coinDropdown {
    position:absolute; top:calc(100% + 4px); left:0; z-index:999;
    width:240px; max-height:300px; overflow-y:auto;
    background:var(--bg-card); border:1px solid var(--border);
    border-radius:var(--radius-md); box-shadow:0 8px 32px rgba(0,0,0,.6); display:none;
}
.coin-dd-item { display:flex; justify-content:space-between; align-items:center; padding:8px 12px; cursor:pointer; font-size:12px; border-bottom:1px solid rgba(255,255,255,.04); transition:background .1s; }
.coin-dd-item:hover,.coin-dd-item.sel { background:rgba(155,109,255,.1); }
.coin-dd-item:last-child { border-bottom:none; }
.coin-dd-sym { font-weight:600; }
.coin-dd-price { font-family:var(--font-data); font-size:11px; }
.tf-group { display:flex; gap:3px; }
.tf-btn { padding:5px 10px; border-radius:var(--radius-sm); border:1px solid var(--border); background:transparent; color:var(--text-muted); font-family:var(--font-ui); font-size:11px; font-weight:500; cursor:pointer; transition:all .15s; }
.tf-btn:hover { color:var(--text); border-color:var(--border-hover); }
.tf-btn.active { background:var(--purple); border-color:var(--purple); color:#fff; }
.metric-strip { display:grid; grid-template-columns:repeat(6,1fr); gap:8px; margin-bottom:12px; }
@keyframes flashUp   { 0%{color:var(--green)} 100%{color:inherit} }
@keyframes flashDown { 0%{color:var(--red)}   100%{color:inherit} }
.flash-up   { animation:flashUp   .5s ease; }
.flash-down { animation:flashDown .5s ease; }
.dash-grid { display:grid; grid-template-columns:1fr 300px; gap:12px; }
.dash-main { min-width:0; display:flex; flex-direction:column; gap:12px; }
.dash-side  { display:flex; flex-direction:column; gap:12px; }
.chart-wrap { position:relative; height:260px; }
.live-bar { display:flex; align-items:center; justify-content:space-between; padding:8px 0 0; border-top:1px solid var(--border); margin-top:10px; font-size:11px; font-family:var(--font-data); flex-wrap:wrap; gap:12px; }
.lb-item { display:flex; flex-direction:column; gap:1px; }
.lb-label { font-size:9px; color:var(--text-dim); text-transform:uppercase; letter-spacing:.07em; }
.lb-val { font-weight:500; }
.ob-row { display:grid; grid-template-columns:1fr auto auto; gap:4px; align-items:center; padding:3px 0; border-bottom:1px solid rgba(255,255,255,.03); font-family:var(--font-data); font-size:11px; }
.ob-row:last-child { border-bottom:none; }
.ob-mid { text-align:center; padding:6px 0; margin:4px 0; font-family:var(--font-data); font-size:13px; font-weight:600; border-top:1px solid var(--border); border-bottom:1px solid var(--border); }
.ind-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:8px; }
.ind-tile { background:rgba(255,255,255,.03); border:1px solid var(--border); border-radius:var(--radius-md); padding:10px 12px; }
.ind-label { font-size:10px; color:var(--text-muted); text-transform:uppercase; letter-spacing:.07em; margin-bottom:4px; }
.ind-val { font-family:var(--font-data); font-size:1rem; font-weight:500; }
.ind-sig { font-size:10px; margin-top:2px; }
.sig-bullish,.sig-bullish_cross,.sig-oversold { color:var(--green); }
.sig-bearish,.sig-bearish_cross,.sig-overbought { color:var(--red); }
.sig-neutral,.sig-none { color:var(--text-muted); }
.signal-card-wrap { border-radius:var(--radius-lg); overflow:hidden; border:1px solid var(--border); }
.sc-header { padding:12px 16px; display:flex; align-items:center; justify-content:space-between; }
.sc-header.long  { background:rgba(31,214,131,.08); border-bottom:1px solid rgba(31,214,131,.15); }
.sc-header.short { background:rgba(240,69,90,.08);  border-bottom:1px solid rgba(240,69,90,.15); }
.sc-dir { font-size:18px; font-weight:700; letter-spacing:-.02em; }
.sc-dir.long { color:var(--green); } .sc-dir.short { color:var(--red); }
.sc-body { padding:14px 16px; background:var(--bg-card); }
.sc-row { display:flex; justify-content:space-between; align-items:center; padding:5px 0; border-bottom:1px solid rgba(255,255,255,.04); font-size:12px; }
.sc-row:last-child { border-bottom:none; }
.sc-key { color:var(--text-muted); } .sc-val { font-family:var(--font-data); font-weight:500; }
.signal-empty { padding:2rem; text-align:center; border:1px dashed rgba(255,255,255,.08); border-radius:var(--radius-lg); }
.wl-input { width:100%; padding:6px 10px; margin-bottom:8px; background:var(--bg-input); border:1px solid var(--border); border-radius:var(--radius-sm); color:var(--text); font-size:11px; font-family:var(--font-ui); outline:none; }
.wl-input:focus { border-color:var(--purple); }
.coin-row { display:flex; align-items:center; justify-content:space-between; padding:7px 4px; border-bottom:1px solid rgba(255,255,255,.03); cursor:pointer; border-radius:4px; transition:background .1s; }
.coin-row:hover { background:rgba(255,255,255,.03); }
.coin-row.active { background:rgba(155,109,255,.08); }
.coin-row:last-child { border-bottom:none; }
.coin-sym { font-weight:600; font-size:12px; }
.coin-sub { font-size:10px; color:var(--text-muted); }
.coin-price { font-family:var(--font-data); font-size:11px; text-align:right; }
.coin-chg { font-family:var(--font-data); font-size:10px; text-align:right; }
.ws-dot { display:inline-block; width:7px; height:7px; border-radius:50%; background:var(--text-dim); transition:background .4s; vertical-align:middle; }
.ws-dot.live { background:var(--green); box-shadow:0 0 6px var(--green); }
.spin { animation:spin .7s linear infinite; display:inline-block; }
@keyframes spin { to{transform:rotate(360deg)} }
</style>
@endpush

@section('content')

<div class="controls-bar">
    <div class="page-title">
        <span class="ws-dot" id="wsDot"></span>
        <span id="pageLabel">Market Dashboard</span>
    </div>
    <div class="coin-search-wrap">
        <input type="text" id="coinSearchInput" placeholder="Search coin…" autocomplete="off"
               oninput="filterDd(this.value)" onfocus="onFocus()" onblur="onBlur()">
        <span class="coin-search-arrow">▾</span>
        <div id="coinDropdown"></div>
    </div>
    <div class="tf-group">
        @foreach(['1m','5m','15m','1h','4h','1d','1w'] as $tf)
        <button class="tf-btn {{ $tf==='15m'?'active':'' }}" data-tf="{{ $tf }}" onclick="setTf('{{ $tf }}')">{{ $tf }}</button>
        @endforeach
    </div>
    <button class="btn" onclick="hardRefresh()"><span id="refreshIcon">↻</span> Refresh</button>
</div>

<div class="metric-strip">
    <div class="metric-tile">
        <div class="metric-label">Mark Price</div>
        <div class="metric-val mono" id="mPrice">—</div>
        <div class="metric-sub mono" id="mSymbol">Futures</div>
    </div>
    <div class="metric-tile">
        <div class="metric-label">24h Change</div>
        <div class="metric-val" id="mChange">—</div>
        <div class="metric-sub mono" id="mChangeAbs">—</div>
    </div>
    <div class="metric-tile">
        <div class="metric-label">24h High</div>
        <div class="metric-val mono" id="mHigh">—</div>
        <div class="metric-sub">Daily high</div>
    </div>
    <div class="metric-tile">
        <div class="metric-label">24h Low</div>
        <div class="metric-val mono" id="mLow">—</div>
        <div class="metric-sub">Daily low</div>
    </div>
    <div class="metric-tile">
        <div class="metric-label">Volume 24h</div>
        <div class="metric-val mono" id="mVol" style="font-size:1rem">—</div>
        <div class="metric-sub">USDT</div>
    </div>
    <div class="metric-tile">
        <div class="metric-label">Funding Rate</div>
        <div class="metric-val mono" id="mFunding" style="font-size:1rem">—</div>
        <div class="metric-sub mono" id="mFundingSub">Next funding</div>
    </div>
</div>

<div class="dash-grid">
    <div class="dash-main">

        <div class="card">
            <div class="card-header">
                <span class="card-title" id="chartTitle">BTC/USDT · 15m</span>
                <span style="font-size:10px;color:var(--text-dim)">Binance Futures · Live</span>
            </div>
            <div class="card-body" style="padding:1rem">
                <div class="chart-wrap"><canvas id="priceChart"></canvas></div>
                <div class="live-bar">
                    <div class="lb-item"><span class="lb-label">Open</span><span class="lb-val" id="lbO">—</span></div>
                    <div class="lb-item"><span class="lb-label">High</span><span class="lb-val text-up" id="lbH">—</span></div>
                    <div class="lb-item"><span class="lb-label">Low</span><span class="lb-val text-dn" id="lbL">—</span></div>
                    <div class="lb-item"><span class="lb-label">Close</span><span class="lb-val" id="lbC">—</span></div>
                    <div class="lb-item"><span class="lb-label">Volume</span><span class="lb-val" id="lbV">—</span></div>
                    <div class="lb-item"><span class="lb-label">Trades</span><span class="lb-val" id="lbT">—</span></div>
                    <div class="lb-item" style="margin-left:auto"><span class="lb-label">Updated</span><span class="lb-val text-dim" id="lbTime">—</span></div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <span class="card-title">Indicators</span>
                <button class="btn" style="font-size:11px;padding:4px 10px" onclick="loadIndicators()"><span id="indIcon">↻</span> Calculate</button>
            </div>
            <div class="card-body">
                <div class="ind-grid" id="indGrid">
                    @foreach(['RSI 14','MACD','EMA 9','EMA 21','EMA 50','BB Width'] as $i)
                    <div class="ind-tile"><div class="ind-label">{{ $i }}</div><div class="ind-val text-dim">—</div><div class="ind-sig text-dim">Waiting</div></div>
                    @endforeach
                </div>
                <div id="trendBadge" style="margin-top:10px;display:none">
                    <span style="font-size:11px;color:var(--text-muted)">Overall trend: </span>
                    <span id="trendLabel" class="badge"></span>
                    <span id="trendStrength" style="font-size:11px;color:var(--text-muted);margin-left:6px"></span>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <span class="card-title">Signal Generator <span style="font-size:10px;color:var(--text-muted);font-weight:400">(Futures)</span></span>
                <button class="btn btn-primary" id="signalBtn" onclick="generateSignal()">⚡ Generate signal</button>
            </div>
            <div class="card-body" id="signalBody">
                <div class="signal-empty">
                    <div style="font-size:28px;margin-bottom:10px;opacity:.4">📡</div>
                    <div style="font-weight:500;margin-bottom:4px">No signal yet</div>
                    <div class="text-muted">Signals are generated from Binance Futures data (mark price, volume, OHLCV).</div>
                </div>
            </div>
        </div>
    </div>

    <div class="dash-side">
        <div class="card">
            <div class="card-header">
                <span class="card-title">Order Book</span>
                <span style="font-size:10px;color:var(--text-dim)" id="obLabel">—</span>
            </div>
            <div class="card-body" style="padding:10px 14px">
                <div style="display:grid;grid-template-columns:1fr auto auto;gap:4px;margin-bottom:4px">
                    <span style="font-size:9px;color:var(--text-dim);text-transform:uppercase;letter-spacing:.07em">Price</span>
                    <span style="font-size:9px;color:var(--text-dim);text-transform:uppercase;letter-spacing:.07em">Size</span>
                    <span style="font-size:9px;color:var(--text-dim);text-transform:uppercase;letter-spacing:.07em">Total</span>
                </div>
                <div id="obAsks"></div>
                <div class="ob-mid" id="obMid">—</div>
                <div id="obBids"></div>
            </div>
        </div>

        <div class="card" style="flex:1">
            <div class="card-header">
                <span class="card-title">Watchlist</span>
                <span style="font-size:10px;color:var(--text-dim)" id="wlCount">Loading…</span>
            </div>
            <div class="card-body" style="padding:8px 12px">
                <input class="wl-input" id="wlSearch" placeholder="Filter pairs…" oninput="filterWl(this.value)">
                <div id="coinList" style="max-height:400px;overflow-y:auto">
                    <div class="text-muted" style="font-size:11px;padding:12px 0;text-align:center">Loading Binance Futures pairs…</div>
                </div>
            </div>
        </div>
    </div>
</div>

@if($recentSignals->count() > 0)
<div class="card mt-3">
    <div class="card-header">
        <span class="card-title">Recent signals</span>
        <a href="{{ route('signals.index') }}" style="font-size:11px;color:var(--text-muted)">View all →</a>
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Coin</th><th>Type</th><th>Mode</th>
                <th style="text-align:right">Entry</th>
                <th style="text-align:right">TP1</th><th style="text-align:right">TP2</th><th style="text-align:right">TP3</th>
                <th style="text-align:right">Stop loss</th>
                <th style="text-align:right">Lev.</th><th style="text-align:right">Conf.</th><th style="text-align:right">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($recentSignals as $s)
            <tr onclick="window.location='{{ route('signals.show',$s) }}'" style="cursor:pointer">
                <td><div class="fw-600">{{ str_replace('USDT','',$s->symbol) }}</div><div class="text-dim" style="font-size:10px">{{ $s->interval }}</div></td>
                <td><span class="badge badge-{{ $s->trade_type }}">{{ strtoupper($s->trade_type) }}</span></td>
                <td class="text-muted">{{ $s->mode }}</td>
                <td class="mono" style="text-align:right">${{ number_format($s->entry_price,4) }}</td>
                <td class="mono text-up" style="text-align:right">${{ number_format($s->take_profit_1,4) }}</td>
                <td class="mono text-up" style="text-align:right">${{ number_format($s->take_profit_2,4) }}</td>
                <td class="mono text-up" style="text-align:right">${{ number_format($s->take_profit_3,4) }}</td>
                <td class="mono text-dn" style="text-align:right">${{ number_format($s->stop_loss,4) }}</td>
                <td class="mono fw-600" style="text-align:right;color:var(--amber)">{{ $s->leverage }}x</td>
                <td class="mono" style="text-align:right">{{ $s->confidence }}%</td>
                <td style="text-align:right"><span class="badge badge-{{ $s->status }}">{{ str_replace('_',' ',$s->status) }}</span></td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<script>
'use strict';
// ═══════════════════════════════════════════════════════════════════════
//  CONSTANTS & STATE
// ═══════════════════════════════════════════════════════════════════════
const FAPI    = 'https://fapi.binance.com/fapi/v1';
const FSTREAM = 'wss://fstream.binance.com/ws';

let sym       = 'BTCUSDT';
let tf        = '15m';
let chart     = null;
let allPairs  = [];
let prevPrice = null;

// ONE WebSocket per purpose — strictly managed
let wsA = null;  // markPrice@1s  → price tile (guaranteed every 1s)
let wsB = null;  // kline_TF      → chart + OHLCV bar
let wsC = null;  // depth20@100ms → order book
let wsD = null;  // ticker        → 24h stats
let wsE = null;  // !miniTicker   → watchlist all-coins

// ═══════════════════════════════════════════════════════════════════════
//  HELPERS
// ═══════════════════════════════════════════════════════════════════════
function fmt(n) {
    const v = parseFloat(n);
    if (!isFinite(v) || v === 0) return '—';
    if (v >= 10000)  return v.toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2});
    if (v >= 1000)   return v.toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2});
    if (v >= 1)      return v.toFixed(4);
    if (v >= 0.01)   return v.toFixed(5);
    if (v >= 0.001)  return v.toFixed(6);
    return parseFloat(v.toPrecision(4)).toString();
}
function fmtVol(v) {
    const n=parseFloat(v); if(!isFinite(n)||n===0) return '—';
    if(n>=1e9) return (n/1e9).toFixed(2)+'B';
    if(n>=1e6) return (n/1e6).toFixed(1)+'M';
    if(n>=1e3) return (n/1e3).toFixed(1)+'K';
    return n.toFixed(0);
}
function fmtChg(c) {
    const v=parseFloat(c); if(!isFinite(v)) return '—';
    return (v>=0?'+':'')+v.toFixed(2)+'%';
}
function el(id) { return document.getElementById(id); }
function setText(id, val) { const e=el(id); if(e) e.textContent=val; }
function setDot(live) { el('wsDot').className='ws-dot'+(live?' live':''); }

// Close a WS safely without triggering auto-reconnect
function closeWs(ws) {
    if (!ws) return null;
    ws._dead = true;   // flag checked in onclose
    try { ws.close(); } catch(e) {}
    return null;
}

// Create a WebSocket with auto-reconnect unless _dead flag is set
function makeWs(url, onmsg, onopen) {
    let ws;
    function connect() {
        ws = new WebSocket(url);
        ws.onopen    = () => { setDot(true); if(onopen) onopen(); };
        ws.onerror   = () => setDot(false);
        ws.onclose   = () => { setDot(false); if(!ws._dead) setTimeout(connect, 3000); };
        ws.onmessage = onmsg;
    }
    connect();
    // Return a proxy so caller can close it
    return { close() { ws._dead=true; try{ws.close()}catch(e){} } };
}

// ═══════════════════════════════════════════════════════════════════════
//  1. LOAD ALL FUTURES PERPETUAL USDT PAIRS
// ═══════════════════════════════════════════════════════════════════════
async function loadAllPairs() {
    try {
        const [infoRes, tickRes] = await Promise.all([
            fetch(FAPI+'/exchangeInfo'),
            fetch(FAPI+'/ticker/24hr'),
        ]);
        const info    = await infoRes.json();
        const tickers = await tickRes.json();

        const tmap = {};
        tickers.forEach(t => { tmap[t.symbol]=t; });

        allPairs = (info.symbols||[])
            .filter(s => s.quoteAsset==='USDT' && s.status==='TRADING' && s.contractType==='PERPETUAL')
            .map(s => {
                const t = tmap[s.symbol]||{};
                return {
                    symbol:    s.symbol,
                    baseAsset: s.baseAsset,
                    price:     parseFloat(t.lastPrice)||null,
                    change:    parseFloat(t.priceChangePercent)||null,
                    vol:       parseFloat(t.quoteVolume)||0,
                };
            })
            .sort((a,b)=>(b.vol||0)-(a.vol||0));

        setText('wlCount', allPairs.length+' pairs');
        renderWl(allPairs.slice(0,80));
        buildDd(allPairs.slice(0,30));

    } catch(e) { console.error('loadAllPairs',e); }
}

// ═══════════════════════════════════════════════════════════════════════
//  2. SEED MAIN METRICS via REST (called once on coin switch)
// ═══════════════════════════════════════════════════════════════════════
async function seedMetrics() {
    try {
        const t = await fetch(`${FAPI}/ticker/24hr?symbol=${sym}`).then(r=>r.json());
        setChange(parseFloat(t.priceChangePercent), parseFloat(t.priceChange));
        setText('mHigh', fmt(parseFloat(t.highPrice)));
        setText('mLow',  fmt(parseFloat(t.lowPrice)));
        setText('mVol',  fmtVol(parseFloat(t.quoteVolume)));
        setText('mSymbol', sym);

        // Only set price from REST if WS hasn't arrived yet
        if (el('mPrice').textContent === '—') {
            setPrice(parseFloat(t.lastPrice));
        }

        const p = allPairs.find(x=>x.symbol===sym);
        setText('pageLabel', p ? p.baseAsset+'/USDT' : sym);
        setText('chartTitle', (p?p.baseAsset+'/USDT':sym)+' · '+tf);
        setText('obLabel', sym);
    } catch(e) {}
}

// ═══════════════════════════════════════════════════════════════════════
//  3. CHART
// ═══════════════════════════════════════════════════════════════════════
async function fetchCandles() {
    try {
        const data = await fetch(`${FAPI}/klines?symbol=${sym}&interval=${tf}&limit=100`).then(r=>r.json());
        if(!Array.isArray(data)) return;

        const fmtT = ms => {
            if (tf.includes('d')||tf.includes('w'))
                return new Date(ms).toLocaleDateString('en-US',{month:'short',day:'numeric'});
            return new Date(ms).toLocaleTimeString('en-US',{hour:'2-digit',minute:'2-digit',hour12:false});
        };
        const labels = data.map(c=>fmtT(c[0]));
        const closes = data.map(c=>parseFloat(c[4]));
        const highs  = data.map(c=>parseFloat(c[2]));
        const lows   = data.map(c=>parseFloat(c[3]));
        renderChart(labels,closes,highs,lows);

        const last = data[data.length-1];
        setText('lbO', fmt(last[1]));
        setText('lbH', fmt(last[2]));
        setText('lbL', fmt(last[3]));
        setText('lbC', fmt(last[4]));
        setText('lbV', fmtVol(last[7]));
        setText('lbT', parseInt(last[8]).toLocaleString());
        setText('chartTitle', sym.replace('USDT','/USDT')+' · '+tf);
    } catch(e) { console.error('fetchCandles',e); }
}

function renderChart(labels,closes,highs,lows) {
    const ctx=el('priceChart').getContext('2d');
    const isUp=closes.at(-1)>=closes[0];
    const col=isUp?'#1fd683':'#f0455a';
    const fill=isUp?'rgba(31,214,131,.06)':'rgba(240,69,90,.06)';
    if(chart) chart.destroy();
    chart=new Chart(ctx,{
        type:'line',
        data:{labels,datasets:[
            {label:'Close',data:closes,borderColor:col,backgroundColor:fill,borderWidth:1.5,pointRadius:0,tension:0.3,fill:true},
            {label:'High', data:highs, borderColor:'rgba(255,255,255,.07)',borderWidth:1,pointRadius:0,tension:0.3,fill:false},
            {label:'Low',  data:lows,  borderColor:'rgba(255,255,255,.07)',borderWidth:1,pointRadius:0,tension:0.3,fill:false},
        ]},
        options:{
            responsive:true,maintainAspectRatio:false,animation:false,
            plugins:{legend:{display:false},tooltip:{mode:'index',intersect:false,
                callbacks:{label:c=>c.dataset.label+': '+fmt(c.raw)}}},
            scales:{
                x:{grid:{display:false},ticks:{maxTicksLimit:10,color:'#44475a',font:{size:10}}},
                y:{grid:{color:'rgba(255,255,255,.03)'},ticks:{color:'#44475a',font:{size:10},callback:v=>fmt(v)},position:'right'}
            }
        }
    });
}

// ═══════════════════════════════════════════════════════════════════════
//  4. MINI-TICKER WS — all-coins watchlist (every 1s)
// ═══════════════════════════════════════════════════════════════════════
function connectMiniTicker() {
    wsE = makeWs(FSTREAM+'/!miniTicker@arr', (ev) => {
        const arr = JSON.parse(ev.data);
        arr.forEach(t => {
            if (!t.s || !t.s.endsWith('USDT')) return;
            const price  = parseFloat(t.c);
            const change = parseFloat(t.P) || ((parseFloat(t.c)-parseFloat(t.o))/parseFloat(t.o)*100);
            const pe = el('cwp-'+t.s);
            const ce = el('cwc-'+t.s);
            if (pe) pe.textContent = fmt(price);
            if (ce) { ce.textContent=fmtChg(change); ce.style.color=change>=0?'var(--green)':'var(--red)'; }
            const idx=allPairs.findIndex(p=>p.symbol===t.s);
            if(idx>=0){ allPairs[idx].price=price; allPairs[idx].change=change; }
        });
    });
}

// ═══════════════════════════════════════════════════════════════════════
//  5. MARK PRICE WS — guaranteed EXACTLY every 1 second
//     Stream: <sym>@markPrice@1s
//     Fields: e=markPriceUpdate, s, p=markPrice, i=indexPrice,
//             r=fundingRate, T=nextFundingTime
// ═══════════════════════════════════════════════════════════════════════
function connectMarkPrice() {
    if (wsA) wsA.close();
    wsA = makeWs(`${FSTREAM}/${sym.toLowerCase()}@markPrice@1s`, (ev) => {
        const d = JSON.parse(ev.data);
        // d.p = mark price string, guaranteed every 1000ms
        const price = parseFloat(d.p);
        if (isFinite(price) && price > 0) setPrice(price);

        // Funding rate (Futures-specific — shown in place of Spread)
        const fr = parseFloat(d.r);
        if (isFinite(fr)) {
            const pct = (fr*100).toFixed(4)+'%';
            setText('mFunding', pct);
            el('mFunding').style.color = fr>=0?'var(--green)':'var(--red)';
            // Next funding countdown
            if (d.T) {
                const ms = d.T - Date.now();
                if (ms>0) {
                    const h=Math.floor(ms/3600000), m=Math.floor((ms%3600000)/60000), s=Math.floor((ms%60000)/1000);
                    setText('mFundingSub', `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`);
                }
            }
        }
        setText('lbTime', new Date().toLocaleTimeString('en-US',{hour12:false}));
    });
}

// ═══════════════════════════════════════════════════════════════════════
//  6. KLINE WS — live OHLCV for current candle (fires per trade on Futures)
// ═══════════════════════════════════════════════════════════════════════
function connectKline() {
    if (wsB) wsB.close();
    wsB = makeWs(`${FSTREAM}/${sym.toLowerCase()}@kline_${tf}`, (ev) => {
        const k = JSON.parse(ev.data).k;
        if (!k) return;
        setText('lbO', fmt(k.o));
        setText('lbH', fmt(k.h));
        setText('lbL', fmt(k.l));
        setText('lbC', fmt(k.c));
        setText('lbV', fmtVol(k.q));
        setText('lbT', parseInt(k.n||0).toLocaleString());

        if (chart) {
            const ds=chart.data.datasets, len=ds[0].data.length;
            if (len>0) {
                ds[0].data[len-1]=parseFloat(k.c);
                ds[1].data[len-1]=parseFloat(k.h);
                ds[2].data[len-1]=parseFloat(k.l);
                chart.update('none');
            }
        }
        // When candle closes, fetch fresh history (new candle opened)
        if (k.x) setTimeout(fetchCandles, 500);
    });
}

// ═══════════════════════════════════════════════════════════════════════
//  7. DEPTH WS — order book 10×/second
//     Stream: <sym>@depth20@100ms
//     Returns: { lastUpdateId, T, E, bids:[[p,q]…], asks:[[p,q]…] }
// ═══════════════════════════════════════════════════════════════════════
function connectDepth() {
    if (wsC) wsC.close();
    wsC = makeWs(`${FSTREAM}/${sym.toLowerCase()}@depth20@100ms`,
        (ev) => {
            const d    = JSON.parse(ev.data);
            const bids = d.bids||d.b||[];
            const asks = d.asks||d.a||[];
            if (!bids.length || !asks.length) return;

            renderBook(bids, asks);

            const bid=parseFloat(bids[0][0]), ask=parseFloat(asks[0][0]);
            if (bid>0 && ask>0) {
                setText('obMid',    fmt((bid+ask)/2));
                // Update spread tile (reuse mFundingSub only for depth spread here when needed)
            }
        },
        () => setText('obLabel', sym)
    );
}

// ═══════════════════════════════════════════════════════════════════════
//  (bonus) TICKER WS — live 24h stats every second
//     Stream: <sym>@ticker
// ═══════════════════════════════════════════════════════════════════════
function connectTicker() {
    if (wsD) wsD.close();
    wsD = makeWs(`${FSTREAM}/${sym.toLowerCase()}@ticker`, (ev) => {
        const d=JSON.parse(ev.data);
        const change=parseFloat(d.P), abs=parseFloat(d.p);
        const high=parseFloat(d.h), low=parseFloat(d.l), vol=parseFloat(d.q);
        if(isFinite(change)) setChange(change, abs);
        if(isFinite(high)&&high>0) setText('mHigh', fmt(high));
        if(isFinite(low) &&low >0) setText('mLow',  fmt(low));
        if(isFinite(vol) &&vol >0) setText('mVol',  fmtVol(vol));
    });
}

// ═══════════════════════════════════════════════════════════════════════
//  ORDER BOOK RENDERER
// ═══════════════════════════════════════════════════════════════════════
function renderBook(bids, asks) {
    const row = (p,q,col) => {
        const price=parseFloat(p), qty=parseFloat(q);
        if(!isFinite(price)||!isFinite(qty)) return '';
        return `<div class="ob-row">
            <span style="color:${col}">${fmt(price)}</span>
            <span class="text-muted">${qty.toFixed(4)}</span>
            <span class="text-dim">${(price*qty).toLocaleString('en-US',{maximumFractionDigits:0})}</span>
        </div>`;
    };
    el('obAsks').innerHTML = [...asks.slice(0,8)].reverse().map(([p,q])=>row(p,q,'var(--red)')).join('');
    el('obBids').innerHTML = bids.slice(0,8).map(([p,q])=>row(p,q,'var(--green)')).join('');
}

// ═══════════════════════════════════════════════════════════════════════
//  PRICE DISPLAY
// ═══════════════════════════════════════════════════════════════════════
function setPrice(price) {
    const e=el('mPrice'), str=fmt(price);
    if (prevPrice!==null && price!==prevPrice) {
        e.classList.remove('flash-up','flash-down');
        void e.offsetWidth;
        e.classList.add(price>prevPrice?'flash-up':'flash-down');
    }
    e.textContent=str;
    prevPrice=price;
    const pe=el('cwp-'+sym); if(pe) pe.textContent=str;
}

function setChange(pct,abs) {
    const e=el('mChange');
    e.textContent=fmtChg(pct);
    e.className='metric-val '+(pct>=0?'up':'down');
    setText('mChangeAbs', (isFinite(abs)?(abs>=0?'+':'-')+'$'+fmt(Math.abs(abs)):'—'));
}

// ═══════════════════════════════════════════════════════════════════════
//  WATCHLIST
// ═══════════════════════════════════════════════════════════════════════
function renderWl(pairs) {
    el('coinList').innerHTML = pairs.map(p => {
        const chgCol=(parseFloat(p.change)||0)>=0?'var(--green)':'var(--red)';
        return `<div class="coin-row ${p.symbol===sym?'active':''}" id="cr-${p.symbol}" onclick="selectCoin('${p.symbol}')">
            <div><div class="coin-sym">${p.baseAsset}</div><div class="coin-sub">${p.symbol}</div></div>
            <div>
                <div class="coin-price" id="cwp-${p.symbol}">${p.price?fmt(p.price):'—'}</div>
                <div class="coin-chg" id="cwc-${p.symbol}" style="color:${chgCol}">${p.change!==null?fmtChg(p.change):'—'}</div>
            </div>
        </div>`;
    }).join('');
}

function filterWl(q) {
    const uc=q.toUpperCase();
    const list=uc?allPairs.filter(p=>p.symbol.includes(uc)||p.baseAsset.includes(uc)):allPairs.slice(0,80);
    renderWl(list.slice(0,80));
}

// ═══════════════════════════════════════════════════════════════════════
//  DROPDOWN
// ═══════════════════════════════════════════════════════════════════════
function buildDd(pairs) {
    el('coinDropdown').innerHTML=pairs.map(p=>`
        <div class="coin-dd-item ${p.symbol===sym?'sel':''}" onmousedown="selectCoin('${p.symbol}')">
            <div class="coin-dd-sym">${p.baseAsset}<span style="color:var(--text-dim);font-weight:400">/USDT</span></div>
            <div class="coin-dd-price" style="color:${(p.change||0)>=0?'var(--green)':'var(--red)'}">
                ${p.price?fmt(p.price):'—'} <span style="font-size:10px">${p.change!==null?fmtChg(p.change):''}</span>
            </div>
        </div>`).join('');
}

function filterDd(q) {
    const uc=q.trim().toUpperCase();
    const m=uc?allPairs.filter(p=>p.symbol.includes(uc)||p.baseAsset.includes(uc)).slice(0,30):allPairs.slice(0,30);
    buildDd(m);
    el('coinDropdown').style.display='block';
}

function onFocus() { el('coinSearchInput').value=''; filterDd(''); }
function onBlur() {
    setTimeout(()=>{
        el('coinDropdown').style.display='none';
        const p=allPairs.find(x=>x.symbol===sym);
        el('coinSearchInput').value=p?p.baseAsset+'/USDT':sym;
    },200);
}

// ═══════════════════════════════════════════════════════════════════════
//  COIN SELECTION
// ═══════════════════════════════════════════════════════════════════════
function selectCoin(symbol) {
    sym=symbol; prevPrice=null;

    // Reset UI immediately
    ['mPrice','mHigh','mLow','mVol','mFunding'].forEach(id=>setText(id,'—'));
    el('mChange').textContent='—'; el('mChange').className='metric-val';
    el('obAsks').innerHTML=''; el('obBids').innerHTML=''; setText('obMid','—');
    setText('mSymbol',symbol); setText('obLabel',symbol);

    document.querySelectorAll('.coin-row').forEach(r=>r.classList.remove('active'));
    const row=el('cr-'+symbol);
    if(row){ row.classList.add('active'); row.scrollIntoView({block:'nearest'}); }

    const p=allPairs.find(x=>x.symbol===symbol);
    el('coinSearchInput').value=p?p.baseAsset+'/USDT':symbol;
    el('coinDropdown').style.display='none';
    setText('pageLabel', p?p.baseAsset+'/USDT':symbol);

    // Instant seed from cache
    if(p&&p.price){ setPrice(p.price); }
    if(p&&p.change!==null){ setChange(p.change, null); }

    // Reconnect all streams for new symbol
    connectMarkPrice();   // 5. guaranteed 1s price
    connectKline();       // 6. OHLCV + chart
    connectDepth();       // 7. order book
    connectTicker();      // bonus: live 24h stats
    fetchCandles();       // chart REST
    seedMetrics();        // 24h metrics REST seed
}

// ═══════════════════════════════════════════════════════════════════════
//  TIMEFRAME
// ═══════════════════════════════════════════════════════════════════════
function setTf(newTf) {
    tf=newTf;
    document.querySelectorAll('.tf-btn').forEach(b=>b.classList.toggle('active',b.dataset.tf===newTf));
    connectKline();
    fetchCandles();
}

// ═══════════════════════════════════════════════════════════════════════
//  INDICATORS
// ═══════════════════════════════════════════════════════════════════════
async function loadIndicators() {
    const icon=el('indIcon'); icon.className='spin';
    try {
        const r=await fetch(`/api/indicators/${sym}?interval=${tf}&force=1`);
        const d=await r.json();
        if(r.ok) renderIndicators(d); else showIndError(d.error||'Failed');
    } catch(e) { showIndError('Network error'); }
    finally { icon.className=''; icon.textContent='↻'; }
}

function renderIndicators(d) {
    const sc=s=>({bullish:'sig-bullish',bullish_cross:'sig-bullish',oversold:'sig-oversold',
        bearish:'sig-bearish',bearish_cross:'sig-bearish',overbought:'sig-overbought'}[s]||'sig-neutral');
    el('indGrid').innerHTML=[
        {label:'RSI 14',  val:parseFloat(d.rsi).toFixed(2),            sig:d.rsi_signal},
        {label:'MACD',    val:parseFloat(d.macd).toFixed(6),            sig:d.macd_cross},
        {label:'EMA 9',   val:'$'+fmt(d.ema9),                          sig:d.ema_trend},
        {label:'EMA 21',  val:'$'+fmt(d.ema21),                         sig:d.ema_trend},
        {label:'EMA 50',  val:'$'+fmt(d.ema50),                         sig:d.ema_trend},
        {label:'BB Width',val:parseFloat(d.bb_bandwidth).toFixed(3)+'%',sig:d.bb_position},
    ].map(t=>`<div class="ind-tile"><div class="ind-label">${t.label}</div><div class="ind-val">${t.val}</div><div class="ind-sig ${sc(t.sig)}">${(t.sig||'neutral').replace(/_/g,' ')}</div></div>`).join('');

    el('trendBadge').style.display='block';
    const tl=el('trendLabel');
    tl.className='badge badge-'+(d.overall_trend==='bullish'?'long':d.overall_trend==='bearish'?'short':'active');
    tl.textContent=d.overall_trend;
    setText('trendStrength',parseFloat(d.trend_strength).toFixed(1)+'% strength');
}

function showIndError(msg) {
    el('indGrid').innerHTML=`<div style="grid-column:1/-1;color:var(--red);font-size:12px;padding:8px 0">${msg}</div>`;
}

// ═══════════════════════════════════════════════════════════════════════
//  SIGNAL GENERATOR — Futures signals
// ═══════════════════════════════════════════════════════════════════════
async function generateSignal() {
    const btn=el('signalBtn');
    btn.disabled=true; btn.innerHTML='<span class="spin">⏳</span> Analyzing…';
    try {
        const r=await fetch('/api/signals/generate',{
            method:'POST',
            headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},
            body:JSON.stringify({symbol:sym,interval:tf})
        });
        const data=await r.json();
        data.success?renderSignal(data.signal):renderSignalEmpty(data.message||'No clear signal found for current conditions.');
    } catch(e) { renderSignalEmpty('Network error — check the server is running.'); }
    finally { btn.disabled=false; btn.innerHTML='⚡ Generate signal'; }
}

function renderSignal(s) {
    const cc=s.confidence>=70?'var(--green)':s.confidence>=50?'var(--amber)':'var(--red)';
    el('signalBody').innerHTML=`
        <div class="signal-card-wrap">
            <div class="sc-header ${s.trade_type}">
                <div>
                    <div class="sc-dir ${s.trade_type}">${s.trade_type.toUpperCase()}</div>
                    <div style="font-size:11px;color:var(--text-muted);margin-top:2px">${s.coin_name||s.symbol} · ${s.symbol} · ${s.interval} · Futures</div>
                </div>
                <div style="text-align:right">
                    <span class="badge badge-${s.signal_strength}">${s.signal_strength}</span>
                    <div style="font-size:11px;color:var(--text-muted);margin-top:4px">${s.confidence}% confidence</div>
                </div>
            </div>
            <div class="sc-body">
                <div class="sc-row"><span class="sc-key">Entry (mark price)</span><span class="sc-val">$${fmt(s.entry_price)}</span></div>
                <div class="sc-row"><span class="sc-key">Mode</span><span class="sc-val" style="text-transform:capitalize">${s.mode}</span></div>
                <div class="sc-row"><span class="sc-key">Leverage</span><span class="sc-val" style="color:var(--amber)">${s.leverage}x</span></div>
                <div class="sc-row"><span class="sc-key">Take profit 1</span><span class="sc-val text-up">$${fmt(s.take_profit_1)} <span style="font-size:10px;color:var(--text-muted)">(+${s.tp1_percent}%)</span></span></div>
                <div class="sc-row"><span class="sc-key">Take profit 2</span><span class="sc-val text-up">$${fmt(s.take_profit_2)}</span></div>
                <div class="sc-row"><span class="sc-key">Take profit 3</span><span class="sc-val text-up">$${fmt(s.take_profit_3)}</span></div>
                <div class="sc-row"><span class="sc-key">Stop loss</span><span class="sc-val text-dn">$${fmt(s.stop_loss)} <span style="font-size:10px;color:var(--text-muted)">(-${s.sl_percent}%)</span></span></div>
                <div class="sc-row"><span class="sc-key">Risk / Reward</span><span class="sc-val">${s.risk_reward}:1</span></div>
                <div style="height:3px;background:rgba(255,255,255,.06);border-radius:2px;margin-top:10px;overflow:hidden">
                    <div style="height:100%;width:${s.confidence}%;background:${cc};border-radius:2px;transition:width .6s"></div>
                </div>
            </div>
        </div>`;
}

function renderSignalEmpty(msg) {
    el('signalBody').innerHTML=`
        <div class="signal-empty">
            <div style="font-size:28px;margin-bottom:10px;opacity:.4">⚠️</div>
            <div style="font-weight:500;margin-bottom:4px">No signal</div>
            <div class="text-muted">${msg}</div>
        </div>`;
}

// ═══════════════════════════════════════════════════════════════════════
//  HARD REFRESH
// ═══════════════════════════════════════════════════════════════════════
function hardRefresh() {
    const icon=el('refreshIcon'); icon.classList.add('spin');
    Promise.all([seedMetrics(),fetchCandles()]).finally(()=>icon.classList.remove('spin'));
}

// ═══════════════════════════════════════════════════════════════════════
//  BOOT — ordered as requested
// ═══════════════════════════════════════════════════════════════════════
(async function boot() {
    await loadAllPairs();    // 1. all Futures PERPETUAL USDT pairs + 24h prices
    seedMetrics();           // 2. seed main metric tiles from REST
    fetchCandles();          // 3. draw chart
    connectMiniTicker();     // 4. live watchlist all-coins 1s
    connectMarkPrice();      // 5. markPrice@1s — guaranteed 1-second price tick
    connectKline();          // 6. live OHLCV + chart candle update
    connectDepth();          // 7. live order book 10×/second
    connectTicker();         // bonus: live 24h high/low/vol/change 1s

    setInterval(fetchCandles, 60000); // chart REST refresh every 60s
})();
</script>
@endpush