@extends('layouts.app')
@section('title', 'Signal History — CryptoSignal Pro')

@push('styles')
<style>
/* ── Stats strip ─────────────────────────────────────────────────────── */
.stats-strip {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 8px;
    margin-bottom: 16px;
}

/* ── Filter bar ──────────────────────────────────────────────────────── */
.filter-bar {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    padding: 10px 14px;
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius-md);
    margin-bottom: 12px;
}
.filter-bar .filter-label {
    font-size: 10px;
    font-weight: 600;
    color: var(--text-dim);
    text-transform: uppercase;
    letter-spacing: .08em;
    margin-right: 4px;
    white-space: nowrap;
}
.filter-bar select {
    font-size: 11px;
    padding: 5px 24px 5px 9px;
}
.filter-spacer { flex: 1; }
.active-filter-tags {
    display: flex; gap: 5px; flex-wrap: wrap;
}
.filter-tag {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 2px 8px; border-radius: 100px;
    font-size: 10px; font-weight: 600;
    background: var(--purple-dim); color: var(--purple);
    border: 1px solid rgba(155,109,255,0.2);
}
.filter-tag a { color: inherit; opacity:.6; margin-left:2px; }
.filter-tag a:hover { opacity:1; }

/* ── Table ───────────────────────────────────────────────────────────── */
.signals-table-wrap {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    overflow: hidden;
}
.signals-table-header {
    padding: 12px 16px;
    border-bottom: 1px solid var(--border);
    display: flex; align-items: center; justify-content: space-between;
}
.data-table th { padding: 10px 14px; }
.data-table td { padding: 11px 14px; }

/* PnL cell coloring */
.pnl-pos { color: var(--green); }
.pnl-neg { color: var(--red); }

/* ── Progress bar for confidence ────────────────────────────────────── */
.conf-cell { display: flex; align-items: center; gap: 7px; }
.conf-bar-bg {
    flex: 1; height: 3px;
    background: rgba(255,255,255,0.06);
    border-radius: 2px; min-width: 40px;
}
.conf-bar-fill { height: 100%; border-radius: 2px; }

/* ── Status dot ──────────────────────────────────────────────────────── */
.status-row { display: flex; align-items: center; gap: 6px; }
.s-dot {
    width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0;
}
.s-dot.active  { background: var(--blue); box-shadow: 0 0 5px var(--blue); animation: livepulse 2s ease-in-out infinite; }
.s-dot.tp      { background: var(--green); }
.s-dot.sl      { background: var(--red); }
.s-dot.expired { background: var(--text-dim); }

/* ── Empty state ─────────────────────────────────────────────────────── */
.empty-state {
    padding: 4rem 2rem;
    text-align: center;
    color: var(--text-muted);
}
.empty-icon { font-size: 3rem; margin-bottom: 1rem; opacity: .3; }

/* ── Pagination ──────────────────────────────────────────────────────── */
.pagination-wrap {
    padding: 12px 16px;
    border-top: 1px solid var(--border);
    display: flex; align-items: center; justify-content: space-between;
    font-size: 11px; color: var(--text-muted);
}
.pagination-links {
    display: flex; gap: 3px;
}
.pagination-links a,
.pagination-links span {
    display: inline-flex; align-items: center; justify-content: center;
    width: 28px; height: 28px;
    border-radius: var(--radius-sm);
    border: 1px solid var(--border);
    background: var(--bg-input);
    color: var(--text-muted);
    font-size: 11px;
    transition: all .15s;
}
.pagination-links a:hover { background: rgba(255,255,255,.08); color: var(--text); }
.pagination-links .active-page {
    background: var(--purple);
    border-color: var(--purple);
    color: #fff;
}
.pagination-links .disabled { opacity: .35; pointer-events: none; }
</style>
@endpush

@section('content')

{{-- ── Page header ─────────────────────────────────────────────────── --}}
<div class="flex-between" style="margin-bottom:14px">
    <div>
        <h1 style="font-size:15px;font-weight:600;margin-bottom:2px">Signal History</h1>
        <div class="text-muted" style="font-size:11px">All generated trade signals with live status tracking</div>
    </div>
    <a href="{{ route('dashboard') }}" class="btn">← Dashboard</a>
</div>

{{-- ── Stats strip ─────────────────────────────────────────────────── --}}
<div class="stats-strip">
    <div class="metric-tile">
        <div class="metric-label">Total signals</div>
        <div class="metric-val mono">{{ number_format($stats['total']) }}</div>
        <div class="metric-sub">All time</div>
    </div>
    <div class="metric-tile">
        <div class="metric-label">Active</div>
        <div class="metric-val mono" style="color:var(--blue)">{{ $stats['active'] }}</div>
        <div class="metric-sub">Open positions</div>
    </div>
    <div class="metric-tile">
        <div class="metric-label">TP hit</div>
        <div class="metric-val mono text-up">{{ $stats['tp_hit'] }}</div>
        <div class="metric-sub">Take profit reached</div>
    </div>
    <div class="metric-tile">
        <div class="metric-label">SL hit</div>
        <div class="metric-val mono text-dn">{{ $stats['sl_hit'] }}</div>
        <div class="metric-sub">Stop loss triggered</div>
    </div>
    <div class="metric-tile">
        <div class="metric-label">Avg confidence</div>
        <div class="metric-val mono">{{ $stats['avg_conf'] ? number_format($stats['avg_conf'], 1).'%' : '—' }}</div>
        <div class="metric-sub">Signal quality</div>
    </div>
    <div class="metric-tile">
        <div class="metric-label">Avg PnL</div>
        @php $pnl = $stats['avg_pnl']; @endphp
        <div class="metric-val mono {{ $pnl >= 0 ? 'up' : 'down' }}">
            {{ $pnl !== null ? ($pnl >= 0 ? '+' : '').number_format($pnl, 2).'%' : '—' }}
        </div>
        <div class="metric-sub">Closed signals</div>
    </div>
</div>

{{-- ── Filter bar ───────────────────────────────────────────────────── --}}
<form method="GET" action="{{ route('signals.index') }}" id="filterForm">
<div class="filter-bar">
    <span class="filter-label">Filter</span>

    <select name="symbol" onchange="document.getElementById('filterForm').submit()">
        <option value="">All coins</option>
        @foreach($symbols as $sym)
            <option value="{{ $sym }}" {{ request('symbol') === $sym ? 'selected' : '' }}>
                {{ str_replace('USDT', '/USDT', $sym) }}
            </option>
        @endforeach
    </select>

    <select name="interval" onchange="document.getElementById('filterForm').submit()">
        <option value="">All intervals</option>
        @foreach($intervals as $tf)
            <option value="{{ $tf }}" {{ request('interval') === $tf ? 'selected' : '' }}>{{ $tf }}</option>
        @endforeach
    </select>

    <select name="trade_type" onchange="document.getElementById('filterForm').submit()">
        <option value="">Long & Short</option>
        <option value="long"  {{ request('trade_type') === 'long'  ? 'selected' : '' }}>Long only</option>
        <option value="short" {{ request('trade_type') === 'short' ? 'selected' : '' }}>Short only</option>
    </select>

    <select name="status" onchange="document.getElementById('filterForm').submit()">
        <option value="">All statuses</option>
        @foreach($statuses as $st)
            <option value="{{ $st }}" {{ request('status') === $st ? 'selected' : '' }}>
                {{ ucfirst(str_replace('_', ' ', $st)) }}
            </option>
        @endforeach
    </select>

    <select name="strength" onchange="document.getElementById('filterForm').submit()">
        <option value="">Any strength</option>
        @foreach($strengths as $str)
            <option value="{{ $str }}" {{ request('strength') === $str ? 'selected' : '' }}>
                {{ ucfirst($str) }}
            </option>
        @endforeach
    </select>

    <div class="filter-spacer"></div>

    {{-- Active filter tags --}}
    @php
        $activeFilters = array_filter([
            'symbol'     => request('symbol'),
            'interval'   => request('interval'),
            'trade_type' => request('trade_type'),
            'status'     => request('status'),
            'strength'   => request('strength'),
        ]);
    @endphp
    @if(count($activeFilters))
    <div class="active-filter-tags">
        @foreach($activeFilters as $key => $val)
            <span class="filter-tag">
                {{ ucfirst($key) }}: {{ $val }}
                <a href="{{ route('signals.index', array_diff_key(request()->query(), [$key => ''])) }}" title="Remove">✕</a>
            </span>
        @endforeach
    </div>
    @if(count($activeFilters) > 1)
    <a href="{{ route('signals.index') }}" class="btn" style="font-size:11px;padding:4px 10px">Clear all</a>
    @endif
    @endif
</div>
</form>

{{-- ── Signals table ────────────────────────────────────────────────── --}}
<div class="signals-table-wrap">
    <div class="signals-table-header">
        <span class="card-title">
            Signals
            <span style="font-weight:400;color:var(--text-muted);margin-left:6px">
                {{ $signals->total() }} {{ Str::plural('result', $signals->total()) }}
            </span>
        </span>
        <span style="font-size:11px;color:var(--text-dim)">
            Page {{ $signals->currentPage() }} of {{ $signals->lastPage() }}
        </span>
    </div>

    @if($signals->isEmpty())
    <div class="empty-state">
        <div class="empty-icon">📡</div>
        <div style="font-weight:600;margin-bottom:6px">No signals found</div>
        <div style="font-size:12px">
            @if(count($activeFilters))
                No signals match the current filters. <a href="{{ route('signals.index') }}" style="color:var(--purple)">Clear filters</a>
            @else
                Go to the <a href="{{ route('dashboard') }}" style="color:var(--purple)">dashboard</a> and generate your first signal.
            @endif
        </div>
    </div>
    @else
    <div style="overflow-x:auto">
    <table class="data-table" style="min-width:960px">
        <thead>
            <tr>
                <th>#</th>
                <th>Coin</th>
                <th>Type</th>
                <th>Strength</th>
                <th style="text-align:right">Entry</th>
                <th style="text-align:right">TP1</th>
                <th style="text-align:right">TP2</th>
                <th style="text-align:right">TP3</th>
                <th style="text-align:right">Stop loss</th>
                <th style="text-align:right">Lev.</th>
                <th>Confidence</th>
                <th>Status</th>
                <th style="text-align:right">PnL</th>
                <th>Created</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        @foreach($signals as $s)
        @php
            $isLong   = $s->trade_type === 'long';
            $isActive = $s->status === 'active';
            $isTp     = in_array($s->status, ['tp1_hit','tp2_hit','tp3_hit']);
            $isSl     = $s->status === 'sl_hit';
            $dotClass = $isActive ? 'active' : ($isTp ? 'tp' : ($isSl ? 'sl' : 'expired'));
            $confColor = $s->confidence >= 70 ? 'var(--green)' : ($s->confidence >= 50 ? 'var(--amber)' : 'var(--red)');
        @endphp
        <tr>
            {{-- ID --}}
            <td class="text-dim mono" style="font-size:10px">{{ $s->id }}</td>

            {{-- Coin --}}
            <td>
                <div class="fw-600" style="font-size:12px">{{ str_replace('USDT', '', $s->symbol) }}</div>
                <div class="text-dim" style="font-size:10px">{{ $s->interval }}</div>
            </td>

            {{-- Trade type --}}
            <td><span class="badge badge-{{ $s->trade_type }}">{{ strtoupper($s->trade_type) }}</span></td>

            {{-- Strength --}}
            <td><span class="badge badge-{{ $s->signal_strength }}">{{ $s->signal_strength }}</span></td>

            {{-- Entry --}}
            <td class="mono" style="text-align:right;font-size:12px">${{ number_format($s->entry_price, 4) }}</td>

            {{-- TP1 --}}
            <td class="mono text-up" style="text-align:right;font-size:12px">
                ${{ number_format($s->take_profit_1, 4) }}
                <div class="text-dim" style="font-size:10px">+{{ $s->tp1_percent }}%</div>
            </td>

            {{-- TP2 --}}
            <td class="mono text-up" style="text-align:right;font-size:12px">${{ number_format($s->take_profit_2, 4) }}</td>

            {{-- TP3 --}}
            <td class="mono text-up" style="text-align:right;font-size:12px">${{ number_format($s->take_profit_3, 4) }}</td>

            {{-- Stop loss --}}
            <td class="mono text-dn" style="text-align:right;font-size:12px">
                ${{ number_format($s->stop_loss, 4) }}
                <div class="text-dim" style="font-size:10px">-{{ $s->sl_percent }}%</div>
            </td>

            {{-- Leverage --}}
            <td class="mono fw-600" style="text-align:right;color:var(--amber)">{{ $s->leverage }}x</td>

            {{-- Confidence --}}
            <td style="min-width:100px">
                <div class="conf-cell">
                    <span class="mono" style="font-size:11px;min-width:32px">{{ number_format($s->confidence, 0) }}%</span>
                    <div class="conf-bar-bg">
                        <div class="conf-bar-fill" style="width:{{ $s->confidence }}%;background:{{ $confColor }}"></div>
                    </div>
                </div>
            </td>

            {{-- Status --}}
            <td>
                <div class="status-row">
                    <span class="s-dot {{ $dotClass }}"></span>
                    <span class="badge badge-{{ $s->status }}" style="font-size:9px">
                        {{ str_replace('_', ' ', $s->status) }}
                    </span>
                </div>
            </td>

            {{-- PnL --}}
            <td class="mono" style="text-align:right;font-size:12px">
                @if($s->pnl_percent !== null)
                    <span class="{{ $s->pnl_percent >= 0 ? 'pnl-pos' : 'pnl-neg' }}">
                        {{ $s->pnl_percent >= 0 ? '+' : '' }}{{ number_format($s->pnl_percent, 2) }}%
                    </span>
                @else
                    <span class="text-dim">—</span>
                @endif
            </td>

            {{-- Time --}}
            <td style="white-space:nowrap">
                <div style="font-size:11px">{{ $s->created_at->format('M d, H:i') }}</div>
                <div class="text-dim" style="font-size:10px">{{ $s->created_at->diffForHumans() }}</div>
            </td>

            {{-- Detail link --}}
            <td>
                <a href="{{ route('signals.show', $s) }}" class="btn" style="font-size:10px;padding:4px 10px;white-space:nowrap">
                    View →
                </a>
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>
    </div>

    {{-- ── Pagination ────────────────────────────────────────────────── --}}
    <div class="pagination-wrap">
        <span>
            Showing {{ $signals->firstItem() }}–{{ $signals->lastItem() }}
            of {{ $signals->total() }} signals
        </span>
        <div class="pagination-links">
            {{-- Previous --}}
            @if($signals->onFirstPage())
                <span class="disabled">‹</span>
            @else
                <a href="{{ $signals->previousPageUrl() }}">‹</a>
            @endif

            {{-- Page numbers (show up to 7 around current) --}}
            @php
                $current = $signals->currentPage();
                $last    = $signals->lastPage();
                $start   = max(1, $current - 3);
                $end     = min($last, $current + 3);
            @endphp
            @if($start > 1)
                <a href="{{ $signals->url(1) }}">1</a>
                @if($start > 2)<span style="border:none;background:none;color:var(--text-dim)">…</span>@endif
            @endif
            @for($p = $start; $p <= $end; $p++)
                @if($p === $current)
                    <span class="active-page">{{ $p }}</span>
                @else
                    <a href="{{ $signals->url($p) }}">{{ $p }}</a>
                @endif
            @endfor
            @if($end < $last)
                @if($end < $last - 1)<span style="border:none;background:none;color:var(--text-dim)">…</span>@endif
                <a href="{{ $signals->url($last) }}">{{ $last }}</a>
            @endif

            {{-- Next --}}
            @if($signals->hasMorePages())
                <a href="{{ $signals->nextPageUrl() }}">›</a>
            @else
                <span class="disabled">›</span>
            @endif
        </div>
    </div>
    @endif
</div>

@endsection