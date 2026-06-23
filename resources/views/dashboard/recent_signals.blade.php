@if($recentSignals->count() > 0)
<div class="card mt-3">
    <div class="card-header">
        <span class="card-title">Recent signals</span>
        <a href="{{ route('signals.index') }}" style="font-size:11px;color:var(--text-muted)">View all →</a>
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Coin</th><th>Type</th><th>Source</th><th>Mode</th>
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
                <td>
                    @if($s->source === 'auto')
                        <span class="badge" style="background:rgba(155,109,255,.2);color:var(--purple);border:1px solid rgba(155,109,255,.3)">🤖 AI AUTO</span>
                    @else
                        <span class="badge" style="background:rgba(255,255,255,.05);color:var(--text-muted);border:1px solid rgba(255,255,255,.1)">👤 MANUAL</span>
                    @endif
                </td>
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
