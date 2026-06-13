@extends('layouts.app')
@section('title', str_replace('USDT','', $signal->symbol) . ' ' . strtoupper($signal->trade_type) . ' Signal — CryptoSignal Pro')

@push('styles')
<style>
/* ── Two-column detail layout ────────────────────────────────────────── */
.detail-grid {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 12px;
    align-items: start;
}
.detail-main { display: flex; flex-direction: column; gap: 12px; }
.detail-side { display: flex; flex-direction: column; gap: 12px; }

/* ── Hero header card ───────────────────────────────────────────────── */
.signal-hero {
    border-radius: var(--radius-lg);
    border: 1px solid var(--border);
    overflow: hidden;
}
.hero-header {
    padding: 18px 20px 14px;
    display: flex; align-items: flex-start; justify-content: space-between;
    gap: 16px;
}
.hero-header.long  { background: linear-gradient(135deg, rgba(31,214,131,.08) 0%, transparent 60%); border-bottom: 1px solid rgba(31,214,131,.12); }
.hero-header.short { background: linear-gradient(135deg, rgba(240,69,90,.08)  0%, transparent 60%); border-bottom: 1px solid rgba(240,69,90,.12); }
.hero-direction {
    font-size: 28px; font-weight: 700; letter-spacing: -0.04em; line-height: 1;
}
.hero-direction.long  { color: var(--green); }
.hero-direction.short { color: var(--red); }
.hero-meta { font-size: 12px; color: var(--text-muted); margin-top: 4px; }
.hero-badges { display: flex; gap: 6px; flex-wrap: wrap; }

/* ── Entry / targets row ─────────────────────────────────────────────── */
.targets-row {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 0;
    background: var(--bg-card);
}
.target-cell {
    padding: 14px 16px;
    border-right: 1px solid var(--border);
    text-align: center;
}
.target-cell:last-child { border-right: none; }
.target-cell-label {
    font-size: 9px; font-weight: 600; text-transform: uppercase;
    letter-spacing: .08em; color: var(--text-dim); margin-bottom: 6px;
}
.target-cell-val {
    font-family: var(--font-data); font-size: 1.05rem; font-weight: 500;
}
.target-cell-sub {
    font-size: 10px; color: var(--text-muted); margin-top: 3px;
    font-family: var(--font-data);
}
.cell-entry { color: var(--text); }
.cell-tp    { color: var(--green); }
.cell-sl    { color: var(--red); }

/* ── Price track bar ─────────────────────────────────────────────────── */
.price-track-wrap {
    padding: 14px 20px;
    background: var(--bg-card);
    border-top: 1px solid var(--border);
}
.price-track-label {
    font-size: 10px; color: var(--text-muted);
    text-transform: uppercase; letter-spacing:.06em;
    margin-bottom: 10px; display: flex; justify-content: space-between;
}
.track-bar {
    position: relative; height: 6px;
    background: rgba(255,255,255,0.06);
    border-radius: 3px;
}
.track-sl-zone, .track-tp-zone {
    position: absolute; top: 0; height: 100%; border-radius: 3px; opacity: .25;
}
.track-sl-zone { background: var(--red); }
.track-tp-zone { background: var(--green); }
.track-entry-marker, .track-price-marker {
    position: absolute; top: 50%; transform: translate(-50%,-50%);
    width: 10px; height: 10px; border-radius: 50%;
}
.track-entry-marker { background: var(--text-muted); border: 2px solid var(--bg-card); }
.track-price-marker { background: var(--purple); border: 2px solid var(--bg-card); z-index:2; }
.track-labels {
    display: flex; justify-content: space-between;
    font-size: 9px; color: var(--text-dim); margin-top: 5px;
    font-family: var(--font-data);
}

/* ── Indicator snapshot ──────────────────────────────────────────────── */
.ind-snap-grid {
    display: grid; grid-template-columns: 1fr 1fr; gap: 8px;
}
.ind-snap-row {
    display: flex; justify-content: space-between; align-items: center;
    padding: 8px 0; border-bottom: 1px solid rgba(255,255,255,.04);
    font-size: 12px;
}
.ind-snap-row:last-child { border-bottom: none; }
.ind-snap-key { color: var(--text-muted); }
.ind-snap-val { font-family: var(--font-data); font-weight: 500; }

/* ── RR gauge ────────────────────────────────────────────────────────── */
.rr-gauge {
    display: flex; align-items: center; gap: 10px;
    padding: 12px 14px;
    background: rgba(155,109,255,.06);
    border: 1px solid rgba(155,109,255,.18);
    border-radius: var(--radius-md);
}
.rr-value { font-size: 2rem; font-weight: 700; font-family: var(--font-data); color: var(--purple); line-height:1; }
.rr-label { font-size: 11px; color: var(--text-muted); }

/* ── Close signal form ──────────────────────────────────────────────── */
.close-wrap {
    padding: 14px 16px;
    background: rgba(240,69,90,.04);
    border: 1px solid rgba(240,69,90,.14);
    border-radius: var(--radius-md);
}
.close-wrap .btn-danger {
    background: var(--red-dim); border-color: rgba(240,69,90,.3);
    color: var(--red); width: 100%;
    justify-content: center; font-size:12px;
}
.close-wrap .btn-danger:hover { background: rgba(240,69,90,.18); }

/* ── Outcome card ───────────────────────────────────────────────────── */
.outcome-card {
    border-radius: var(--radius-md);
    padding: 16px;
    text-align: center;
}
.outcome-card.win  { background: var(--green-dim); border: 1px solid rgba(31,214,131,.2); }
.outcome-card.loss { background: var(--red-dim);   border: 1px solid rgba(240,69,90,.2); }
.outcome-card.neutral { background: rgba(255,255,255,.04); border: 1px solid var(--border); }
.outcome-pnl { font-size: 2rem; font-weight: 700; font-family: var(--font-data); line-height:1; }
</style>
@endpush

@section('content')

@php
    $isLong   = $signal->trade_type === 'long';
    $isActive = $signal->status === 'active';
    $isTp     = in_array($signal->status, ['tp1_hit','tp2_hit','tp3_hit']);
    $isSl     = $signal->status === 'sl_hit';

    $entry = (float) $signal->entry_price;
    $sl    = (float) $signal->stop_loss;
    $tp1   = (float) $signal->take_profit_1;
    $tp2   = (float) $signal->take_profit_2;
    $tp3   = (float) $signal->take_profit_3;
    $rr    = $signal->risk_reward;

    // Track bar geometry: span from SL to TP3
    $minP  = min($sl, $tp3);
    $maxP  = max($sl, $tp3);
    $range = $maxP - $minP ?: 1;
    $pct   = fn($v) => round(($v - $minP) / $range * 100, 2);

    // Colour for confidence
    $confColor = $signal->confidence >= 70 ? 'var(--green)' : ($signal->confidence >= 50 ? 'var(--amber)' : 'var(--red)');
@endphp

{{-- ── Breadcrumb ──────────────────────────────────────────────────── --}}
<div class="flex-center gap-2" style="margin-bottom:14px;font-size:12px;color:var(--text-muted)">
    <a href="{{ route('dashboard') }}" style="color:var(--text-muted)">Dashboard</a>
    <span>›</span>
    <a href="{{ route('signals.index') }}" style="color:var(--text-muted)">Signals</a>
    <span>›</span>
    <span style="color:var(--text)">
        {{ str_replace('USDT','/USDT', $signal->symbol) }} #{{ $signal->id }}
    </span>
</div>

<div class="detail-grid">

    {{-- ══ LEFT — main detail ════════════════════════════════════════ --}}
    <div class="detail-main">

        {{-- ── Hero card ──────────────────────────────────────────── --}}
        <div class="signal-hero">
            <div class="hero-header {{ $signal->trade_type }}">
                <div>
                    <div class="hero-direction {{ $signal->trade_type }}">
                        {{ strtoupper($signal->trade_type) }}
                    </div>
                    <div class="hero-meta">
                        {{ $signal->coin_name }} · {{ $signal->symbol }} · {{ $signal->interval }} ·
                        {{ $signal->mode }} mode · {{ $signal->leverage }}x leverage
                    </div>
                </div>
                <div class="hero-badges">
                    <span class="badge badge-{{ $signal->signal_strength }}">{{ $signal->signal_strength }}</span>
                    <span class="badge badge-{{ $signal->status }}">{{ str_replace('_',' ', $signal->status) }}</span>
                    <span class="badge badge-active" style="font-family:var(--font-data)">{{ number_format($signal->confidence, 1) }}% conf.</span>
                </div>
            </div>

            {{-- Entry / TP / SL cells --}}
            <div class="targets-row">
                <div class="target-cell">
                    <div class="target-cell-label">Entry</div>
                    <div class="target-cell-val cell-entry">${{ number_format($entry, 4) }}</div>
                    <div class="target-cell-sub">{{ $signal->mode }}</div>
                </div>
                <div class="target-cell">
                    <div class="target-cell-label">TP 1</div>
                    <div class="target-cell-val cell-tp">${{ number_format($tp1, 4) }}</div>
                    <div class="target-cell-sub">+{{ $signal->tp1_percent }}%</div>
                </div>
                <div class="target-cell">
                    <div class="target-cell-label">TP 2</div>
                    <div class="target-cell-val cell-tp">${{ number_format($tp2, 4) }}</div>
                </div>
                <div class="target-cell">
                    <div class="target-cell-label">TP 3</div>
                    <div class="target-cell-val cell-tp">${{ number_format($tp3, 4) }}</div>
                </div>
                <div class="target-cell">
                    <div class="target-cell-label">Stop loss</div>
                    <div class="target-cell-val cell-sl">${{ number_format($sl, 4) }}</div>
                    <div class="target-cell-sub">-{{ $signal->sl_percent }}%</div>
                </div>
            </div>

            {{-- Price track bar --}}
            <div class="price-track-wrap">
                <div class="price-track-label">
                    <span>Price range: SL → TP3</span>
                    <span>{{ $isLong ? 'Long position' : 'Short position' }}</span>
                </div>
                <div class="track-bar">
                    @if($isLong)
                        {{-- SL zone: left portion --}}
                        <div class="track-sl-zone" style="left:0;width:{{ $pct($entry) }}%"></div>
                        {{-- TP zone: right portion from entry → TP3 --}}
                        <div class="track-tp-zone" style="left:{{ $pct($entry) }}%;right:0"></div>
                    @else
                        <div class="track-sl-zone" style="right:0;width:{{ 100 - $pct($entry) }}%"></div>
                        <div class="track-tp-zone" style="left:0;width:{{ $pct($entry) }}%"></div>
                    @endif
                    <div class="track-entry-marker" style="left:{{ $pct($entry) }}%"
                         title="Entry: ${{ number_format($entry,4) }}"></div>
                    {{-- TP1/2/3 ticks --}}
                    @foreach([$tp1,$tp2,$tp3] as $tp)
                    <div style="position:absolute;top:-3px;left:{{ $pct($tp) }}%;width:1px;height:12px;background:var(--green);opacity:.5"></div>
                    @endforeach
                    {{-- SL tick --}}
                    <div style="position:absolute;top:-3px;left:{{ $pct($sl) }}%;width:1px;height:12px;background:var(--red);opacity:.5"></div>
                </div>
                <div class="track-labels">
                    <span>${{ number_format($minP, 4) }}</span>
                    <span>SL · Entry · TP1 · TP2 · TP3</span>
                    <span>${{ number_format($maxP, 4) }}</span>
                </div>
            </div>
        </div>

        {{-- ── Indicator snapshot ──────────────────────────────────── --}}
        <div class="card">
            <div class="card-header">
                <span class="card-title">Indicator snapshot at signal time</span>
            </div>
            <div class="card-body">
                <div class="ind-snap-grid">
                    {{-- Left column --}}
                    <div>
                        <div class="ind-snap-row">
                            <span class="ind-snap-key">RSI (14)</span>
                            <span class="ind-snap-val
                                @if($signal->rsi <= 30) text-up
                                @elseif($signal->rsi >= 70) text-dn
                                @endif">
                                {{ $signal->rsi ? number_format($signal->rsi, 2) : '—' }}
                            </span>
                        </div>
                        <div class="ind-snap-row">
                            <span class="ind-snap-key">MACD</span>
                            <span class="ind-snap-val {{ $signal->macd >= 0 ? 'text-up' : 'text-dn' }}">
                                {{ $signal->macd ? number_format($signal->macd, 6) : '—' }}
                            </span>
                        </div>
                        <div class="ind-snap-row">
                            <span class="ind-snap-key">MACD signal</span>
                            <span class="ind-snap-val">{{ $signal->macd_signal ? number_format($signal->macd_signal, 6) : '—' }}</span>
                        </div>
                        <div class="ind-snap-row">
                            <span class="ind-snap-key">EMA 9</span>
                            <span class="ind-snap-val">${{ $signal->ema9 ? number_format($signal->ema9, 4) : '—' }}</span>
                        </div>
                    </div>
                    {{-- Right column --}}
                    <div>
                        <div class="ind-snap-row">
                            <span class="ind-snap-key">EMA 21</span>
                            <span class="ind-snap-val">${{ $signal->ema21 ? number_format($signal->ema21, 4) : '—' }}</span>
                        </div>
                        <div class="ind-snap-row">
                            <span class="ind-snap-key">EMA 50</span>
                            <span class="ind-snap-val">${{ $signal->ema50 ? number_format($signal->ema50, 4) : '—' }}</span>
                        </div>
                        <div class="ind-snap-row">
                            <span class="ind-snap-key">BB Upper</span>
                            <span class="ind-snap-val">${{ $signal->bb_upper ? number_format($signal->bb_upper, 4) : '—' }}</span>
                        </div>
                        <div class="ind-snap-row">
                            <span class="ind-snap-key">BB Middle / Lower</span>
                            <span class="ind-snap-val">
                                ${{ $signal->bb_middle ? number_format($signal->bb_middle, 4) : '—' }} ·
                                ${{ $signal->bb_lower  ? number_format($signal->bb_lower,  4) : '—' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- ══ RIGHT — sidebar ════════════════════════════════════════════ --}}
    <div class="detail-side">

        {{-- ── Outcome (if closed) ─────────────────────────────────── --}}
        @if(!$isActive)
        <div class="outcome-card {{ $isTp ? 'win' : ($isSl ? 'loss' : 'neutral') }}">
            <div style="font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.08em;color:var(--text-muted);margin-bottom:6px">
                Outcome
            </div>
            <div class="outcome-pnl {{ $isTp ? 'text-up' : ($isSl ? 'text-dn' : '') }}">
                @if($signal->pnl_percent !== null)
                    {{ $signal->pnl_percent >= 0 ? '+' : '' }}{{ number_format($signal->pnl_percent, 2) }}%
                @else
                    —
                @endif
            </div>
            <div style="font-size:11px;color:var(--text-muted);margin-top:4px">
                {{ strtoupper(str_replace('_',' ',$signal->status)) }}
                @if($signal->close_price)
                    · closed @ ${{ number_format($signal->close_price, 4) }}
                @endif
            </div>
            @if($signal->closed_at)
            <div style="font-size:10px;color:var(--text-dim);margin-top:4px">
                {{ $signal->closed_at->format('M d, Y H:i') }}
            </div>
            @endif
        </div>
        @endif

        {{-- ── Risk / Reward ────────────────────────────────────────── --}}
        <div class="card">
            <div class="card-header"><span class="card-title">Risk / Reward</span></div>
            <div class="card-body" style="display:flex;flex-direction:column;gap:10px">
                <div class="rr-gauge">
                    <div class="rr-value">{{ $rr }}</div>
                    <div>
                        <div class="rr-label">Risk : Reward</div>
                        <div style="font-size:10px;color:var(--text-dim)">TP1 vs Stop loss</div>
                    </div>
                </div>
                <div class="ind-snap-row">
                    <span class="ind-snap-key">Risk (SL distance)</span>
                    <span class="ind-snap-val text-dn">-{{ $signal->sl_percent }}% · ${{ number_format(abs($entry - $sl), 4) }}</span>
                </div>
                <div class="ind-snap-row">
                    <span class="ind-snap-key">Reward TP1</span>
                    <span class="ind-snap-val text-up">+{{ $signal->tp1_percent }}% · ${{ number_format(abs($tp1 - $entry), 4) }}</span>
                </div>
                <div class="ind-snap-row">
                    <span class="ind-snap-key">Leverage</span>
                    <span class="ind-snap-val" style="color:var(--amber)">{{ $signal->leverage }}x {{ $signal->mode }}</span>
                </div>
                <div class="ind-snap-row" style="border:none">
                    <span class="ind-snap-key">Effective max gain</span>
                    <span class="ind-snap-val text-up">
                        +{{ number_format($signal->tp1_percent * $signal->leverage, 1) }}%
                    </span>
                </div>

                {{-- Confidence bar --}}
                <div style="margin-top:4px">
                    <div style="display:flex;justify-content:space-between;font-size:10px;color:var(--text-muted);margin-bottom:5px">
                        <span>Signal confidence</span>
                        <span style="color:{{ $confColor }}">{{ number_format($signal->confidence, 1) }}%</span>
                    </div>
                    <div style="height:5px;background:rgba(255,255,255,.06);border-radius:3px;overflow:hidden">
                        <div style="height:100%;width:{{ $signal->confidence }}%;background:{{ $confColor }};border-radius:3px;transition:width .6s"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Signal metadata ──────────────────────────────────────── --}}
        <div class="card">
            <div class="card-header"><span class="card-title">Metadata</span></div>
            <div class="card-body" style="display:flex;flex-direction:column;gap:0">
                @foreach([
                    ['ID',         '#'.$signal->id],
                    ['Symbol',     $signal->symbol],
                    ['Coin',       $signal->coin_name],
                    ['Interval',   $signal->interval],
                    ['Strength',   $signal->signal_strength],
                    ['Generated',  $signal->created_at->format('M d, Y H:i')],
                    ['Ago',        $signal->created_at->diffForHumans()],
                ] as [$k,$v])
                <div class="ind-snap-row">
                    <span class="ind-snap-key">{{ $k }}</span>
                    <span class="ind-snap-val" style="font-size:12px">{{ $v }}</span>
                </div>
                @endforeach
            </div>
        </div>

        {{-- ── Close signal (active only) ──────────────────────────── --}}
        @if($isActive)
        <div class="close-wrap">
            <div style="font-size:11px;color:var(--text-muted);margin-bottom:10px;line-height:1.5">
                Manually close this signal at the current market price. This marks it as <strong style="color:var(--text)">expired</strong> and records PnL.
            </div>
            <button class="btn btn-danger" id="closeBtn" onclick="closeSignal({{ $signal->id }})">
                ✕ Close signal at market
            </button>
            <div id="closeMsg" style="font-size:11px;margin-top:8px;display:none"></div>
        </div>
        @endif

        {{-- ── Back / nav links ─────────────────────────────────────── --}}
        <div style="display:flex;gap:8px">
            <a href="{{ route('signals.index') }}" class="btn" style="flex:1;justify-content:center">← All signals</a>
            <a href="{{ route('dashboard') }}"     class="btn" style="flex:1;justify-content:center">Dashboard</a>
        </div>

    </div>
</div>

@endsection

@push('scripts')
<script>
async function closeSignal(id) {
    const btn = document.getElementById('closeBtn');
    const msg = document.getElementById('closeMsg');
    btn.disabled = true;
    btn.textContent = 'Closing…';

    try {
        const res  = await fetch(`/api/signals/${id}/close`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            }
        });
        const data = await res.json();

        if (data.success) {
            msg.style.display = 'block';
            msg.style.color   = 'var(--green)';
            msg.textContent   = `Closed @ $${parseFloat(data.close_price).toFixed(4)} · PnL: ${data.pnl_percent >= 0 ? '+' : ''}${data.pnl_percent}%`;
            btn.textContent   = '✓ Signal closed';
            // Reload after 1.5s to reflect new status
            setTimeout(() => location.reload(), 1500);
        } else {
            msg.style.display = 'block';
            msg.style.color   = 'var(--red)';
            msg.textContent   = data.error || 'Failed to close signal.';
            btn.disabled      = false;
            btn.textContent   = '✕ Close signal at market';
        }
    } catch(e) {
        msg.style.display = 'block';
        msg.style.color   = 'var(--red)';
        msg.textContent   = 'Network error.';
        btn.disabled      = false;
        btn.textContent   = '✕ Close signal at market';
    }
}
</script>
@endpush