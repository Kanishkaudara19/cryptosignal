@php
    $latestAuto = \App\Models\Signal::where('source', 'auto')
        ->where('created_at', '>', now()->subHour())
        ->orderBy('created_at', 'desc')
        ->first();
@endphp

@if($latestAuto)
<div class="alert alert-purple" style="background:rgba(155,109,255,.12); border:1px solid rgba(155,109,255,.2); border-radius:var(--radius-md); padding:10px 16px; margin-bottom:16px; display:flex; align-items:center; gap:12px; animation: slideIn .4s ease-out;">
    <div style="font-size:20px">🤖</div>
    <div style="flex:1">
        <div style="font-size:12px; font-weight:600; color:var(--purple); margin-bottom:1px">Latest AI Automated Signal</div>
        <div style="font-size:11px; color:var(--text-muted)">
            <span style="color:var(--text); font-weight:500">{{ $latestAuto->symbol }}</span> 
            identifed a <span class="text-{{ $latestAuto->trade_type }} fw-600">{{ strtoupper($latestAuto->trade_type) }}</span> 
            opportunity with <span style="color:var(--text)">{{ $latestAuto->confidence }}%</span> confidence 
            <span style="font-style:italic">({{ $latestAuto->created_at->diffForHumans() }})</span>
        </div>
    </div>
    <a href="{{ route('signals.show', $latestAuto) }}" class="btn btn-primary" style="font-size:10px; padding:5px 12px">View Details</a>
</div>
@endif
