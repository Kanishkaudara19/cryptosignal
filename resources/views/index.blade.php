@extends('layouts.app')

@section('content')

<div class="flex-between" style="margin-bottom:1.5rem">
    <div>
        <h1 style="font-size:1.25rem;font-weight:600">Signal history</h1>
        <p class="text-muted text-sm" style="margin-top:2px">All generated trade signals</p>
    </div>
    <a href="{{ route('dashboard') }}" class="btn">← Back to dashboard</a>
</div>

@if($signals->count() > 0)
<div class="card" style="padding:0;overflow:hidden">
    <table style="width:100%;border-collapse:collapse;font-size:12px">
        <thead>
            <tr style="color:var(--text-muted);border-bottom:1px solid var(--border);background:rgba(255,255,255,0.02)">
                <th style="text-align:left;padding:10px 14px;font-weight:500">Coin</th>
                <th style="text-align:left;padding:10px 8px;font-weight:500">Type</th>
                <th style="text-align:left;padding:10px 8px;font-weight:500">Mode</th>
                <th style="text-align:right;padding:10px 8px;font-weight:500">Entry</th>
                <th style="text-align:right;padding:10px 8px;font-weight:500">TP1</th>
                <th style="text-align:right;padding:10px 8px;font-weight:500">TP2</th>
                <th style="text-align:right;padding:10px 8px;font-weight:500">TP3</th>
                <th style="text-align:right;padding:10px 8px;font-weight:500">Stop loss</th>
                <th style="text-align:right;padding:10px 8px;font-weight:500">Lev.</th>
                <th style="text-align:right;padding:10px 8px;font-weight:500">Status</th>
                <th style="text-align:right;padding:10px 14px;font-weight:500">Time</th>
            </tr>
        </thead>
        <tbody>
            @foreach($signals as $signal)
            <tr style="border-bottom:1px solid rgba(255,255,255,0.04)" onmouseover="this.style.background='rgba(255,255,255,0.02)'" onmouseout="this.style.background=''">
                <td style="padding:10px 14px;font-weight:500">
                    {{ $signal->symbol }}<br>
                    <span style="font-size:11px;color:var(--text-muted)">{{ $signal->interval }}</span>
                </td>
                <td style="padding:10px 8px">
                    <span class="badge badge-{{ $signal->trade_type }}">{{ strtoupper($signal->trade_type) }}</span>
                </td>
                <td style="padding:10px 8px;color:var(--text-muted)">{{ $signal->mode }}</td>
                <td style="padding:10px 8px;text-align:right;font-family:var(--font-mono)">${{ number_format($signal->entry_price, 2) }}</td>
                <td style="padding:10px 8px;text-align:right;font-family:var(--font-mono);color:var(--green)">${{ number_format($signal->take_profit_1, 2) }}</td>
                <td style="padding:10px 8px;text-align:right;font-family:var(--font-mono);color:var(--green)">${{ number_format($signal->take_profit_2, 2) }}</td>
                <td style="padding:10px 8px;text-align:right;font-family:var(--font-mono);color:var(--green)">${{ number_format($signal->take_profit_3, 2) }}</td>
                <td style="padding:10px 8px;text-align:right;font-family:var(--font-mono);color:var(--red)">${{ number_format($signal->stop_loss, 2) }}</td>
                <td style="padding:10px 8px;text-align:right;font-weight:500">{{ $signal->leverage }}x</td>
                <td style="padding:10px 8px;text-align:right">
                    <span class="badge badge-active">{{ $signal->status }}</span>
                </td>
                <td style="padding:10px 14px;text-align:right;color:var(--text-muted)">{{ $signal->created_at->diffForHumans() }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div style="margin-top:1rem">
    {{ $signals->links() }}
</div>

@else
<div class="card" style="text-align:center;padding:3rem">
    <div style="font-size:36px;margin-bottom:12px">📡</div>
    <div style="font-size:14px;font-weight:500;margin-bottom:6px">No signals yet</div>
    <div class="text-muted text-sm">Signals will appear here once the signal engine is running (Parts 3–5).</div>
    <a href="{{ route('dashboard') }}" class="btn btn-primary" style="margin-top:1rem;display:inline-flex">Go to dashboard</a>
</div>
@endif

@endsection