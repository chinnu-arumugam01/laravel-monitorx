@extends('monitorx.layout', ['title' => 'Record #'.$record->id, 'crumb' => config('monitorx.modules.'.$module)])

@section('content')
<div class="mb-5 flex items-center gap-3">
    <a href="{{ route('monitorx.index', $module) }}"
       class="flex items-center gap-2 text-xs text-muted hover:text-white transition-colors">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="15 18 9 12 15 6"/></svg>
        Back to {{ config('monitorx.modules.'.$module) }}
    </a>
    <span class="text-slate-700">/</span>
    <span class="text-xs text-slate-400">Record #{{ $record->id }}</span>
</div>

<div class="grid gap-5 xl:grid-cols-[1fr_360px]">

    {{-- Main detail --}}
    <div class="space-y-4">

        {{-- Header card --}}
        <div class="card p-5">
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <div class="flex items-center gap-2 text-[10px] text-muted">
                        <span class="font-medium text-slate-500">{{ config('monitorx.modules.'.$module) }}</span>
                        <span>·</span>
                        <span>{{ $record->event }}</span>
                        <span>·</span>
                        <span>{{ $record->occurred_at->format('M j, Y H:i:s.v') }}</span>
                    </div>
                    <h2 class="mt-2 break-words text-lg font-semibold leading-snug">{{ $record->summary ?: $record->event }}</h2>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <span class="status-badge {{ match($record->status) {
                            'failed','denied' => 'bg-rose-500/10 text-rose-400 ring-1 ring-rose-500/30',
                            'slow' => 'bg-amber-500/10 text-amber-400 ring-1 ring-amber-500/30',
                            'pending','running' => 'bg-sky-500/10 text-sky-400 ring-1 ring-sky-500/30',
                            'success','allowed','submitted','hit' => 'bg-emerald-500/10 text-emerald-400 ring-1 ring-emerald-500/30',
                            default => 'bg-slate-500/10 text-slate-400 ring-1 ring-slate-500/30',
                        } }}">{{ $record->status ?: 'recorded' }}</span>
                        <span class="status-badge bg-indigo-500/10 text-indigo-400 ring-1 ring-indigo-500/30">{{ $record->module }}</span>
                        <span class="status-badge bg-slate-500/10 text-slate-400">{{ $record->environment }}</span>
                        @if($record->duration_ms)
                            <span class="status-badge {{ $record->isSlow() ? 'bg-amber-500/10 text-amber-400' : 'bg-slate-500/10 text-slate-400' }}">
                                {{ number_format($record->duration_ms) }} ms
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Metadata grid --}}
            <dl class="mt-5 grid grid-cols-2 gap-4 border-t border-border pt-5 sm:grid-cols-3">
                <div>
                    <dt class="text-[10px] font-semibold uppercase tracking-wider text-slate-600">Record ID</dt>
                    <dd class="mt-1 font-mono text-xs">{{ $record->id }}</dd>
                </div>
                <div>
                    <dt class="text-[10px] font-semibold uppercase tracking-wider text-slate-600">Application</dt>
                    <dd class="mt-1 text-xs">{{ $record->application }}</dd>
                </div>
                <div>
                    <dt class="text-[10px] font-semibold uppercase tracking-wider text-slate-600">Environment</dt>
                    <dd class="mt-1 text-xs">{{ $record->environment }}</dd>
                </div>
                <div>
                    <dt class="text-[10px] font-semibold uppercase tracking-wider text-slate-600">Duration</dt>
                    <dd class="mt-1 font-mono text-xs {{ $record->isSlow() ? 'text-amber-400' : '' }}">
                        {{ $record->duration_ms !== null ? number_format($record->duration_ms).' ms' : '—' }}
                    </dd>
                </div>
                @if($record->user_id)
                <div>
                    <dt class="text-[10px] font-semibold uppercase tracking-wider text-slate-600">User ID</dt>
                    <dd class="mt-1 font-mono text-xs">{{ $record->user_id }}</dd>
                </div>
                @endif
                <div>
                    <dt class="text-[10px] font-semibold uppercase tracking-wider text-slate-600">Recorded at</dt>
                    <dd class="mt-1 text-xs text-muted">{{ $record->occurred_at->diffForHumans() }}</dd>
                </div>
            </dl>

            @if($record->correlation_id)
            <div class="mt-4 border-t border-border pt-4">
                <dt class="text-[10px] font-semibold uppercase tracking-wider text-slate-600">Correlation ID</dt>
                <dd class="mt-1.5 flex items-center gap-2">
                    <span class="font-mono text-[11px] text-indigo-300">{{ $record->correlation_id }}</span>
                    <a href="{{ route('monitorx.index', ['module' => $module, 'correlation_id' => $record->correlation_id]) }}"
                       class="text-[10px] text-indigo-400 hover:text-indigo-300">Filter by this ID →</a>
                </dd>
            </div>
            @endif
        </div>

        {{-- Payload --}}
        @if($record->payload)
        <div class="card overflow-hidden" x-data="{ copied: false }">
            <div class="flex items-center justify-between border-b border-border px-5 py-3">
                <h3 class="text-xs font-semibold">Captured Payload</h3>
                <button @click="navigator.clipboard.writeText($el.closest('[x-data]').querySelector('pre').textContent); copied = true; setTimeout(() => copied = false, 2000)"
                        class="flex items-center gap-1.5 text-[10px] text-muted hover:text-white transition-colors">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                    <span x-text="copied ? 'Copied!' : 'Copy'"></span>
                </button>
            </div>
            <pre class="max-h-[60vh] overflow-auto p-5 text-[11px] leading-5 text-slate-300 font-mono" style="background: #080d1a">{{ json_encode($record->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
        </div>
        @endif

    </div>

    {{-- Sidebar: Timeline & Related --}}
    <div class="space-y-4">

        {{-- Correlation Timeline --}}
        <div class="card overflow-hidden">
            <div class="border-b border-border px-5 py-4">
                <h3 class="text-xs font-semibold">Correlated Timeline</h3>
                <p class="mt-0.5 text-[10px] text-muted">
                    {{ $timeline->count() }} events sharing this request
                </p>
            </div>
            <div class="max-h-96 overflow-y-auto p-5">
                @forelse($timeline as $item)
                <div class="relative pb-5 pl-5 last:pb-0 {{ !$loop->last ? 'border-l border-border' : '' }}">
                    <span class="absolute -left-[5px] top-1 h-2.5 w-2.5 rounded-full border-2 border-panel {{ $item->id === $record->id ? 'bg-indigo-400' : 'bg-slate-600' }}"></span>
                    <div class="text-[10px] font-semibold text-slate-400">
                        {{ config('monitorx.modules.'.$item->module, $item->module) }}
                        <span class="ml-1 text-slate-600">·</span>
                        <span class="text-slate-600">{{ $item->event }}</span>
                    </div>
                    <div class="mt-0.5 truncate text-[11px] {{ $item->id === $record->id ? 'text-indigo-300 font-medium' : 'text-slate-300' }}">
                        {{ $item->summary ?: $item->event }}
                    </div>
                    <div class="mt-0.5 flex items-center gap-2">
                        <span class="font-mono text-[9px] text-slate-600">{{ $item->occurred_at->format('H:i:s.v') }}</span>
                        @if($item->duration_ms)
                            <span class="text-[9px] text-muted">{{ number_format($item->duration_ms) }}ms</span>
                        @endif
                    </div>
                    @if($item->id !== $record->id)
                    <a href="{{ route('monitorx.show', [$item->module, $item]) }}"
                       class="mt-0.5 inline-block text-[9px] text-indigo-500 hover:text-indigo-400">View →</a>
                    @endif
                </div>
                @empty
                <div class="text-center py-6">
                    <div class="text-xs text-muted">No correlation ID for this record</div>
                </div>
                @endforelse
            </div>
        </div>

        {{-- Related by module --}}
        @if($related->count())
        <div class="card overflow-hidden">
            <div class="border-b border-border px-5 py-4">
                <h3 class="text-xs font-semibold">Related Records</h3>
                <p class="mt-0.5 text-[10px] text-muted">Other modules linked to this request</p>
            </div>
            <div class="divide-y divide-border">
                @foreach($related as $module_name => $items)
                <div class="px-5 py-3">
                    <div class="mb-2 text-[10px] font-semibold uppercase tracking-wider text-slate-500">
                        {{ config('monitorx.modules.'.$module_name, $module_name) }}
                        <span class="ml-1 text-slate-600">({{ $items->count() }})</span>
                    </div>
                    @foreach($items->take(3) as $item)
                    <a href="{{ route('monitorx.show', [$item->module, $item]) }}"
                       class="flex items-center gap-2 py-1 hover:text-indigo-300 transition-colors">
                        <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $item->isFailed() ? 'bg-rose-400' : 'bg-indigo-400' }}"></span>
                        <span class="truncate text-[11px] text-slate-400">{{ $item->summary ?: $item->event }}</span>
                    </a>
                    @endforeach
                </div>
                @endforeach
            </div>
        </div>
        @endif

    </div>
</div>
@endsection
