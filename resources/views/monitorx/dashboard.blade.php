@extends('monitorx.layout', ['title' => 'Dashboard', 'crumb' => 'Dashboard'])

@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h2 class="text-xl font-semibold tracking-tight">System Overview</h2>
        <p class="mt-0.5 text-xs text-muted">Live application signals across your monitored services.</p>
    </div>
    <form class="flex items-center gap-2" method="GET">
        <select name="environment" class="input-field">
            <option value="">All environments</option>
            @foreach($environments as $env)
                <option @selected(request('environment') === $env)>{{ $env }}</option>
            @endforeach
        </select>
        <input type="date" name="from" value="{{ request('from') }}" class="input-field">
        <span class="text-xs text-muted">to</span>
        <input type="date" name="to" value="{{ request('to') }}" class="input-field">
        <button type="submit" class="btn-primary">Apply</button>
        @if(request()->hasAny(['environment','from','to']))
            <a href="{{ route('monitorx.dashboard') }}" class="btn-secondary">Reset</a>
        @endif
    </form>
</div>

{{-- Metric Cards --}}
<div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-5 2xl:grid-cols-5">
    @php
    $metrics = [
        ['label' => 'Requests', 'value' => number_format($stats['requests']), 'sub' => 'Total monitored requests', 'color' => 'indigo', 'icon' => '<polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>'],
        ['label' => 'Exceptions', 'value' => number_format($stats['exceptions']), 'sub' => 'Errors caught', 'color' => 'rose', 'icon' => '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>'],
        ['label' => 'DB Queries', 'value' => number_format($stats['queries']), 'sub' => $stats['slow_queries'].' slow queries', 'color' => 'cyan', 'icon' => '<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/>'],
        ['label' => 'Failed Jobs', 'value' => number_format($stats['failed_jobs']), 'sub' => 'Needs attention', 'color' => 'amber', 'icon' => '<rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>'],
        ['label' => 'Cache Hit Ratio', 'value' => $stats['cache_hit_ratio'].'%', 'sub' => 'Cache efficiency', 'color' => 'emerald', 'icon' => '<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/>'],
        ['label' => 'HTTP Failures', 'value' => number_format($stats['http_failures']), 'sub' => 'Outgoing API failures', 'color' => 'orange', 'icon' => '<circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/>'],
        ['label' => 'Mail Failures', 'value' => number_format($stats['mail_failures']), 'sub' => 'Undelivered mail', 'color' => 'pink', 'icon' => '<path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>'],
        ['label' => 'Avg Response', 'value' => $stats['avg_duration'].' ms', 'sub' => 'Request duration avg', 'color' => 'violet', 'icon' => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>'],
        ['label' => 'Slow Queries', 'value' => number_format($stats['slow_queries']), 'sub' => 'Above threshold', 'color' => 'yellow', 'icon' => '<polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>'],
        ['label' => 'Failed Batches', 'value' => number_format($stats['failed_batches']), 'sub' => 'Batch failures', 'color' => 'red', 'icon' => '<line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/>'],
    ];
    $colorMap = [
        'indigo' => ['text' => 'text-indigo-400', 'bg' => 'bg-indigo-500/10', 'ring' => 'ring-indigo-500/20'],
        'rose' => ['text' => 'text-rose-400', 'bg' => 'bg-rose-500/10', 'ring' => 'ring-rose-500/20'],
        'cyan' => ['text' => 'text-cyan-400', 'bg' => 'bg-cyan-500/10', 'ring' => 'ring-cyan-500/20'],
        'amber' => ['text' => 'text-amber-400', 'bg' => 'bg-amber-500/10', 'ring' => 'ring-amber-500/20'],
        'emerald' => ['text' => 'text-emerald-400', 'bg' => 'bg-emerald-500/10', 'ring' => 'ring-emerald-500/20'],
        'orange' => ['text' => 'text-orange-400', 'bg' => 'bg-orange-500/10', 'ring' => 'ring-orange-500/20'],
        'pink' => ['text' => 'text-pink-400', 'bg' => 'bg-pink-500/10', 'ring' => 'ring-pink-500/20'],
        'violet' => ['text' => 'text-violet-400', 'bg' => 'bg-violet-500/10', 'ring' => 'ring-violet-500/20'],
        'yellow' => ['text' => 'text-yellow-400', 'bg' => 'bg-yellow-500/10', 'ring' => 'ring-yellow-500/20'],
        'red' => ['text' => 'text-red-400', 'bg' => 'bg-red-500/10', 'ring' => 'ring-red-500/20'],
    ];
    @endphp

    @foreach(array_slice($metrics, 0, 5) as $metric)
    @php $c = $colorMap[$metric['color']]; @endphp
    <div class="card metric-card p-4 transition-all hover:border-border/80">
        <div class="flex items-start justify-between">
            <div>
                <div class="text-[10px] font-medium uppercase tracking-wider text-muted">{{ $metric['label'] }}</div>
                <div class="mt-2 text-2xl font-bold tracking-tight">{{ $metric['value'] }}</div>
                <div class="mt-1 text-[10px] text-slate-600">{{ $metric['sub'] }}</div>
            </div>
            <div class="{{ $c['bg'] }} {{ $c['ring'] }} ring-1 rounded-xl p-2.5">
                <svg class="{{ $c['text'] }}" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $metric['icon'] !!}</svg>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-5">
    @foreach(array_slice($metrics, 5) as $metric)
    @php $c = $colorMap[$metric['color']]; @endphp
    <div class="card metric-card p-4 transition-all hover:border-border/80">
        <div class="flex items-start justify-between">
            <div>
                <div class="text-[10px] font-medium uppercase tracking-wider text-muted">{{ $metric['label'] }}</div>
                <div class="mt-2 text-2xl font-bold tracking-tight">{{ $metric['value'] }}</div>
                <div class="mt-1 text-[10px] text-slate-600">{{ $metric['sub'] }}</div>
            </div>
            <div class="{{ $c['bg'] }} {{ $c['ring'] }} ring-1 rounded-xl p-2.5">
                <svg class="{{ $c['text'] }}" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $metric['icon'] !!}</svg>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- Charts row --}}
<div class="mt-6 grid gap-4 xl:grid-cols-3">
    {{-- Request Volume Chart --}}
    <div class="card col-span-2 p-5">
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-semibold">Request Volume</h3>
                <p class="mt-0.5 text-[10px] text-muted">Last 14 days</p>
            </div>
            <span class="flex items-center gap-1.5 text-[10px] text-emerald-400">
                <span class="h-1.5 w-1.5 rounded-full bg-emerald-400 live-indicator"></span>Live
            </span>
        </div>
        <div class="h-52">
            <canvas id="requestVolumeChart"></canvas>
        </div>
    </div>

    {{-- Status Distribution --}}
    <div class="card p-5">
        <div class="mb-4">
            <h3 class="text-sm font-semibold">Response Status</h3>
            <p class="mt-0.5 text-[10px] text-muted">Request status breakdown</p>
        </div>
        <div class="h-52 flex items-center justify-center">
            <canvas id="statusChart"></canvas>
        </div>
    </div>
</div>

{{-- Activity + Modules --}}
<div class="mt-4 grid gap-4 xl:grid-cols-[1fr_320px]">

    {{-- Recent Activity --}}
    <div class="card overflow-hidden">
        <div class="flex items-center justify-between border-b border-border px-5 py-4">
            <div>
                <h3 class="text-sm font-semibold">Recent Activity</h3>
                <p class="mt-0.5 text-[10px] text-muted">Latest events from all collectors</p>
            </div>
            <span class="flex items-center gap-1.5 rounded-full border border-emerald-500/30 bg-emerald-500/10 px-2.5 py-1 text-[10px] font-medium text-emerald-400">
                <span class="live-indicator h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                LIVE
            </span>
        </div>
        <div class="divide-y divide-border">
            @forelse($recent as $record)
            <a href="{{ route('monitorx.show', [$record->module, $record]) }}"
               class="flex items-center gap-4 px-5 py-3 transition-all hover:bg-white/[.02]">
                @php
                $dot = match($record->status) {
                    'failed', 'denied' => 'bg-rose-400',
                    'slow' => 'bg-amber-400',
                    'pending' => 'bg-sky-400',
                    default => 'bg-indigo-400',
                };
                @endphp
                <span class="h-2 w-2 shrink-0 rounded-full {{ $dot }}"></span>
                <div class="min-w-0 flex-1">
                    <div class="truncate text-xs font-medium text-slate-200">{{ $record->summary ?: $record->event }}</div>
                    <div class="mt-0.5 flex items-center gap-2 text-[10px] text-muted">
                        <span class="font-medium text-slate-500">{{ config('monitorx.modules.'.$record->module, $record->module) }}</span>
                        <span>·</span>
                        <span>{{ $record->occurred_at->diffForHumans() }}</span>
                    </div>
                </div>
                <div class="flex flex-col items-end gap-1">
                    @if($record->duration_ms)
                        <span class="text-[10px] text-slate-500">{{ number_format($record->duration_ms) }} ms</span>
                    @endif
                    @if($record->status)
                    <span class="status-badge {{ match($record->status) {
                        'failed','denied' => 'bg-rose-500/10 text-rose-400',
                        'slow' => 'bg-amber-500/10 text-amber-400',
                        'pending' => 'bg-sky-500/10 text-sky-400',
                        'success','allowed','submitted','hit' => 'bg-emerald-500/10 text-emerald-400',
                        default => 'bg-slate-500/10 text-slate-400',
                    } }}">{{ $record->status }}</span>
                    @endif
                </div>
            </a>
            @empty
            <div class="px-5 py-16 text-center">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-white/5">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" class="text-muted">
                        <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
                    </svg>
                </div>
                <div class="mt-3 text-sm text-slate-400">Waiting for activity</div>
                <p class="mt-1 text-xs text-muted">Collected events will appear here</p>
            </div>
            @endforelse
        </div>
    </div>

    {{-- Module Activity Bars --}}
    <div class="card p-5">
        <h3 class="text-sm font-semibold">Module Activity</h3>
        <p class="mt-0.5 text-[10px] text-muted">Record counts per module</p>
        @php $maxCount = $counts->max() ?: 1; @endphp
        <div class="mt-4 space-y-3">
            @foreach(config('monitorx.modules') as $slug => $label)
            @php $count = $counts[$slug] ?? 0; $pct = min(100, ($count / $maxCount) * 100); @endphp
            <a href="{{ route('monitorx.index', $slug) }}" class="block group">
                <div class="mb-1 flex items-center justify-between text-[10px]">
                    <span class="text-slate-400 group-hover:text-slate-200 transition-colors">{{ $label }}</span>
                    <span class="font-mono text-muted">{{ number_format($count) }}</span>
                </div>
                <div class="h-1 overflow-hidden rounded-full bg-white/5">
                    <div class="h-full rounded-full bg-indigo-500/60 transition-all duration-500 group-hover:bg-indigo-400"
                         style="width: {{ $pct }}%"></div>
                </div>
            </a>
            @endforeach
        </div>
    </div>
</div>

@push('scripts')
<script>
(function() {
    const chartDefaults = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false }, tooltip: {
            backgroundColor: '#131c30',
            borderColor: '#1e2d47',
            borderWidth: 1,
            titleColor: '#94a3b8',
            bodyColor: '#e2e8f0',
            padding: 10,
        }},
    };

    // Request Volume Chart
    const volumeData = @json($requestVolumeChart);
    new Chart(document.getElementById('requestVolumeChart'), {
        type: 'line',
        data: {
            labels: Object.keys(volumeData).map(d => new Date(d).toLocaleDateString('en', { month: 'short', day: 'numeric' })),
            datasets: [{
                data: Object.values(volumeData),
                borderColor: '#6366f1',
                backgroundColor: 'rgba(99, 102, 241, 0.08)',
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                pointRadius: 3,
                pointBackgroundColor: '#6366f1',
                pointBorderColor: '#080d1a',
                pointBorderWidth: 2,
            }]
        },
        options: {
            ...chartDefaults,
            scales: {
                x: { grid: { color: '#1e2d47' }, ticks: { color: '#6b7fa3', font: { size: 10 } } },
                y: { grid: { color: '#1e2d47' }, ticks: { color: '#6b7fa3', font: { size: 10 }, precision: 0 } }
            }
        }
    });

    // Status Distribution Doughnut
    const statusData = @json($statusDistribution);
    const statusLabels = Object.keys(statusData);
    const statusColors = { success: '#10b981', failed: '#f43f5e', slow: '#f59e0b', pending: '#0ea5e9' };
    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: statusLabels,
            datasets: [{
                data: Object.values(statusData),
                backgroundColor: statusLabels.map(s => statusColors[s] || '#6366f1'),
                borderColor: '#131c30',
                borderWidth: 3,
                hoverOffset: 4,
            }]
        },
        options: {
            ...chartDefaults,
            cutout: '72%',
            plugins: {
                ...chartDefaults.plugins,
                legend: {
                    display: true,
                    position: 'bottom',
                    labels: { color: '#6b7fa3', font: { size: 10 }, padding: 12, boxWidth: 8, usePointStyle: true }
                }
            }
        }
    });
})();
</script>
@endpush
@endsection
