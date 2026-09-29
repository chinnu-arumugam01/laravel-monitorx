@extends('monitorx.layout', ['title' => 'Settings', 'crumb' => 'Settings'])

@section('content')
<div class="mb-6">
    <h2 class="text-xl font-semibold">Settings & System Info</h2>
    <p class="mt-0.5 text-xs text-muted">Configuration overview and data retention controls.</p>
</div>

<div class="grid gap-5 xl:grid-cols-[1fr_360px]">
    <div class="space-y-5">

        {{-- Configuration --}}
        <div class="card overflow-hidden">
            <div class="border-b border-border px-5 py-4">
                <h3 class="text-sm font-semibold">Active Configuration</h3>
            </div>
            <div class="divide-y divide-border">
                @foreach([
                    ['enabled', 'Monitoring Enabled', $config['enabled'] ? 'Yes' : 'No', $config['enabled'] ? 'text-emerald-400' : 'text-rose-400'],
                    ['path', 'Dashboard Path', '/'.$config['path'], 'text-slate-300'],
                    ['retention_days', 'Retention Period', $config['retention_days'].' days', 'text-slate-300'],
                    ['slow_request_ms', 'Slow Request Threshold', $config['slow_request_ms'].' ms', 'text-amber-400'],
                    ['slow_query_ms', 'Slow Query Threshold', $config['slow_query_ms'].' ms', 'text-amber-400'],
                    ['dumps_enabled', 'Dumps Enabled', $config['dumps_enabled'] ? 'Yes' : 'No (production)', $config['dumps_enabled'] ? 'text-emerald-400' : 'text-slate-500'],
                ] as [$key, $label, $value, $color])
                <div class="flex items-center justify-between px-5 py-3">
                    <div>
                        <div class="text-xs font-medium text-slate-300">{{ $label }}</div>
                        <div class="text-[10px] text-muted font-mono">monitorx.{{ $key }}</div>
                    </div>
                    <span class="font-mono text-xs {{ $color }}">{{ $value }}</span>
                </div>
                @endforeach

                <div class="flex items-center justify-between px-5 py-3">
                    <div>
                        <div class="text-xs font-medium text-slate-300">Redacted Fields</div>
                        <div class="text-[10px] text-muted font-mono">monitorx.redact</div>
                    </div>
                    <div class="flex flex-wrap justify-end gap-1 max-w-xs">
                        @foreach($config['redact'] as $field)
                            <span class="status-badge bg-slate-500/10 text-slate-500 font-mono">{{ $field }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Modules --}}
        <div class="card overflow-hidden">
            <div class="border-b border-border px-5 py-4">
                <h3 class="text-sm font-semibold">Module Status</h3>
                <p class="mt-0.5 text-[10px] text-muted">All 18 monitoring modules are active</p>
            </div>
            <div class="grid grid-cols-2 divide-y divide-border">
                @foreach(config('monitorx.modules') as $slug => $label)
                <div class="flex items-center justify-between px-5 py-2.5 {{ $loop->even ? 'border-l border-border' : '' }}">
                    <span class="text-xs text-slate-300">{{ $label }}</span>
                    <div class="flex items-center gap-2">
                        <span class="font-mono text-[10px] text-muted">{{ number_format($moduleCounts[$slug] ?? 0) }}</span>
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

    </div>

    {{-- Stats sidebar --}}
    <div class="space-y-5">

        {{-- Storage stats --}}
        <div class="card p-5">
            <h3 class="text-sm font-semibold">Storage Statistics</h3>
            <div class="mt-4 space-y-4">
                <div class="flex items-center justify-between text-xs">
                    <span class="text-muted">Total Records</span>
                    <span class="font-mono font-semibold text-slate-200">{{ number_format($totalRecords) }}</span>
                </div>
                <div class="flex items-center justify-between text-xs">
                    <span class="text-muted">Payload Size (est.)</span>
                    <span class="font-mono font-semibold text-slate-200">
                        @php
                        $bytes = $diskUsage->payload_size ?? 0;
                        $units = ['B','KB','MB','GB'];
                        $u = 0;
                        while ($bytes >= 1024 && $u < 3) { $bytes /= 1024; $u++; }
                        @endphp
                        {{ round($bytes, 1) }} {{ $units[$u] }}
                    </span>
                </div>
                <div class="flex items-center justify-between text-xs">
                    <span class="text-muted">Environments</span>
                    <span class="text-slate-200">{{ $environments->join(', ') }}</span>
                </div>
                <div class="flex items-center justify-between text-xs">
                    <span class="text-muted">Retention Policy</span>
                    <span class="text-emerald-400">{{ $config['retention_days'] }} days</span>
                </div>
            </div>
        </div>

        {{-- Prune control --}}
        <div class="card p-5">
            <h3 class="text-sm font-semibold">Data Retention</h3>
            <p class="mt-1 text-[10px] text-muted">Records older than the retention period are pruned automatically by the scheduler. Run manually below.</p>
            <div class="mt-4 rounded-lg border border-border bg-surface p-3">
                <div class="font-mono text-[11px] text-slate-400">php artisan monitorx:prune</div>
                <div class="mt-1 text-[10px] text-muted">Scheduled: daily</div>
            </div>
            <div class="mt-3 text-[10px] text-muted">
                Set <code class="font-mono text-slate-400">MONITORX_RETENTION_DAYS</code> in your <code class="font-mono text-slate-400">.env</code> to change the period.
            </div>
        </div>

        {{-- Env variables --}}
        <div class="card p-5">
            <h3 class="text-sm font-semibold">Environment Variables</h3>
            <div class="mt-3 space-y-2">
                @foreach([
                    'MONITORX_ENABLED' => 'true',
                    'MONITORX_PATH' => 'monitorx',
                    'MONITORX_USERNAME' => '(set in production)',
                    'MONITORX_PASSWORD' => '(set in production)',
                    'MONITORX_RETENTION_DAYS' => '30',
                    'MONITORX_SLOW_REQUEST_MS' => '1000',
                    'MONITORX_SLOW_QUERY_MS' => '100',
                    'MONITORX_DUMPS_ENABLED' => '(auto: non-production)',
                ] as $var => $example)
                <div class="flex items-center gap-2 text-[10px]">
                    <code class="font-mono text-indigo-300">{{ $var }}</code>
                    <span class="text-slate-600">=</span>
                    <span class="text-muted">{{ $example }}</span>
                </div>
                @endforeach
            </div>
        </div>

    </div>
</div>
@endsection
